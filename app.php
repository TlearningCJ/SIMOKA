<?php
// ===== SIMOKA core: config, koneksi DB, auth, logika status, layout =====
date_default_timezone_set('Asia/Jakarta');
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'simoka';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $ex) {
            http_response_code(503);
            exit('<!doctype html><meta charset="utf-8"><body style="font-family:Arial;max-width:560px;margin:60px auto;padding:0 16px">'
               . '<h2>Database belum siap</h2><p>SIMOKA tidak dapat terhubung ke MySQL. Pastikan <b>MySQL di XAMPP sudah Start</b> '
               . 'dan database <b>simoka</b> sudah di-import (jalankan <code>setup.bat</code>).</p><p style="color:#888">' . htmlspecialchars($ex->getMessage()) . '</p>');
        }
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function role(): ?string { return user()['peran'] ?? null; }
function require_role(string ...$roles): void {
    if (!user()) { header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'])); exit; }
    if (!in_array(role(), $roles, true)) { http_response_code(403); layout('Akses ditolak', '<div class="c"><p>Anda tidak memiliki hak akses ke halaman ini.</p></div>'); exit; }
}
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void { if (($_POST['csrf'] ?? '') !== csrf()) { http_response_code(400); exit('Token tidak valid, muat ulang halaman.'); } }

const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
function hari_dari_tanggal(string $tgl): string { return HARI[(int)date('N', strtotime($tgl))]; }

/**
 * Status ruangan pada suatu waktu.
 * Aturan (Bab VI 6.3): jadwal menjadi acuan; laporan PJ terbaru hari itu
 * menjadi acuan utama selama jam jadwal berlangsung. Di luar jadwal,
 * laporan "digunakan" dalam 2 jam terakhir tetap dianggap dipakai.
 * Return: ['status'=>'tersedia'|'digunakan', 'sumber'=>..., 'jadwal'=>?, 'laporan'=>?]
 */
function status_ruangan(int $id, ?int $ts = null): array {
    $ts ??= time();
    $hari = HARI[(int)date('N', $ts)];
    $jam = date('H:i:s', $ts);
    $jadwal = q('SELECT j.*, p.nama pj FROM jadwal j LEFT JOIN pengguna p ON p.id_pengguna=j.id_pj
                 WHERE id_ruangan=? AND hari=? AND jam_mulai<=? AND jam_selesai>? LIMIT 1', [$id, $hari, $jam, $jam])->fetch() ?: null;
    $lap = q('SELECT l.*, p.nama pj FROM laporan_status l JOIN pengguna p ON p.id_pengguna=l.id_pj
              WHERE id_ruangan=? AND DATE(waktu_laporan)=? AND waktu_laporan<=? ORDER BY waktu_laporan DESC, id_laporan DESC LIMIT 1',
              [$id, date('Y-m-d', $ts), date('Y-m-d H:i:s', $ts)])->fetch() ?: null;
    if ($jadwal) {
        $mulai = strtotime(date('Y-m-d', $ts) . ' ' . $jadwal['jam_mulai']);
        if ($lap && strtotime($lap['waktu_laporan']) >= $mulai - 15 * 60) {
            return ['status' => $lap['status'] === 'digunakan' ? 'digunakan' : 'tersedia', 'sumber' => 'laporan', 'jadwal' => $jadwal, 'laporan' => $lap];
        }
        return ['status' => 'digunakan', 'sumber' => 'jadwal', 'jadwal' => $jadwal, 'laporan' => $lap];
    }
    if ($lap && $lap['status'] === 'digunakan' && strtotime($lap['waktu_laporan']) >= $ts - 2 * 3600) {
        return ['status' => 'digunakan', 'sumber' => 'laporan', 'jadwal' => null, 'laporan' => $lap];
    }
    return ['status' => 'tersedia', 'sumber' => $lap ? 'laporan' : 'jadwal', 'jadwal' => null, 'laporan' => $lap];
}

/** Apakah ruangan kosong pada rentang tanggal+jam (untuk pencarian, FR-07). */
function ruangan_kosong(int $id, string $tgl, string $dari, string $sampai): bool {
    $sql = 'SELECT COUNT(*) FROM jadwal WHERE id_ruangan=? AND hari=? AND jam_mulai<? AND jam_selesai>? AND id_jadwal<>?';
    $now = time();
    $a = strtotime("$tgl $dari"); $b = strtotime("$tgl $sampai");
    // Rentang mencakup saat ini: pakai status real-time (termasuk laporan PJ)
    if ($a <= $now && $now < $b) {
        $st = status_ruangan($id, $now);
        if ($st['status'] !== 'tersedia') return false;
        $skip = $st['jadwal']['id_jadwal'] ?? 0; // jadwal aktif yang dilaporkan kosong
        return q($sql, [$id, hari_dari_tanggal($tgl), $sampai, date('H:i:s', $now), $skip])->fetchColumn() == 0;
    }
    return q($sql, [$id, hari_dari_tanggal($tgl), $sampai, $dari, 0])->fetchColumn() == 0;
}

function fasilitas_ruangan(int $id): array {
    return q('SELECT f.nama_fasilitas, rf.jumlah, rf.kondisi, f.id_fasilitas FROM ruangan_fasilitas rf
              JOIN fasilitas f ON f.id_fasilitas=rf.id_fasilitas WHERE rf.id_ruangan=? ORDER BY f.nama_fasilitas', [$id])->fetchAll();
}
function badge(string $s): string {
    return $s === 'digunakan' ? '<span class="b no">Digunakan</span>' : '<span class="b ok">Tersedia</span>';
}
function jam($t): string { return str_replace(':', '.', substr($t, 0, 5)); }

function layout(string $title, string $body, string $active = '', string $sub = ''): void {
    $u = user(); $f = flash();
    $nav = [['index.php', 'Ruangan', 'ruangan'], ['jadwal.php', 'Jadwal', 'jadwal']];
    if (in_array(role(), ['pj_kelas', 'admin'], true)) $nav[] = ['lapor.php', 'Lapor', 'lapor'];
    if (role() === 'admin') $nav[] = ['admin.php', 'Kelola', 'admin'];
    $nav[] = $u ? ['logout.php', 'Keluar', 'akun'] : ['login.php', 'Masuk', 'akun'];
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · SIMOKA</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="bar"><a class="brand" href="index.php"><img class="logo" src="assets/logo-fasilkom.png" alt="Logo Fasilkom UNSIKA">SIMOKA</a>
<nav class="nav"><?php foreach ($nav as [$h, $l, $k]): ?><a href="<?= $h ?>" class="<?= $k === $active ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></nav>
<span class="who"><?= $u ? e($u['nama']) . ' · ' . e(str_replace('_', ' ', $u['peran'])) : 'Pengunjung' ?></span></header>
<div class="top"><div class="wrap"><h1><?= e($title) ?></h1><p class="sub"><?= e($sub ?: 'Sistem Monitoring Ketersediaan Kelas') ?></p></div></div>
<main class="wrap"><?php if ($f): ?><div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?><?= $body ?></main>
</body></html><?php
}

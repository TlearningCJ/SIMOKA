<?php
// Lapor Status Penggunaan (UC-06) — FR-15, 17, 18, 19, 20
require 'app.php';
require_role('pj_kelas', 'admin');
$u = user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $rid = (int)($_POST['id_ruangan'] ?? 0);
    $status = $_POST['status'] ?? '';
    $ket = trim($_POST['keterangan'] ?? '');
    if (!q('SELECT 1 FROM ruangan WHERE id_ruangan=?', [$rid])->fetch() || !in_array($status, ['digunakan', 'tidak_digunakan'], true)) {
        flash('Pilih ruangan dan status terlebih dahulu.', 'err');
    } else {
        q('INSERT INTO laporan_status (id_ruangan,id_pj,status,keterangan,waktu_laporan) VALUES (?,?,?,?,NOW())', [$rid, $u['id_pengguna'], $status, mb_substr($ket, 0, 255) ?: null]);
        // FR-20: perbarui kolom status pada tabel ruangan
        q('UPDATE ruangan SET status=?, updated_at=NOW() WHERE id_ruangan=?', [$status === 'digunakan' ? 'digunakan' : 'tersedia', $rid]);
        $kode = q('SELECT kode_ruangan FROM ruangan WHERE id_ruangan=?', [$rid])->fetchColumn();
        flash("Laporan terkirim. Status $kode diperbarui menjadi \"" . ($status === 'digunakan' ? 'Digunakan' : 'Tersedia') . '" pukul ' . date('H.i') . '.');
    }
    header('Location: lapor.php'); exit;
}
$hari = HARI[(int)date('N')];
$milik = q('SELECT j.*, r.kode_ruangan FROM jadwal j JOIN ruangan r ON r.id_ruangan=j.id_ruangan WHERE j.id_pj=? AND hari=? ORDER BY jam_mulai', [$u['id_pengguna'], $hari])->fetchAll();
$semua = q('SELECT id_ruangan, kode_ruangan FROM ruangan ORDER BY kode_ruangan')->fetchAll();
$riwayat = q('SELECT l.*, r.kode_ruangan FROM laporan_status l JOIN ruangan r ON r.id_ruangan=l.id_ruangan WHERE id_pj=? ORDER BY waktu_laporan DESC LIMIT 10', [$u['id_pengguna']])->fetchAll();
ob_start(); ?>
<form class="card" method="post"><?= csrf_field() ?>
<label>Ruangan<select name="id_ruangan" required><option value="">— pilih ruangan —</option>
<?php if ($milik): ?><optgroup label="Jadwal kelas Anda hari ini"><?php foreach ($milik as $j): ?><option value="<?= $j['id_ruangan'] ?>"><?= e($j['kode_ruangan']) ?> — <?= e($j['kegiatan']) ?> (<?= jam($j['jam_mulai']) ?>–<?= jam($j['jam_selesai']) ?>)</option><?php endforeach; ?></optgroup><?php endif; ?>
<optgroup label="Semua ruangan"><?php foreach ($semua as $r): ?><option value="<?= $r['id_ruangan'] ?>"><?= e($r['kode_ruangan']) ?></option><?php endforeach; ?></optgroup></select></label>
<div class="ttl">Status saat ini</div>
<label class="opt"><input type="radio" name="status" value="digunakan" required> Sedang Digunakan</label>
<label class="opt"><input type="radio" name="status" value="tidak_digunakan"> Tidak Digunakan (kelas kosong / batal / selesai lebih cepat)</label>
<label>Keterangan (opsional)<textarea name="keterangan" maxlength="255" rows="3" placeholder="mis. Kelas diperpanjang sampai 11.40 untuk kuis"></textarea></label>
<button class="btn">Kirim Laporan</button></form>
<div class="card"><h3>Riwayat Laporan Anda</h3><?php if (!$riwayat): ?><p class="meta">Belum ada laporan.</p><?php else: ?><table><tr><th>Waktu</th><th>Ruangan</th><th>Status</th></tr>
<?php foreach ($riwayat as $l): ?><tr><td><?= date('d/m H.i', strtotime($l['waktu_laporan'])) ?></td><td><?= e($l['kode_ruangan']) ?></td><td><?= $l['status'] === 'digunakan' ? 'Digunakan' : 'Tidak digunakan' ?></td></tr><?php endforeach; ?></table><?php endif; ?></div>
<?php layout('Lapor Status Ruangan', ob_get_clean(), 'lapor');

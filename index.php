<?php
// Daftar Ruangan & Pencarian Ruangan Kosong (UC-02, UC-05) — FR-01, 03, 07, 13, 14, 16
require 'app.php';
$tgl = $_GET['tgl'] ?? date('Y-m-d');
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$kap = (int)($_GET['kap'] ?? 0);
$fas = array_map('intval', (array)($_GET['fas'] ?? []));
$cari = isset($_GET['cari']);
$err = '';
if ($cari && $dari && $sampai && $sampai <= $dari) $err = 'Jam selesai harus setelah jam mulai.';

$sql = 'SELECT * FROM ruangan r WHERE kapasitas >= ?'; $p = [$kap];
foreach ($fas as $f) { $sql .= ' AND EXISTS (SELECT 1 FROM ruangan_fasilitas rf WHERE rf.id_ruangan=r.id_ruangan AND rf.id_fasilitas=? AND rf.kondisi="baik")'; $p[] = $f; }
$rooms = q($sql . ' ORDER BY kode_ruangan', $p)->fetchAll();
$semuaFas = q('SELECT * FROM fasilitas ORDER BY nama_fasilitas')->fetchAll();

$hasil = [];
foreach ($rooms as $r) {
    $st = status_ruangan($r['id_ruangan']);
    if ($cari && $dari && $sampai && !$err && !ruangan_kosong($r['id_ruangan'], $tgl, $dari . ':00', $sampai . ':00')) continue;
    $r['st'] = $st; $hasil[] = $r;
}
ob_start(); ?>
<form class="card filter" method="get">
  <div class="row"><label>Tanggal<input type="date" name="tgl" value="<?= e($tgl) ?>"></label>
  <label>Dari<input type="time" name="dari" value="<?= e($dari) ?>"></label><label>Sampai<input type="time" name="sampai" value="<?= e($sampai) ?>"></label></div>
  <div class="row"><label>Kapasitas min.<input type="number" min="0" name="kap" value="<?= $kap ?: '' ?>" placeholder="mis. 30"></label></div>
  <div class="chips"><?php foreach ($semuaFas as $f): ?><label class="chip"><input type="checkbox" name="fas[]" value="<?= $f['id_fasilitas'] ?>" <?= in_array($f['id_fasilitas'], $fas) ? 'checked' : '' ?>><?= e($f['nama_fasilitas']) ?></label><?php endforeach; ?></div>
  <button class="btn" name="cari" value="1">Cari Ruangan Kosong</button>
  <?php if ($cari): ?><a class="link" href="index.php">Reset filter</a><?php endif; ?>
</form>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<p class="meta">Status diperbarui: <?= date('d/m/Y H.i') ?> WIB<?= $cari && $dari && $sampai ? ' · menampilkan ruangan kosong ' . e(hari_dari_tanggal($tgl)) . ' ' . e($dari) . '–' . e($sampai) : '' ?></p>
<?php if (!$hasil): ?><div class="card empty">Tidak ada ruangan tersedia untuk filter ini. Coba ubah waktu, kapasitas, atau fasilitas.</div><?php endif; ?>
<div class="grid"><?php foreach ($hasil as $r): $st = $r['st']; $fs = fasilitas_ruangan($r['id_ruangan']); ?>
<a class="card room" href="ruangan.php?id=<?= $r['id_ruangan'] ?>">
  <div class="rh"><h3><?= e($r['kode_ruangan']) ?></h3><?= badge($st['status']) ?></div>
  <div class="meta"><?= e($r['gedung']) ?> · Lantai <?= $r['lantai'] ?> · <?= $r['kapasitas'] ?> orang</div>
  <?php if ($st['sumber'] === 'laporan' && $st['laporan']): ?><div class="meta">Dilaporkan PJ <?= jam(substr($st['laporan']['waktu_laporan'], 11)) ?><?= $st['laporan']['keterangan'] ? ' · ' . e($st['laporan']['keterangan']) : '' ?></div>
  <?php elseif ($st['jadwal']): ?><div class="meta"><?= e($st['jadwal']['kegiatan']) ?> s.d. <?= jam($st['jadwal']['jam_selesai']) ?></div><?php endif; ?>
  <div><?php foreach ($fs as $f): ?><span class="tag<?= $f['kondisi'] === 'rusak' ? ' bad' : '' ?>"><?= e($f['nama_fasilitas']) ?></span><?php endforeach; ?></div>
</a><?php endforeach; ?></div>
<?php layout('Daftar Ruangan', ob_get_clean(), 'ruangan');

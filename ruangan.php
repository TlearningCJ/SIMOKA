<?php
// Detail Ruangan (UC-03) — FR-02, 05, 06, 08–12
require 'app.php';
$id = (int)($_GET['id'] ?? 0);
$r = q('SELECT * FROM ruangan WHERE id_ruangan=?', [$id])->fetch();
if (!$r) { http_response_code(404); layout('Ruangan tidak ditemukan', '<div class="card">Data ruangan tidak ditemukan. <a href="index.php">Kembali</a></div>'); exit; }
$st = status_ruangan($id);
$fs = fasilitas_ruangan($id);
$hari = HARI[(int)date('N')];
$jd = q('SELECT j.*, p.nama pj FROM jadwal j LEFT JOIN pengguna p ON p.id_pengguna=j.id_pj WHERE id_ruangan=? AND hari=? ORDER BY jam_mulai', [$id, $hari])->fetchAll();
$lap = q('SELECT l.*, p.nama pj FROM laporan_status l JOIN pengguna p ON p.id_pengguna=l.id_pj WHERE id_ruangan=? ORDER BY waktu_laporan DESC LIMIT 5', [$id])->fetchAll();
ob_start(); ?>
<div class="card"><?= badge($st['status']) ?> <span class="meta">· diperbarui <?= date('H.i') ?>
<?php if ($st['sumber'] === 'laporan' && $st['laporan']): ?> · berdasarkan laporan <?= e($st['laporan']['pj']) ?> pukul <?= jam(substr($st['laporan']['waktu_laporan'], 11)) ?><?php endif; ?></span>
<?php if ($st['jadwal']): ?><p class="meta">Jadwal saat ini: <?= e($st['jadwal']['kegiatan']) ?> (<?= jam($st['jadwal']['jam_mulai']) ?>–<?= jam($st['jadwal']['jam_selesai']) ?>)</p><?php endif; ?></div>
<div class="card"><h3>Informasi Ruangan</h3><table>
<tr><th>Nama</th><td><?= e($r['nama_ruangan']) ?></td></tr><tr><th>Kapasitas</th><td><?= $r['kapasitas'] ?> orang</td></tr>
<tr><th>Lokasi</th><td><?= e($r['gedung']) ?>, lantai <?= $r['lantai'] ?></td></tr></table></div>
<div class="card"><h3>Fasilitas</h3><?php if (!$fs): ?><p class="meta">Fasilitas belum didata.</p><?php else: ?><table><tr><th>Fasilitas</th><th>Jml</th><th>Kondisi</th></tr>
<?php foreach ($fs as $f): ?><tr><td><?= e($f['nama_fasilitas']) ?></td><td><?= $f['jumlah'] ?></td><td><?= $f['kondisi'] === 'baik' ? 'Baik' : '<span class="red">Rusak</span>' ?></td></tr><?php endforeach; ?></table><?php endif; ?></div>
<div class="card"><h3>Jadwal Hari Ini (<?= $hari ?>)</h3><?php if (!$jd): ?><p class="meta">Tidak ada jadwal, ruangan kosong sepanjang hari.</p><?php else: ?><table><tr><th>Jam</th><th>Kegiatan</th><th>PJ</th></tr>
<?php foreach ($jd as $j): ?><tr><td><?= jam($j['jam_mulai']) ?>–<?= jam($j['jam_selesai']) ?></td><td><?= e($j['kegiatan']) ?></td><td><?= e($j['pj'] ?? '-') ?></td></tr><?php endforeach; ?></table><?php endif; ?>
<a class="link" href="jadwal.php?ruangan=<?= $id ?>">Lihat jadwal seminggu →</a></div>
<?php if ($lap): ?><div class="card"><h3>Laporan PJ Terakhir</h3><table><tr><th>Waktu</th><th>Status</th><th>PJ</th></tr>
<?php foreach ($lap as $l): ?><tr><td><?= date('d/m H.i', strtotime($l['waktu_laporan'])) ?></td><td><?= $l['status'] === 'digunakan' ? 'Digunakan' : 'Tidak digunakan' ?><?= $l['keterangan'] ? '<br><span class="meta">' . e($l['keterangan']) . '</span>' : '' ?></td><td><?= e($l['pj']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<?php layout($r['kode_ruangan'], ob_get_clean(), 'ruangan');

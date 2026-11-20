<?php
// Jadwal Penggunaan (UC-04) — FR-02, FR-06
require 'app.php';
$rid = (int)($_GET['ruangan'] ?? 0);
$hari = $_GET['hari'] ?? HARI[min(6, (int)date('N'))];
if (!in_array($hari, array_slice(HARI, 0, 6), true)) $hari = 'Senin';
$rooms = q('SELECT id_ruangan, kode_ruangan FROM ruangan ORDER BY kode_ruangan')->fetchAll();
$sql = 'SELECT j.*, r.kode_ruangan, p.nama pj FROM jadwal j JOIN ruangan r ON r.id_ruangan=j.id_ruangan LEFT JOIN pengguna p ON p.id_pengguna=j.id_pj WHERE hari=?';
$p = [$hari]; if ($rid) { $sql .= ' AND j.id_ruangan=?'; $p[] = $rid; }
$rows = q($sql . ' ORDER BY jam_mulai, r.kode_ruangan', $p)->fetchAll();

// Ruang kosong: celah waktu tanpa jadwal pada jam operasional 07.00–18.00
const BUKA = '07:00:00', TUTUP = '18:00:00';
$kosong = [];
foreach (q('SELECT * FROM ruangan' . ($rid ? ' WHERE id_ruangan=' . $rid : '') . ' ORDER BY kode_ruangan')->fetchAll() as $r) {
    $js = q('SELECT jam_mulai, jam_selesai FROM jadwal WHERE id_ruangan=? AND hari=? ORDER BY jam_mulai', [$r['id_ruangan'], $hari])->fetchAll();
    $slot = []; $t = BUKA;
    foreach ($js as $j) {
        if ($j['jam_mulai'] > $t) $slot[] = [$t, min($j['jam_mulai'], TUTUP)];
        if ($j['jam_selesai'] > $t) $t = $j['jam_selesai'];
    }
    if ($t < TUTUP) $slot[] = [$t, TUTUP];
    $slot = array_filter($slot, fn($s) => strtotime($s[1]) - strtotime($s[0]) >= 30 * 60); // minimal 30 menit
    $r['slot'] = $slot; $r['penuh_kosong'] = !$js; $kosong[] = $r;
}
ob_start(); ?>
<form class="card filter" method="get"><div class="row">
<label>Hari<select name="hari"><?php foreach (array_slice(HARI, 0, 6) as $h): ?><option <?= $h === $hari ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?></select></label>
<label>Ruangan<select name="ruangan"><option value="0">Semua ruangan</option><?php foreach ($rooms as $r): ?><option value="<?= $r['id_ruangan'] ?>" <?= $rid == $r['id_ruangan'] ? 'selected' : '' ?>><?= e($r['kode_ruangan']) ?></option><?php endforeach; ?></select></label>
</div><button class="btn">Tampilkan</button></form>
<div class="card"><h3>Ruang Tersedia / Kosong — <?= e($hari) ?></h3>
<p class="meta">Jam operasional <?= jam(BUKA) ?>–<?= jam(TUTUP) ?>. Waktu di bawah ini tidak ada jadwal penggunaan.</p>
<table><tr><th>Ruangan</th><th>Kapasitas</th><th>Waktu Kosong</th></tr>
<?php foreach ($kosong as $r): ?><tr><td><a href="ruangan.php?id=<?= $r['id_ruangan'] ?>"><?= e($r['kode_ruangan']) ?></a></td><td><?= $r['kapasitas'] ?> orang</td>
<td><?php if ($r['penuh_kosong']): ?><span class="b ok">Kosong sepanjang hari</span><?php elseif (!$r['slot']): ?><span class="b no">Penuh</span><?php else: foreach ($r['slot'] as $s): ?><span class="tag slot"><?= jam($s[0]) ?>–<?= jam($s[1]) ?></span><?php endforeach; endif; ?></td></tr><?php endforeach; ?></table></div>
<div class="card"><h3>Jadwal Terpakai — <?= e($hari) ?></h3><?php if (!$rows): ?><p class="meta">Tidak ada jadwal pada hari <?= e($hari) ?>.</p><?php else: ?>
<table><tr><th>Jam</th><th>Ruangan</th><th>Kegiatan</th><th>PJ</th></tr>
<?php foreach ($rows as $j): ?><tr><td><?= jam($j['jam_mulai']) ?>–<?= jam($j['jam_selesai']) ?></td><td><a href="ruangan.php?id=<?= $j['id_ruangan'] ?>"><?= e($j['kode_ruangan']) ?></a></td><td><?= e($j['kegiatan']) ?></td><td><?= e($j['pj'] ?? '-') ?></td></tr><?php endforeach; ?></table><?php endif; ?></div>
<?php layout('Jadwal Penggunaan', ob_get_clean(), 'jadwal');

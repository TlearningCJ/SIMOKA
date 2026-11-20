<?php
// Kelola Data (UC-07, UC-08, UC-09) — FR-01, 02, 04, 09–12 ; khusus Admin (NFR-02)
require 'app.php';
require_role('admin');
$tab = $_GET['tab'] ?? 'ruangan';
$back = fn($t = null) => header('Location: admin.php?tab=' . ($t ?? $tab)) || exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $a = $_POST['aksi'] ?? '';
    try {
        switch ($a) {
            case 'simpan_ruangan':
                $id = (int)$_POST['id_ruangan'];
                $d = [trim($_POST['kode_ruangan']), trim($_POST['nama_ruangan']), trim($_POST['gedung']), (int)$_POST['lantai'], (int)$_POST['kapasitas']];
                if (in_array('', array_slice($d, 0, 3), true) || $d[4] < 1) { flash('Semua data ruangan wajib diisi dan kapasitas minimal 1.', 'err'); break; }
                if ($id) { q('UPDATE ruangan SET kode_ruangan=?, nama_ruangan=?, gedung=?, lantai=?, kapasitas=? WHERE id_ruangan=?', [...$d, $id]); }
                else { q('INSERT INTO ruangan (kode_ruangan,nama_ruangan,gedung,lantai,kapasitas) VALUES (?,?,?,?,?)', $d); $id = (int)db()->lastInsertId(); }
                // fasilitas ruangan (UC-08)
                q('DELETE FROM ruangan_fasilitas WHERE id_ruangan=?', [$id]);
                foreach ((array)($_POST['fas'] ?? []) as $fid => $on) {
                    $jml = max(1, (int)($_POST['jml'][$fid] ?? 1)); $kon = ($_POST['kon'][$fid] ?? 'baik') === 'rusak' ? 'rusak' : 'baik';
                    q('INSERT INTO ruangan_fasilitas (id_ruangan,id_fasilitas,jumlah,kondisi) VALUES (?,?,?,?)', [$id, (int)$fid, $jml, $kon]);
                }
                flash('Data ruangan ' . $d[0] . ' disimpan.'); break;
            case 'hapus_ruangan':
                q('DELETE FROM ruangan WHERE id_ruangan=?', [(int)$_POST['id']]); flash('Ruangan dihapus.'); break;
            case 'simpan_fasilitas':
                $n = trim($_POST['nama_fasilitas']); if ($n === '') { flash('Nama fasilitas wajib diisi.', 'err'); break; }
                if ($id = (int)$_POST['id_fasilitas']) q('UPDATE fasilitas SET nama_fasilitas=?, keterangan=? WHERE id_fasilitas=?', [$n, trim($_POST['keterangan']), $id]);
                else q('INSERT INTO fasilitas (nama_fasilitas, keterangan) VALUES (?,?)', [$n, trim($_POST['keterangan'])]);
                flash('Fasilitas disimpan.'); break;
            case 'hapus_fasilitas':
                q('DELETE FROM fasilitas WHERE id_fasilitas=?', [(int)$_POST['id']]); flash('Fasilitas dihapus.'); break;
            case 'simpan_jadwal':
                $id = (int)$_POST['id_jadwal']; $rid = (int)$_POST['id_ruangan']; $h = $_POST['hari']; $m = $_POST['jam_mulai']; $s = $_POST['jam_selesai'];
                $pj = (int)$_POST['id_pj'] ?: null; $k = trim($_POST['kegiatan']);
                if (!$rid || !$m || !$s || $k === '' || $s <= $m) { flash('Lengkapi jadwal; jam selesai harus setelah jam mulai.', 'err'); break; }
                $bentrok = q('SELECT kegiatan, jam_mulai, jam_selesai FROM jadwal WHERE id_ruangan=? AND hari=? AND jam_mulai<? AND jam_selesai>? AND id_jadwal<>?', [$rid, $h, $s, $m, $id])->fetch();
                if ($bentrok) { flash('Jadwal bentrok dengan "' . $bentrok['kegiatan'] . '" (' . jam($bentrok['jam_mulai']) . '–' . jam($bentrok['jam_selesai']) . ').', 'err'); break; }
                if ($id) q('UPDATE jadwal SET id_ruangan=?, id_pj=?, hari=?, jam_mulai=?, jam_selesai=?, kegiatan=? WHERE id_jadwal=?', [$rid, $pj, $h, $m, $s, $k, $id]);
                else q('INSERT INTO jadwal (id_ruangan,id_pj,hari,jam_mulai,jam_selesai,kegiatan) VALUES (?,?,?,?,?,?)', [$rid, $pj, $h, $m, $s, $k]);
                flash('Jadwal disimpan.'); break;
            case 'hapus_jadwal':
                q('DELETE FROM jadwal WHERE id_jadwal=?', [(int)$_POST['id']]); flash('Jadwal dihapus.'); break;
            case 'simpan_pengguna':
                $id = (int)$_POST['id_pengguna']; $n = trim($_POST['nama']); $em = trim($_POST['email']); $pr = $_POST['peran']; $pw = $_POST['password'] ?? '';
                if ($n === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || !in_array($pr, ['mahasiswa', 'pj_kelas', 'admin'], true) || (!$id && strlen($pw) < 6)) { flash('Data pengguna tidak valid (kata sandi minimal 6 karakter).', 'err'); break; }
                if ($id) { q('UPDATE pengguna SET nama=?, email=?, peran=? WHERE id_pengguna=?', [$n, $em, $pr, $id]); if ($pw !== '') q('UPDATE pengguna SET password=? WHERE id_pengguna=?', [password_hash($pw, PASSWORD_DEFAULT), $id]); }
                else q('INSERT INTO pengguna (nama,email,password,peran) VALUES (?,?,?,?)', [$n, $em, password_hash($pw, PASSWORD_DEFAULT), $pr]);
                flash('Pengguna disimpan.'); break;
            case 'hapus_pengguna':
                if ((int)$_POST['id'] === (int)user()['id_pengguna']) { flash('Tidak dapat menghapus akun sendiri.', 'err'); break; }
                q('DELETE FROM pengguna WHERE id_pengguna=?', [(int)$_POST['id']]); flash('Pengguna dihapus.'); break;
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() == 23000 ? 'Data sudah terdaftar (kode/nama/email harus unik).' : 'Gagal menyimpan: ' . $ex->getMessage(), 'err');
    }
    $back();
}

$del = fn($aksi, $id, $label) => '<form method="post" class="inline" onsubmit="return confirm(\'Hapus ' . e(addslashes($label)) . '?\')">' . csrf_field() . '<input type="hidden" name="aksi" value="' . $aksi . '"><input type="hidden" name="id" value="' . $id . '"><button class="lnk red">Hapus</button></form>';
$edit = (int)($_GET['edit'] ?? 0);
ob_start(); ?>
<div class="tabs"><?php foreach (['ruangan' => 'Ruangan & Fasilitas', 'jadwal' => 'Jadwal', 'fasilitas' => 'Master Fasilitas', 'pengguna' => 'Pengguna'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="?tab=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>

<?php if ($tab === 'ruangan'):
  $rows = q('SELECT * FROM ruangan ORDER BY kode_ruangan')->fetchAll();
  $cur = $edit ? q('SELECT * FROM ruangan WHERE id_ruangan=?', [$edit])->fetch() : null;
  $curF = []; if ($cur) foreach (fasilitas_ruangan($edit) as $f) $curF[$f['id_fasilitas']] = $f;
  $allF = q('SELECT * FROM fasilitas ORDER BY nama_fasilitas')->fetchAll(); ?>
<div class="card"><table><tr><th>Kode</th><th>Kap.</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['kode_ruangan']) ?><br><span class="meta"><?= e($r['gedung']) ?> lt.<?= $r['lantai'] ?></span></td><td><?= $r['kapasitas'] ?></td><td><?= badge(status_ruangan($r['id_ruangan'])['status']) ?></td>
<td><a href="?tab=ruangan&edit=<?= $r['id_ruangan'] ?>#form">Ubah</a> · <?= $del('hapus_ruangan', $r['id_ruangan'], $r['kode_ruangan']) ?></td></tr><?php endforeach; ?></table></div>
<form class="card" id="form" method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_ruangan"><input type="hidden" name="id_ruangan" value="<?= $cur['id_ruangan'] ?? 0 ?>">
<h3><?= $cur ? 'Ubah Ruangan ' . e($cur['kode_ruangan']) : 'Tambah Ruangan' ?></h3>
<div class="row"><label>Kode<input name="kode_ruangan" required value="<?= e($cur['kode_ruangan'] ?? '') ?>" placeholder="FIK 3.05"></label><label>Kapasitas<input type="number" min="1" name="kapasitas" required value="<?= e($cur['kapasitas'] ?? '') ?>"></label></div>
<label>Nama ruangan<input name="nama_ruangan" required value="<?= e($cur['nama_ruangan'] ?? '') ?>"></label>
<div class="row"><label>Gedung<input name="gedung" required value="<?= e($cur['gedung'] ?? 'Gedung FIK') ?>"></label><label>Lantai<input type="number" name="lantai" required value="<?= e($cur['lantai'] ?? 1) ?>"></label></div>
<div class="ttl">Fasilitas ruangan</div>
<table><tr><th></th><th>Fasilitas</th><th>Jml</th><th>Kondisi</th></tr><?php foreach ($allF as $f): $c = $curF[$f['id_fasilitas']] ?? null; ?>
<tr><td><input type="checkbox" name="fas[<?= $f['id_fasilitas'] ?>]" value="1" <?= $c ? 'checked' : '' ?>></td><td><?= e($f['nama_fasilitas']) ?></td>
<td><input class="sm" type="number" min="1" name="jml[<?= $f['id_fasilitas'] ?>]" value="<?= $c['jumlah'] ?? 1 ?>"></td>
<td><select name="kon[<?= $f['id_fasilitas'] ?>]"><option value="baik">Baik</option><option value="rusak" <?= ($c['kondisi'] ?? '') === 'rusak' ? 'selected' : '' ?>>Rusak</option></select></td></tr><?php endforeach; ?></table>
<button class="btn">Simpan</button><?php if ($cur): ?> <a class="link" href="?tab=ruangan">Batal</a><?php endif; ?></form>

<?php elseif ($tab === 'jadwal'):
  $rows = q('SELECT j.*, r.kode_ruangan, p.nama pj FROM jadwal j JOIN ruangan r ON r.id_ruangan=j.id_ruangan LEFT JOIN pengguna p ON p.id_pengguna=j.id_pj ORDER BY FIELD(hari,"Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"), jam_mulai')->fetchAll();
  $cur = $edit ? q('SELECT * FROM jadwal WHERE id_jadwal=?', [$edit])->fetch() : null;
  $rooms = q('SELECT id_ruangan, kode_ruangan FROM ruangan ORDER BY kode_ruangan')->fetchAll();
  $pjs = q("SELECT id_pengguna, nama FROM pengguna WHERE peran='pj_kelas' ORDER BY nama")->fetchAll(); ?>
<form class="card" id="form" method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_jadwal"><input type="hidden" name="id_jadwal" value="<?= $cur['id_jadwal'] ?? 0 ?>">
<h3><?= $cur ? 'Ubah Jadwal' : 'Tambah Jadwal' ?></h3>
<div class="row"><label>Ruangan<select name="id_ruangan" required><?php foreach ($rooms as $r): ?><option value="<?= $r['id_ruangan'] ?>" <?= ($cur['id_ruangan'] ?? 0) == $r['id_ruangan'] ? 'selected' : '' ?>><?= e($r['kode_ruangan']) ?></option><?php endforeach; ?></select></label>
<label>Hari<select name="hari"><?php foreach (array_slice(HARI, 0, 6) as $h): ?><option <?= ($cur['hari'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?></select></label></div>
<div class="row"><label>Jam mulai<input type="time" name="jam_mulai" required value="<?= e(substr($cur['jam_mulai'] ?? '', 0, 5)) ?>"></label><label>Jam selesai<input type="time" name="jam_selesai" required value="<?= e(substr($cur['jam_selesai'] ?? '', 0, 5)) ?>"></label></div>
<div class="row"><label>Kegiatan<input name="kegiatan" required value="<?= e($cur['kegiatan'] ?? '') ?>" placeholder="Basis Data - SI 2A"></label>
<label>PJ Kelas<select name="id_pj"><option value="0">—</option><?php foreach ($pjs as $p): ?><option value="<?= $p['id_pengguna'] ?>" <?= ($cur['id_pj'] ?? 0) == $p['id_pengguna'] ? 'selected' : '' ?>><?= e($p['nama']) ?></option><?php endforeach; ?></select></label></div>
<button class="btn">Simpan</button><?php if ($cur): ?> <a class="link" href="?tab=jadwal">Batal</a><?php endif; ?></form>
<div class="card"><table><tr><th>Hari / Jam</th><th>Ruangan</th><th>Kegiatan</th><th>Aksi</th></tr>
<?php foreach ($rows as $j): ?><tr><td><?= $j['hari'] ?><br><span class="meta"><?= jam($j['jam_mulai']) ?>–<?= jam($j['jam_selesai']) ?></span></td><td><?= e($j['kode_ruangan']) ?></td><td><?= e($j['kegiatan']) ?><br><span class="meta"><?= e($j['pj'] ?? '-') ?></span></td>
<td><a href="?tab=jadwal&edit=<?= $j['id_jadwal'] ?>#form">Ubah</a> · <?= $del('hapus_jadwal', $j['id_jadwal'], $j['kegiatan']) ?></td></tr><?php endforeach; ?></table></div>

<?php elseif ($tab === 'fasilitas'):
  $rows = q('SELECT f.*, (SELECT COUNT(*) FROM ruangan_fasilitas rf WHERE rf.id_fasilitas=f.id_fasilitas) dipakai FROM fasilitas f ORDER BY nama_fasilitas')->fetchAll();
  $cur = $edit ? q('SELECT * FROM fasilitas WHERE id_fasilitas=?', [$edit])->fetch() : null; ?>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_fasilitas"><input type="hidden" name="id_fasilitas" value="<?= $cur['id_fasilitas'] ?? 0 ?>">
<h3><?= $cur ? 'Ubah Fasilitas' : 'Tambah Fasilitas' ?></h3>
<div class="row"><label>Nama<input name="nama_fasilitas" required value="<?= e($cur['nama_fasilitas'] ?? '') ?>"></label><label>Keterangan<input name="keterangan" value="<?= e($cur['keterangan'] ?? '') ?>"></label></div>
<button class="btn">Simpan</button></form>
<div class="card"><table><tr><th>Fasilitas</th><th>Di ruangan</th><th>Aksi</th></tr><?php foreach ($rows as $f): ?><tr><td><?= e($f['nama_fasilitas']) ?><br><span class="meta"><?= e($f['keterangan']) ?></span></td><td><?= $f['dipakai'] ?></td>
<td><a href="?tab=fasilitas&edit=<?= $f['id_fasilitas'] ?>">Ubah</a> · <?= $del('hapus_fasilitas', $f['id_fasilitas'], $f['nama_fasilitas']) ?></td></tr><?php endforeach; ?></table></div>

<?php else:
  $rows = q('SELECT id_pengguna, nama, email, peran FROM pengguna ORDER BY peran, nama')->fetchAll();
  $cur = $edit ? q('SELECT * FROM pengguna WHERE id_pengguna=?', [$edit])->fetch() : null; ?>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_pengguna"><input type="hidden" name="id_pengguna" value="<?= $cur['id_pengguna'] ?? 0 ?>">
<h3><?= $cur ? 'Ubah Pengguna' : 'Tambah Pengguna' ?></h3>
<div class="row"><label>Nama<input name="nama" required value="<?= e($cur['nama'] ?? '') ?>"></label><label>Email<input type="email" name="email" required value="<?= e($cur['email'] ?? '') ?>"></label></div>
<div class="row"><label>Peran<select name="peran"><?php foreach (['mahasiswa' => 'Mahasiswa', 'pj_kelas' => 'PJ Kelas', 'admin' => 'Admin'] as $k => $l): ?><option value="<?= $k ?>" <?= ($cur['peran'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
<label>Kata sandi<?= $cur ? ' (kosongkan jika tetap)' : '' ?><input type="password" name="password" <?= $cur ? '' : 'required minlength="6"' ?>></label></div>
<button class="btn">Simpan</button></form>
<div class="card"><table><tr><th>Nama</th><th>Peran</th><th>Aksi</th></tr><?php foreach ($rows as $p): ?><tr><td><?= e($p['nama']) ?><br><span class="meta"><?= e($p['email']) ?></span></td><td><?= e(str_replace('_', ' ', $p['peran'])) ?></td>
<td><a href="?tab=pengguna&edit=<?= $p['id_pengguna'] ?>">Ubah</a> · <?= $del('hapus_pengguna', $p['id_pengguna'], $p['nama']) ?></td></tr><?php endforeach; ?></table></div>
<?php endif; ?>
<?php layout('Kelola Data', ob_get_clean(), 'admin');

<?php
// Login (UC-01) — NFR-02
require 'app.php';
$err = '';
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (!preg_match('#^/?[\w./?=&%-]*$#', $next) || str_contains($next, '//')) $next = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $u = q('SELECT * FROM pengguna WHERE email=?', [trim($_POST['email'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']); $_SESSION['user'] = $u;
        flash('Selamat datang, ' . $u['nama'] . '.');
        header('Location: ' . ($next ?: ($u['peran'] === 'admin' ? 'admin.php' : ($u['peran'] === 'pj_kelas' ? 'lapor.php' : 'index.php')))); exit;
    }
    $err = 'Email atau kata sandi salah.';
}
ob_start(); ?>
<form class="card narrow" method="post"><?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
<h3>Masuk ke akun</h3><?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<label>Email kampus<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
<label>Kata sandi<input type="password" name="password" required></label>
<p class="meta">Peran (Mahasiswa / PJ Kelas / Admin) ditentukan otomatis sesuai akun.</p>
<button class="btn">Masuk</button>
<p class="meta center">Atau <a href="index.php">lihat ruangan tanpa login</a></p></form>
<?php layout('Masuk', ob_get_clean(), 'akun');

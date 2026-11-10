<?php
if (user()) redirect('dashboard');
$err = null;
$email = trim($_POST['email'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!batas_coba('login_' . strtolower($email), 5, 600)) {
        $err = 'Terlalu banyak percobaan masuk. Coba lagi 10 menit lagi.';
    } else {
        $u = q('SELECT * FROM anggota WHERE email = ?', [$email])->fetch();
        if (!$u || !password_verify((string) ($_POST['password'] ?? ''), $u['password'])) {
            $err = 'Email atau kata sandi salah.';
        } elseif ($u['status'] === 'pending') {
            $err = 'Keanggotaan Anda masih menunggu verifikasi pengurus.';
        } elseif ($u['status'] === 'nonaktif') {
            $err = 'Akun ini dinonaktifkan. Hubungi pengurus MGMP.';
        } else {
            reset_coba('login_' . strtolower($email));
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'role' => $u['role']];
            redirect('dashboard');
        }
    }
}
$s = setting();
$title = 'Masuk';
require __DIR__ . '/../includes/pub_header.php';
?>
<div class="wadah auth">
  <div class="kartu map" data-tab="Akun anggota">
    <h2 style="font-size:25px">Masuk</h2>
    <p class="muted kecil" style="margin:8px 0 18px">Gunakan email yang terdaftar di MGMP <?= e($s['mapel']) ?> <?= e($s['jenjang']) ?>.</p>
    <?php if ($err): ?><div class="snack error"><?= icon('alert') ?> <?= e($err) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label class="f"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></label>
      <label class="f"><span>Kata sandi</span><input type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn blok">Masuk</button>
    </form>
    <p class="kecil muted tengah" style="margin:16px 0 0">Belum terdaftar? <a href="<?= url('daftar') ?>">Daftar jadi anggota</a>.</p>
  </div>
</div>
<?php require __DIR__ . '/../includes/pub_footer.php'; ?>

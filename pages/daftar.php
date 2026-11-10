<?php
$s = setting();
$err = [];
$in = ['nama' => '', 'nuptk' => '', 'email' => '', 'no_hp' => '', 'sekolah' => '', 'kecamatan' => '', 'jabatan' => 'Guru Mata Pelajaran'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($in as $k => $_) $in[$k] = trim($_POST[$k] ?? '');
    $sandi = (string) ($_POST['password'] ?? '');
    $ulang = (string) ($_POST['password2'] ?? '');

    if (!empty($_POST['situs'])) redirect('daftar');            // honeypot
    if (!batas_coba('daftar', 6, 900)) $err['umum'] = 'Terlalu banyak percobaan pendaftaran. Coba lagi beberapa saat lagi.';
    if (mb_strlen($in['nama']) < 3) $err['nama'] = 'Isi nama lengkap beserta gelar.';
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Alamat email tidak valid.';
    if ($in['nuptk'] !== '' && !preg_match('/^[0-9]{10,20}$/', $in['nuptk'])) $err['nuptk'] = 'NUPTK berisi 10-20 angka.';
    if (!preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $in['no_hp'])) $err['no_hp'] = 'Nomor WhatsApp tidak valid (contoh 081234567890).';
    if (mb_strlen($in['sekolah']) < 3) $err['sekolah'] = 'Isi nama sekolah tempat mengajar.';
    if (strlen($sandi) < 8 || !preg_match('/[A-Za-z]/', $sandi) || !preg_match('/[0-9]/', $sandi)) {
        $err['password'] = 'Kata sandi minimal 8 karakter dan mengandung huruf serta angka.';
    } elseif ($sandi !== $ulang) {
        $err['password2'] = 'Ulangi kata sandi dengan isian yang sama.';
    }
    if (!$err && q('SELECT 1 FROM anggota WHERE email = ?', [$in['email']])->fetchColumn()) {
        $err['email'] = 'Email ini sudah terdaftar. Silakan masuk atau hubungi pengurus.';
    }
    if (!$err) {
        q('INSERT INTO anggota (nuptk, nama, email, password, no_hp, sekolah, kecamatan, jabatan, role, status, created_at)
           VALUES (?,?,?,?,?,?,?,?,"anggota","pending",NOW())',
            [$in['nuptk'] ?: null, $in['nama'], $in['email'], password_hash($sandi, PASSWORD_DEFAULT),
             $in['no_hp'], $in['sekolah'], $in['kecamatan'] ?: null, $in['jabatan']]);
        reset_coba('daftar');
        flash('Pendaftaran terkirim. Pengurus akan memverifikasi keanggotaan Anda, biasanya dalam 1-2 hari kerja.');
        redirect('login');
    }
}
$title = 'Pendaftaran anggota';
require __DIR__ . '/../includes/pub_header.php';
?>
<div class="wadah auth" style="max-width:640px">
  <div class="kartu map" data-tab="Formulir">
    <h2 style="font-size:25px">Daftar jadi anggota MGMP</h2>
    <p class="muted kecil" style="margin:8px 0 18px">Khusus guru <?= e($s['mapel']) ?> jenjang <?= e($s['jenjang']) ?> di <?= e($s['wilayah']) ?>. Setelah diverifikasi pengurus, Anda bisa masuk memakai email dan kata sandi di bawah.</p>
    <?php if (!empty($err['umum'])): ?><div class="snack error"><?= e($err['umum']) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div style="position:absolute;left:-9999px"><label>Situs <input name="situs" tabindex="-1" autocomplete="off"></label></div>
      <div class="grid g2">
        <label class="f"><span>Nama lengkap &amp; gelar</span><input name="nama" value="<?= e($in['nama']) ?>" maxlength="100" required><?php if (isset($err['nama'])): ?><span class="galat"><?= e($err['nama']) ?></span><?php endif; ?></label>
        <label class="f"><span>NUPTK (opsional)</span><input name="nuptk" value="<?= e($in['nuptk']) ?>" maxlength="20" inputmode="numeric"><?php if (isset($err['nuptk'])): ?><span class="galat"><?= e($err['nuptk']) ?></span><?php endif; ?></label>
      </div>
      <div class="grid g2">
        <label class="f"><span>Email (dipakai untuk masuk)</span><input type="email" name="email" value="<?= e($in['email']) ?>" maxlength="120" required><?php if (isset($err['email'])): ?><span class="galat"><?= e($err['email']) ?></span><?php endif; ?></label>
        <label class="f"><span>Nomor WhatsApp</span><input name="no_hp" value="<?= e($in['no_hp']) ?>" maxlength="20" inputmode="tel" placeholder="08xxxxxxxxxx" required><?php if (isset($err['no_hp'])): ?><span class="galat"><?= e($err['no_hp']) ?></span><?php endif; ?></label>
      </div>
      <div class="grid g2">
        <label class="f"><span>Sekolah tempat mengajar</span><input name="sekolah" value="<?= e($in['sekolah']) ?>" maxlength="120" required><?php if (isset($err['sekolah'])): ?><span class="galat"><?= e($err['sekolah']) ?></span><?php endif; ?></label>
        <label class="f"><span>Kecamatan</span><input name="kecamatan" value="<?= e($in['kecamatan']) ?>" maxlength="60"></label>
      </div>
      <label class="f"><span>Jabatan</span>
        <select name="jabatan">
          <?php foreach (['Guru Mata Pelajaran', 'Wakil Kepala Sekolah', 'Kepala Sekolah', 'Guru Penggerak'] as $j): ?>
            <option <?= $in['jabatan'] === $j ? 'selected' : '' ?>><?= e($j) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="grid g2">
        <label class="f"><span>Kata sandi</span><input type="password" name="password" minlength="8" required><small>Minimal 8 karakter, ada huruf dan angka.</small><?php if (isset($err['password'])): ?><span class="galat"><?= e($err['password']) ?></span><?php endif; ?></label>
        <label class="f"><span>Ulangi kata sandi</span><input type="password" name="password2" minlength="8" required><?php if (isset($err['password2'])): ?><span class="galat"><?= e($err['password2']) ?></span><?php endif; ?></label>
      </div>
      <button class="btn blok">Kirim pendaftaran</button>
      <p class="kecil muted tengah" style="margin:14px 0 0">Sudah punya akun? <a href="<?= url('login') ?>">Masuk di sini</a>.</p>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/pub_footer.php'; ?>

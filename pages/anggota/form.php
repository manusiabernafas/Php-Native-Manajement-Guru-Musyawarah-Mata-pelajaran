<?php
require_role('kelola_kegiatan', 'Data anggota dikelola ketua & sekretaris.');
$id = (int) ($_GET['id'] ?? 0);
$a = $id ? q('SELECT * FROM anggota WHERE id = ?', [$id])->fetch() : null;
if ($id && !$a) { flash('Anggota tidak ditemukan.', 'error'); redirect('anggota'); }
// Hanya ketua yang boleh mengatur peran dan mengubah data akun ketua
$bolehPeran = role() === 'ketua';
if ($a && $a['role'] !== 'anggota' && !$bolehPeran && (int) $a['id'] !== (int) user()['id']) {
    flash('Data akun pengurus hanya bisa diubah oleh ketua.', 'error');
    redirect('anggota');
}
$peranLama = $a['role'] ?? 'anggota';
$a = $a ?: ['nama' => '', 'nuptk' => '', 'email' => '', 'no_hp' => '', 'sekolah' => '', 'kecamatan' => '',
            'jabatan' => 'Guru Mata Pelajaran', 'role' => 'anggota', 'status' => 'aktif', 'catatan' => ''];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'nuptk', 'email', 'no_hp', 'sekolah', 'kecamatan', 'jabatan', 'role', 'status', 'catatan'] as $f) {
        $a[$f] = trim($_POST[$f] ?? '');
    }
    $sandi = (string) ($_POST['password'] ?? '');
    if (!$bolehPeran) $a['role'] = $peranLama;

    if (mb_strlen($a['nama']) < 3) $err['nama'] = 'Isi nama lengkap.';
    if (!filter_var($a['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Email tidak valid.';
    if ($a['nuptk'] !== '' && !preg_match('/^[0-9]{10,20}$/', $a['nuptk'])) $err['nuptk'] = 'NUPTK berisi 10-20 angka.';
    if ($a['no_hp'] !== '' && !preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $a['no_hp'])) $err['no_hp'] = 'Nomor WhatsApp tidak valid.';
    if (mb_strlen($a['sekolah']) < 3) $err['sekolah'] = 'Isi asal sekolah.';
    if (!isset(LABEL_ROLE[$a['role']])) $err['role'] = 'Peran tidak valid.';
    if (!isset(STATUS_ANGGOTA[$a['status']])) $err['status'] = 'Status tidak valid.';
    if ($a['role'] !== 'anggota' && $a['status'] !== 'aktif') $err['status'] = 'Pengurus harus berstatus aktif.';
    if (!$id && strlen($sandi) < 8) $err['password'] = 'Kata sandi awal minimal 8 karakter.';
    if ($sandi !== '' && strlen($sandi) < 8) $err['password'] = 'Kata sandi minimal 8 karakter.';
    $dobel = q('SELECT id FROM anggota WHERE email = ? AND id <> ?', [$a['email'], $id])->fetchColumn();
    if ($dobel) $err['email'] = 'Email sudah dipakai anggota lain.';
    // Ketua tidak boleh menurunkan perannya sendiri agar MGMP tidak kehilangan admin
    if ($id === (int) user()['id'] && (role() === 'ketua' && $a['role'] !== 'ketua')) {
        $err['role'] = 'Anda tidak bisa menurunkan peran sendiri. Minta ketua lain melakukannya.';
    }

    if (!$err) {
        $p = [$a['nama'], $a['nuptk'] ?: null, $a['email'], $a['no_hp'] ?: null, $a['sekolah'], $a['kecamatan'] ?: null,
              $a['jabatan'], $a['role'], $a['status'], $a['catatan'] ?: null];
        if ($id) {
            q('UPDATE anggota SET nama=?, nuptk=?, email=?, no_hp=?, sekolah=?, kecamatan=?, jabatan=?, role=?, status=?, catatan=? WHERE id=?',
                array_merge($p, [$id]));
            if ($sandi !== '') q('UPDATE anggota SET password = ? WHERE id = ?', [password_hash($sandi, PASSWORD_DEFAULT), $id]);
            flash('Data anggota diperbarui.');
        } else {
            q('INSERT INTO anggota (nama, nuptk, email, no_hp, sekolah, kecamatan, jabatan, role, status, catatan, password, created_at)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())', array_merge($p, [password_hash($sandi, PASSWORD_DEFAULT)]));
            flash('Anggota ditambahkan. Berikan kata sandi awalnya ke yang bersangkutan.');
        }
        redirect('anggota');
    }
}
$title = $id ? 'Ubah anggota' : 'Tambah anggota';
$actions = '<a class="btn putih" href="' . url('anggota') . '">' . icon('arrow-left', 18) . ' Kembali</a>';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="kartu map" data-tab="Data anggota" style="max-width:780px">
  <?= csrf_field() ?>
  <div class="grid g2">
    <label class="f"><span>Nama lengkap &amp; gelar</span><input name="nama" value="<?= e($a['nama']) ?>" maxlength="100" required><?php if (isset($err['nama'])): ?><span class="galat"><?= e($err['nama']) ?></span><?php endif; ?></label>
    <label class="f"><span>NUPTK</span><input name="nuptk" value="<?= e($a['nuptk']) ?>" maxlength="20"><?php if (isset($err['nuptk'])): ?><span class="galat"><?= e($err['nuptk']) ?></span><?php endif; ?></label>
  </div>
  <div class="grid g2">
    <label class="f"><span>Email</span><input type="email" name="email" value="<?= e($a['email']) ?>" maxlength="120" required><?php if (isset($err['email'])): ?><span class="galat"><?= e($err['email']) ?></span><?php endif; ?></label>
    <label class="f"><span>Nomor WhatsApp</span><input name="no_hp" value="<?= e($a['no_hp']) ?>" maxlength="20"><?php if (isset($err['no_hp'])): ?><span class="galat"><?= e($err['no_hp']) ?></span><?php endif; ?></label>
  </div>
  <div class="grid g2">
    <label class="f"><span>Sekolah</span><input name="sekolah" value="<?= e($a['sekolah']) ?>" maxlength="120" required><?php if (isset($err['sekolah'])): ?><span class="galat"><?= e($err['sekolah']) ?></span><?php endif; ?></label>
    <label class="f"><span>Kecamatan</span><input name="kecamatan" value="<?= e($a['kecamatan']) ?>" maxlength="60"></label>
  </div>
  <div class="grid g3">
    <label class="f"><span>Jabatan di sekolah</span><input name="jabatan" value="<?= e($a['jabatan']) ?>" maxlength="40"></label>
    <label class="f"><span>Peran di MGMP</span><select name="role"<?= $bolehPeran ? '' : ' disabled title="Peran hanya bisa diatur ketua"' ?>><?php foreach (LABEL_ROLE as $k => $v): ?><option value="<?= $k ?>" <?= $a['role'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><?php if (isset($err['role'])): ?><span class="galat"><?= e($err['role']) ?></span><?php endif; ?></label>
    <label class="f"><span>Status</span><select name="status"><?php foreach (STATUS_ANGGOTA as $k => $v): ?><option value="<?= $k ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?></select><?php if (isset($err['status'])): ?><span class="galat"><?= e($err['status']) ?></span><?php endif; ?></label>
  </div>
  <label class="f"><span>Kata sandi <?= $id ? '(kosongkan bila tidak diganti)' : 'awal' ?></span><input type="password" name="password" <?= $id ? '' : 'required' ?>><small>Minimal 8 karakter.</small><?php if (isset($err['password'])): ?><span class="galat"><?= e($err['password']) ?></span><?php endif; ?></label>
  <label class="f"><span>Catatan</span><input name="catatan" value="<?= e($a['catatan']) ?>" maxlength="200" placeholder="Misal: mutasi, pensiun, tugas belajar"></label>
  <button class="btn"><?= icon('check', 18) ?> Simpan</button>
</form>

<?php if ($id && (int) $id !== (int) user()['id']): ?>
  <form method="post" action="<?= url('anggota.aksi') ?>" class="mt" data-yakin="Hapus anggota ini? Riwayat kehadiran dan iurannya ikut terhapus.">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn merah"><?= icon('trash', 18) ?> Hapus anggota</button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

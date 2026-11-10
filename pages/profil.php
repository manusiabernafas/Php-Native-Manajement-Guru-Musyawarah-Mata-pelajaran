<?php
$me = q('SELECT * FROM anggota WHERE id = ?', [user()['id']])->fetch();
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'sandi') {
        $lama = (string) ($_POST['lama'] ?? '');
        $baru = (string) ($_POST['baru'] ?? '');
        if (!password_verify($lama, $me['password'])) $err['lama'] = 'Kata sandi lama salah.';
        elseif (strlen($baru) < 8 || !preg_match('/[A-Za-z]/', $baru) || !preg_match('/[0-9]/', $baru)) $err['baru'] = 'Kata sandi baru minimal 8 karakter dengan huruf dan angka.';
        elseif ($baru !== (string) ($_POST['ulang'] ?? '')) $err['ulang'] = 'Ulangi kata sandi dengan isian yang sama.';
        if (!$err) {
            q('UPDATE anggota SET password = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), $me['id']]);
            flash('Kata sandi berhasil diganti.');
            redirect('profil');
        }
    } else {
        $d = ['nama' => trim($_POST['nama'] ?? ''), 'no_hp' => trim($_POST['no_hp'] ?? ''),
              'sekolah' => trim($_POST['sekolah'] ?? ''), 'kecamatan' => trim($_POST['kecamatan'] ?? ''),
              'jabatan' => trim($_POST['jabatan'] ?? ''), 'nuptk' => trim($_POST['nuptk'] ?? '')];
        if (mb_strlen($d['nama']) < 3) $err['nama'] = 'Isi nama lengkap.';
        if ($d['no_hp'] !== '' && !preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $d['no_hp'])) $err['no_hp'] = 'Nomor WhatsApp tidak valid.';
        if (mb_strlen($d['sekolah']) < 3) $err['sekolah'] = 'Isi asal sekolah.';
        if ($d['nuptk'] !== '' && !preg_match('/^[0-9]{10,20}$/', $d['nuptk'])) $err['nuptk'] = 'NUPTK berisi 10-20 angka.';
        if (!$err) {
            q('UPDATE anggota SET nama=?, no_hp=?, sekolah=?, kecamatan=?, jabatan=?, nuptk=? WHERE id=?',
                [$d['nama'], $d['no_hp'] ?: null, $d['sekolah'], $d['kecamatan'] ?: null, $d['jabatan'] ?: 'Guru Mata Pelajaran', $d['nuptk'] ?: null, $me['id']]);
            $_SESSION['user']['nama'] = $d['nama'];
            flash('Profil diperbarui.');
            redirect('profil');
        }
        $me = array_merge($me, $d);
    }
}
$rekap = rekap_hadir((int) $me['id']);
$sertifikat = q("SELECT p.*, k.judul, k.tanggal, k.jam_pelatihan FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id
                 WHERE p.anggota_id = ? AND p.status = 'hadir' AND k.jam_pelatihan > 0 ORDER BY k.tanggal DESC", [$me['id']])->fetchAll();
$title = 'Profil saya';
$sub = LABEL_ROLE[$me['role']] . ' · ' . $me['sekolah'];
require __DIR__ . '/../includes/header.php';
?>
<div class="grid g4 mb">
  <div class="stat biru"><div class="l"><?= icon('check', 15) ?> Kehadiran</div><div class="v"><?= (int) $rekap['persen'] ?>%</div><div class="s"><?= (int) $rekap['hadir'] ?> dari <?= (int) $rekap['total'] ?> kegiatan</div></div>
  <div class="stat stabilo"><div class="l"><?= icon('award', 15) ?> Jam pelatihan</div><div class="v"><?= (int) $rekap['jp'] ?> JP</div></div>
  <div class="stat"><div class="l"><?= icon('alert', 15) ?> Izin / tanpa kabar</div><div class="v"><?= (int) $rekap['izin'] ?> / <?= (int) $rekap['alfa'] ?></div></div>
  <div class="stat mint"><div class="l"><?= icon('folder', 15) ?> Materi diunggah</div><div class="v"><?= (int) q('SELECT COUNT(*) FROM materi WHERE anggota_id = ?', [$me['id']])->fetchColumn() ?></div></div>
</div>

<div class="grid g21">
  <form method="post" class="kartu map" data-tab="Data diri">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="profil">
    <div class="grid g2">
      <label class="f"><span>Nama lengkap &amp; gelar</span><input name="nama" value="<?= e($me['nama']) ?>" required><?php if (isset($err['nama'])): ?><span class="galat"><?= e($err['nama']) ?></span><?php endif; ?></label>
      <label class="f"><span>NUPTK</span><input name="nuptk" value="<?= e($me['nuptk']) ?>"><?php if (isset($err['nuptk'])): ?><span class="galat"><?= e($err['nuptk']) ?></span><?php endif; ?></label>
    </div>
    <div class="grid g2">
      <label class="f"><span>Email (tidak bisa diubah sendiri)</span><input value="<?= e($me['email']) ?>" disabled></label>
      <label class="f"><span>Nomor WhatsApp</span><input name="no_hp" value="<?= e($me['no_hp']) ?>"><?php if (isset($err['no_hp'])): ?><span class="galat"><?= e($err['no_hp']) ?></span><?php endif; ?></label>
    </div>
    <div class="grid g2">
      <label class="f"><span>Sekolah</span><input name="sekolah" value="<?= e($me['sekolah']) ?>" required><?php if (isset($err['sekolah'])): ?><span class="galat"><?= e($err['sekolah']) ?></span><?php endif; ?></label>
      <label class="f"><span>Kecamatan</span><input name="kecamatan" value="<?= e($me['kecamatan']) ?>"></label>
    </div>
    <label class="f"><span>Jabatan di sekolah</span><input name="jabatan" value="<?= e($me['jabatan']) ?>"></label>
    <button class="btn"><?= icon('check', 18) ?> Simpan profil</button>
  </form>

  <div style="display:grid;gap:16px">
    <form method="post" class="kartu">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="sandi">
      <h3>Ganti kata sandi</h3>
      <label class="f mt"><span>Kata sandi lama</span><input type="password" name="lama" required><?php if (isset($err['lama'])): ?><span class="galat"><?= e($err['lama']) ?></span><?php endif; ?></label>
      <label class="f"><span>Kata sandi baru</span><input type="password" name="baru" required><small>Minimal 8 karakter, ada huruf dan angka.</small><?php if (isset($err['baru'])): ?><span class="galat"><?= e($err['baru']) ?></span><?php endif; ?></label>
      <label class="f"><span>Ulangi kata sandi baru</span><input type="password" name="ulang" required><?php if (isset($err['ulang'])): ?><span class="galat"><?= e($err['ulang']) ?></span><?php endif; ?></label>
      <button class="btn putih"><?= icon('key', 18) ?> Ganti kata sandi</button>
    </form>
    <div class="kartu">
      <h3>Sertifikat saya</h3>
      <div class="lst mt">
        <?php foreach ($sertifikat as $s2): ?>
          <div><span><b class="kecil"><?= e($s2['judul']) ?></b><br><span class="kecil muted"><?= tgl($s2['tanggal']) ?> · <?= (int) $s2['jam_pelatihan'] ?> JP</span></span>
            <a class="btn putih kecil" target="_blank" href="<?= url('kegiatan.cetak', ['id' => $s2['kegiatan_id'], 'jenis' => 'sertifikat', 'peserta' => $s2['id']]) ?>"><?= icon('award', 15) ?> Cetak</a></div>
        <?php endforeach; ?>
        <?php if (!$sertifikat): ?><p class="muted kecil m0">Belum ada sertifikat. Hadiri kegiatan ber-JP untuk mendapatkannya.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

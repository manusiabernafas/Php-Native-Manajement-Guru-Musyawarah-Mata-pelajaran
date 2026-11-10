<?php
require_role(fn() => role() === 'ketua', 'Pengaturan MGMP hanya bisa diubah ketua.');
$s = setting();
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [];
    foreach (['nama', 'mapel', 'jenjang', 'wilayah', 'sekretariat', 'telepon', 'email', 'whatsapp',
              'ketua', 'sekretaris', 'bendahara', 'pembina', 'periode', 'tagline', 'tentang'] as $f) {
        $d[$f] = trim($_POST[$f] ?? '');
    }
    $d['iuran_bulanan'] = (int) ($_POST['iuran_bulanan'] ?? 0);
    $d['menit_presensi'] = (int) ($_POST['menit_presensi'] ?? 120);

    if (mb_strlen($d['nama']) < 5) $err['nama'] = 'Isi nama lengkap MGMP.';
    if (mb_strlen($d['mapel']) < 3) $err['mapel'] = 'Isi mata pelajaran.';
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Email tidak valid.';
    if ($d['whatsapp'] !== '' && !preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $d['whatsapp'])) $err['whatsapp'] = 'Nomor WhatsApp tidak valid.';
    if ($d['iuran_bulanan'] < 0 || $d['iuran_bulanan'] > 10000000) $err['iuran_bulanan'] = 'Nominal iuran tidak wajar.';
    if ($d['menit_presensi'] < 5 || $d['menit_presensi'] > 1440) $err['menit_presensi'] = 'Masa berlaku kode presensi 5 - 1440 menit.';

    if (!$err) {
        q('UPDATE pengaturan SET nama=?, mapel=?, jenjang=?, wilayah=?, sekretariat=?, telepon=?, email=?, whatsapp=?,
           ketua=?, sekretaris=?, bendahara=?, pembina=?, periode=?, tagline=?, tentang=?, iuran_bulanan=?, menit_presensi=? WHERE id=1',
            [$d['nama'], $d['mapel'], $d['jenjang'], $d['wilayah'], $d['sekretariat'], $d['telepon'], $d['email'], $d['whatsapp'],
             $d['ketua'], $d['sekretaris'], $d['bendahara'], $d['pembina'], $d['periode'], $d['tagline'], $d['tentang'],
             $d['iuran_bulanan'], $d['menit_presensi']]);
        flash('Pengaturan MGMP disimpan.');
        redirect('pengaturan');
    }
    $s = array_merge($s, $d);
}
$title = 'Pengaturan MGMP';
$sub = 'Identitas organisasi, pengurus, dan aturan aplikasi';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="grid g2">
  <?= csrf_field() ?>
  <div class="kartu map" data-tab="Identitas">
    <label class="f"><span>Nama lengkap MGMP</span><input name="nama" value="<?= e($s['nama']) ?>" maxlength="120" required><?php if (isset($err['nama'])): ?><span class="galat"><?= e($err['nama']) ?></span><?php endif; ?></label>
    <div class="grid g3">
      <label class="f"><span>Mata pelajaran</span><input name="mapel" value="<?= e($s['mapel']) ?>" maxlength="60" required><?php if (isset($err['mapel'])): ?><span class="galat"><?= e($err['mapel']) ?></span><?php endif; ?></label>
      <label class="f"><span>Jenjang</span><input name="jenjang" value="<?= e($s['jenjang']) ?>" maxlength="20" placeholder="SMP"></label>
      <label class="f"><span>Periode</span><input name="periode" value="<?= e($s['periode']) ?>" maxlength="20" placeholder="2026-2029"></label>
    </div>
    <label class="f"><span>Wilayah</span><input name="wilayah" value="<?= e($s['wilayah']) ?>" maxlength="80"></label>
    <label class="f"><span>Sekretariat</span><input name="sekretariat" value="<?= e($s['sekretariat']) ?>" maxlength="200"></label>
    <div class="grid g3">
      <label class="f"><span>Telepon</span><input name="telepon" value="<?= e($s['telepon']) ?>" maxlength="30"></label>
      <label class="f"><span>Email</span><input name="email" value="<?= e($s['email']) ?>" maxlength="120"><?php if (isset($err['email'])): ?><span class="galat"><?= e($err['email']) ?></span><?php endif; ?></label>
      <label class="f"><span>WhatsApp</span><input name="whatsapp" value="<?= e($s['whatsapp']) ?>" maxlength="20"><?php if (isset($err['whatsapp'])): ?><span class="galat"><?= e($err['whatsapp']) ?></span><?php endif; ?></label>
    </div>
    <label class="f"><span>Tagline</span><input name="tagline" value="<?= e($s['tagline']) ?>" maxlength="160"></label>
    <label class="f"><span>Tentang MGMP</span><textarea name="tentang" maxlength="2000"><?= e($s['tentang']) ?></textarea></label>
  </div>

  <div style="display:grid;gap:16px;align-content:start">
    <div class="kartu map" data-tab="Pengurus">
      <label class="f"><span>Ketua</span><input name="ketua" value="<?= e($s['ketua']) ?>" maxlength="100"></label>
      <label class="f"><span>Sekretaris</span><input name="sekretaris" value="<?= e($s['sekretaris']) ?>" maxlength="100"></label>
      <label class="f"><span>Bendahara</span><input name="bendahara" value="<?= e($s['bendahara']) ?>" maxlength="100"></label>
      <label class="f"><span>Pembina</span><input name="pembina" value="<?= e($s['pembina']) ?>" maxlength="100"></label>
      <p class="kecil muted m0">Nama di sini dipakai pada daftar hadir dan sertifikat. Peran akun diatur di menu Anggota.</p>
    </div>
    <div class="kartu">
      <h3>Aturan aplikasi</h3>
      <label class="f mt"><span>Iuran bulanan (Rp)</span><input type="number" name="iuran_bulanan" min="0" step="1000" value="<?= (int) $s['iuran_bulanan'] ?>"><?php if (isset($err['iuran_bulanan'])): ?><span class="galat"><?= e($err['iuran_bulanan']) ?></span><?php endif; ?></label>
      <label class="f"><span>Masa berlaku kode presensi (menit)</span><input type="number" name="menit_presensi" min="5" max="1440" value="<?= (int) $s['menit_presensi'] ?>"><small>Kode presensi otomatis hangus setelah batas ini.</small><?php if (isset($err['menit_presensi'])): ?><span class="galat"><?= e($err['menit_presensi']) ?></span><?php endif; ?></label>
      <button class="btn blok"><?= icon('check', 18) ?> Simpan pengaturan</button>
    </div>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>

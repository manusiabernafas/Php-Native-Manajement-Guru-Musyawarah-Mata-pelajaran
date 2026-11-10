<?php
// Verifikasi keaslian sertifikat kegiatan lewat kode yang tercetak di dokumen
$kode = strtoupper(trim($_GET['kode'] ?? ''));
$data = null;
$salah = false;
if ($kode !== '') {
    if (!batas_coba('cek_sertifikat', 12, 600)) {
        $salah = true;
        flash('Terlalu banyak percobaan. Coba lagi 10 menit lagi.', 'error');
    } else {
        $data = q("SELECT p.*, a.nama, a.sekolah, k.judul, k.tanggal, k.jam_pelatihan, k.tempat, k.narasumber
                   FROM peserta p JOIN anggota a ON a.id = p.anggota_id JOIN kegiatan k ON k.id = p.kegiatan_id
                   WHERE p.kode_verifikasi = ? AND p.status = 'hadir'", [$kode])->fetch() ?: null;
        $salah = !$data;
    }
}
$s = setting();
$title = 'Cek sertifikat';
require __DIR__ . '/../includes/pub_header.php';
?>
<div class="wadah auth" style="max-width:720px">
  <div class="kartu map" data-tab="Verifikasi">
    <h2 style="font-size:25px">Cek keaslian sertifikat</h2>
    <p class="muted kecil" style="margin:8px 0 18px">Masukkan kode verifikasi yang tercetak di bagian bawah sertifikat kegiatan MGMP.</p>
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
      <input type="hidden" name="page" value="cek">
      <input class="inp grow" name="kode" value="<?= e($kode) ?>" placeholder="SRT-XXXXXX" maxlength="12" style="text-transform:uppercase;min-width:200px" required>
      <button class="btn"><?= icon('search', 18) ?> Cek</button>
    </form>

    <?php if ($data): ?>
      <div class="snack mt"><?= icon('check') ?> Sertifikat <b>asli</b> dan tercatat di basis data MGMP.</div>
      <table class="t">
        <tr><th style="width:170px">Nomor sertifikat</th><td class="tebal"><?= e($data['no_sertifikat']) ?></td></tr>
        <tr><th>Nama</th><td class="tebal"><?= e($data['nama']) ?></td></tr>
        <tr><th>Instansi</th><td><?= e($data['sekolah']) ?></td></tr>
        <tr><th>Kegiatan</th><td><?= e($data['judul']) ?></td></tr>
        <tr><th>Tanggal</th><td><?= tgl_hari($data['tanggal']) ?></td></tr>
        <tr><th>Tempat</th><td><?= e($data['tempat']) ?></td></tr>
        <tr><th>Jam pelatihan</th><td><?= (int) $data['jam_pelatihan'] ?> JP</td></tr>
      </table>
    <?php elseif ($salah): ?>
      <div class="snack error mt"><?= icon('alert') ?> Kode tidak ditemukan. Periksa kembali penulisannya, atau hubungi pengurus MGMP.</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/pub_footer.php'; ?>

<?php
$id = (int) ($_GET['id'] ?? 0);
$jenis = $_GET['jenis'] ?? 'hadir';
$s = setting();
$k = q('SELECT * FROM kegiatan WHERE id = ?', [$id])->fetch();
if (!$k) { flash('Kegiatan tidak ditemukan.', 'error'); redirect('kegiatan'); }

if ($jenis === 'sertifikat') {
    $pid = (int) ($_GET['peserta'] ?? 0);
    $p = q("SELECT p.*, a.nama, a.sekolah FROM peserta p JOIN anggota a ON a.id = p.anggota_id
            WHERE p.id = ? AND p.kegiatan_id = ?", [$pid, $id])->fetch();
    if (!$p) { flash('Data peserta tidak ditemukan.', 'error'); redirect('kegiatan.detail', ['id' => $id]); }
    // Anggota biasa hanya boleh mencetak sertifikatnya sendiri
    if (!kelola_kegiatan() && (int) $p['anggota_id'] !== (int) user()['id']) {
        flash('Anda hanya dapat mencetak sertifikat atas nama sendiri.', 'error');
        redirect('kegiatan.detail', ['id' => $id]);
    }
    if ($p['status'] !== 'hadir') { flash('Sertifikat hanya untuk peserta yang hadir.', 'error'); redirect('kegiatan.detail', ['id' => $id]); }

    // Nomor & kode verifikasi dibuat sekali, lalu dipakai seterusnya
    if (!$p['no_sertifikat'] || !$p['kode_verifikasi']) {
        $urut = (int) q("SELECT COUNT(*) FROM peserta WHERE no_sertifikat IS NOT NULL")->fetchColumn() + 1;
        $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $nomor = sprintf('%03d/SRT/MGMP-%s/%s/%s', $urut, strtoupper(substr($s['mapel'], 0, 3)),
            $romawi[(int) date('n', strtotime($k['tanggal']))], date('Y', strtotime($k['tanggal'])));
        do { $kode = 'SRT-' . kode_acak(6); } while (q('SELECT 1 FROM peserta WHERE kode_verifikasi = ?', [$kode])->fetchColumn());
        q('UPDATE peserta SET no_sertifikat = ?, kode_verifikasi = ? WHERE id = ?', [$nomor, $kode, $pid]);
        $p['no_sertifikat'] = $nomor;
        $p['kode_verifikasi'] = $kode;
    }
} else {
    require_role('kelola_kegiatan', 'Daftar hadir hanya bisa dicetak pengurus.');
    $peserta = q("SELECT p.*, a.nama, a.sekolah, a.no_hp FROM peserta p JOIN anggota a ON a.id = p.anggota_id
                  WHERE p.kegiatan_id = ? ORDER BY a.nama", [$id])->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $jenis === 'sertifikat' ? 'Sertifikat' : 'Daftar hadir' ?> · <?= e($k['judul']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background:#e9e5db">
<div class="no-print" style="max-width:820px;margin:16px auto;display:flex;gap:8px;padding:0 12px">
  <a class="btn putih" href="<?= url('kegiatan.detail', ['id' => $id]) ?>"><?= icon('arrow-left', 18) ?> Kembali</a>
  <button class="btn" onclick="print()"><?= icon('printer', 18) ?> Cetak</button>
</div>

<?php if ($jenis === 'sertifikat'): ?>
  <div class="cetak">
    <div class="sert">
      <div style="font-size:13px;letter-spacing:.2em;text-transform:uppercase"><?= e($s['nama']) ?></div>
      <div class="judul">Sertifikat</div>
      <div style="font-size:13.5px">Nomor: <?= e($p['no_sertifikat']) ?></div>
      <p style="margin:18px 0 0;font-size:14.5px">Diberikan kepada:</p>
      <div class="nama"><?= e($p['nama']) ?></div>
      <div style="font-size:13.5px"><?= e($p['sekolah']) ?></div>
      <p style="margin:18px auto 0;max-width:600px;font-size:14.5px">
        Atas partisipasinya sebagai <b>peserta</b> dalam kegiatan<br>
        <b style="font-size:17px"><?= e($k['judul']) ?></b><br>
        yang diselenggarakan pada <?= tgl_hari($k['tanggal']) ?> di <?= e($k['tempat']) ?>
        <?php if ($k['jam_pelatihan']): ?> dengan pola <b><?= (int) $k['jam_pelatihan'] ?> jam pelatihan</b><?php endif; ?>.
      </p>
      <div class="ttd" style="margin-top:30px">
        <div><?= e($s['wilayah']) ?>, <?= tgl($k['tanggal'], true) ?><br>Ketua MGMP<div class="nm"><?= e($s['ketua']) ?></div></div>
        <?php if ($k['narasumber']): ?><div>Narasumber<div class="nm"><?= e($k['narasumber']) ?></div></div><?php endif; ?>
      </div>
      <div style="margin-top:22px;font-size:12px;color:#444">
        Keaslian sertifikat dapat diperiksa di halaman <b>Cek sertifikat</b> dengan kode <b><?= e($p['kode_verifikasi']) ?></b>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="cetak">
    <div class="kop">
      <h2><?= e($s['nama']) ?></h2>
      <div><?= e($s['sekretariat']) ?> · <?= e($s['telepon']) ?></div>
    </div>
    <h3 class="jd">Daftar Hadir Kegiatan</h3>
    <table class="polos" style="margin-bottom:6px">
      <tr><td style="width:120px">Kegiatan</td><td style="width:10px">:</td><td><b><?= e($k['judul']) ?></b></td></tr>
      <tr><td>Jenis</td><td>:</td><td><?= e(JENIS_KEGIATAN[$k['jenis']]) ?><?= $k['jam_pelatihan'] ? ' (' . (int) $k['jam_pelatihan'] . ' JP)' : '' ?></td></tr>
      <tr><td>Hari / tanggal</td><td>:</td><td><?= tgl_hari($k['tanggal']) ?></td></tr>
      <tr><td>Waktu</td><td>:</td><td><?= jam($k['jam_mulai']) ?> - <?= jam($k['jam_selesai']) ?> WIB</td></tr>
      <tr><td>Tempat</td><td>:</td><td><?= e($k['tempat']) ?></td></tr>
      <?php if ($k['narasumber']): ?><tr><td>Narasumber</td><td>:</td><td><?= e($k['narasumber']) ?></td></tr><?php endif; ?>
    </table>
    <table>
      <tr><th style="width:34px">No</th><th>Nama</th><th>Asal sekolah</th><th style="width:92px">Kehadiran</th><th style="width:150px">Tanda tangan</th></tr>
      <?php foreach ($peserta as $i => $p): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($p['nama']) ?></td>
          <td><?= e($p['sekolah']) ?></td>
          <td><?= $p['status'] === 'hadir' ? 'Hadir' : ($p['status'] === 'izin' ? 'Izin' : ($p['status'] === 'alfa' ? '-' : 'Terdaftar')) ?></td>
          <td style="height:30px"><?= $i % 2 === 0 ? '' : '<div style="text-align:right">' . ($i + 1) . '.</div>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$peserta): ?><tr><td colspan="5" style="text-align:center">Belum ada peserta terdaftar.</td></tr><?php endif; ?>
    </table>
    <p style="font-size:13.5px">Jumlah peserta terdaftar: <b><?= count($peserta) ?> orang</b>, hadir: <b><?= count(array_filter($peserta, fn($x) => $x['status'] === 'hadir')) ?> orang</b>.</p>
    <div class="ttd">
      <div>Sekretaris<div class="nm"><?= e($s['sekretaris']) ?></div></div>
      <div><?= e($s['wilayah']) ?>, <?= tgl($k['tanggal'], true) ?><br>Ketua MGMP<div class="nm"><?= e($s['ketua']) ?></div></div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>

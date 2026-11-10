<?php
require_role('pengurus', 'Laporan hanya untuk pengurus MGMP.');
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-6 months'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if ($sampai < $dari) [$dari, $sampai] = [$sampai, $dari];

$keg = q("SELECT k.*, COUNT(p.id) peserta, SUM(p.status='hadir') hadir, SUM(p.status='izin') izin
          FROM kegiatan k LEFT JOIN peserta p ON p.kegiatan_id = k.id
          WHERE k.tanggal BETWEEN ? AND ? GROUP BY k.id ORDER BY k.tanggal DESC", [$dari, $sampai])->fetchAll();
$ringkas = [
    'kegiatan' => count($keg),
    'selesai' => count(array_filter($keg, fn($k) => $k['status'] === 'selesai')),
    'jp' => array_sum(array_map(fn($k) => $k['status'] === 'selesai' ? (int) $k['jam_pelatihan'] : 0, $keg)),
    'hadir' => array_sum(array_map(fn($k) => (int) $k['hadir'], $keg)),
    'peserta' => array_sum(array_map(fn($k) => (int) $k['peserta'], $keg)),
];
$perJenis = q("SELECT jenis, COUNT(*) n FROM kegiatan WHERE tanggal BETWEEN ? AND ? GROUP BY jenis ORDER BY n DESC", [$dari, $sampai])->fetchAll();
$teraktif = q("SELECT a.nama, a.sekolah, COUNT(*) ikut, SUM(p.status='hadir') hadir,
                      COALESCE(SUM(CASE WHEN p.status='hadir' THEN k.jam_pelatihan ELSE 0 END),0) jp
               FROM peserta p JOIN anggota a ON a.id = p.anggota_id JOIN kegiatan k ON k.id = p.kegiatan_id
               WHERE k.tanggal BETWEEN ? AND ? AND k.status = 'selesai'
               GROUP BY a.id ORDER BY hadir DESC, jp DESC LIMIT 10", [$dari, $sampai])->fetchAll();
$malas = q("SELECT a.nama, a.sekolah, COUNT(*) ikut, SUM(p.status='hadir') hadir
            FROM peserta p JOIN anggota a ON a.id = p.anggota_id JOIN kegiatan k ON k.id = p.kegiatan_id
            WHERE k.tanggal BETWEEN ? AND ? AND k.status = 'selesai' AND a.status = 'aktif'
            GROUP BY a.id HAVING hadir / ikut < 0.6 ORDER BY hadir / ikut LIMIT 10", [$dari, $sampai])->fetchAll();
$perSekolah = q("SELECT a.sekolah, COUNT(DISTINCT a.id) anggota, SUM(p.status='hadir') hadir, COUNT(p.id) ikut
                 FROM anggota a LEFT JOIN peserta p ON p.anggota_id = a.id
                 LEFT JOIN kegiatan k ON k.id = p.kegiatan_id AND k.status = 'selesai' AND k.tanggal BETWEEN ? AND ?
                 WHERE a.status = 'aktif' GROUP BY a.sekolah ORDER BY anggota DESC, hadir DESC LIMIT 20", [$dari, $sampai])->fetchAll();
$kasRing = q("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah ELSE 0 END),0) masuk,
                     COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END),0) keluar
              FROM kas WHERE tanggal BETWEEN ? AND ?", [$dari, $sampai])->fetch();
$materiRing = q("SELECT COUNT(*) n, COALESCE(SUM(unduhan),0) unduh FROM materi WHERE created_at BETWEEN ? AND ?", [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->fetch();

$title = 'Laporan MGMP';
$sub = 'Periode ' . tgl($dari, true) . ' - ' . tgl($sampai, true);
$actions = '<a class="btn putih no-print" href="' . url('laporan.csv', ['jenis' => 'kegiatan', 'dari' => $dari, 'sampai' => $sampai]) . '">' . icon('download', 18) . ' CSV kegiatan</a>'
    . '<a class="btn putih no-print" href="' . url('laporan.csv', ['jenis' => 'kehadiran', 'dari' => $dari, 'sampai' => $sampai]) . '">' . icon('download', 18) . ' CSV kehadiran</a>'
    . '<button class="btn no-print" onclick="print()">' . icon('printer', 18) . ' Cetak</button>';
require __DIR__ . '/../includes/header.php';
?>
<form class="filter no-print" method="get" data-auto>
  <input type="hidden" name="page" value="laporan">
  <label class="kecil tebal">Dari <input class="inp" type="date" name="dari" value="<?= e($dari) ?>"></label>
  <label class="kecil tebal">Sampai <input class="inp" type="date" name="sampai" value="<?= e($sampai) ?>"></label>
</form>

<div class="grid g4 mb">
  <div class="stat biru"><div class="l"><?= icon('calendar', 15) ?> Kegiatan</div><div class="v"><?= $ringkas['kegiatan'] ?></div><div class="s"><?= $ringkas['selesai'] ?> terlaksana · <?= $ringkas['jp'] ?> JP</div></div>
  <div class="stat stabilo"><div class="l"><?= icon('users', 15) ?> Kehadiran</div><div class="v"><?= $ringkas['peserta'] ? round($ringkas['hadir'] / $ringkas['peserta'] * 100) : 0 ?>%</div><div class="s"><?= $ringkas['hadir'] ?> dari <?= $ringkas['peserta'] ?> pendaftaran</div></div>
  <div class="stat mint"><div class="l"><?= icon('wallet', 15) ?> Kas periode ini</div><div class="v"><?= rupiah($kasRing['masuk'] - $kasRing['keluar']) ?></div><div class="s">masuk <?= rupiah($kasRing['masuk']) ?> · keluar <?= rupiah($kasRing['keluar']) ?></div></div>
  <div class="stat"><div class="l"><?= icon('folder', 15) ?> Materi baru</div><div class="v"><?= (int) $materiRing['n'] ?></div><div class="s"><?= (int) $materiRing['unduh'] ?> kali diunduh</div></div>
</div>

<div class="grid g21">
  <div class="kartu map" data-tab="Rekap kegiatan">
    <div class="tabel-w">
      <table class="t">
        <tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis</th><th class="kanan">Peserta</th><th class="kanan">Hadir</th><th class="kanan">%</th></tr>
        <?php foreach ($keg as $k): $pr = $k['peserta'] ? round($k['hadir'] / $k['peserta'] * 100) : 0; ?>
          <tr>
            <td class="nowrap kecil"><?= tgl($k['tanggal']) ?></td>
            <td><b><?= e($k['judul']) ?></b><?php if ($k['jam_pelatihan']): ?> <span class="chip chip-abu"><?= (int) $k['jam_pelatihan'] ?> JP</span><?php endif; ?></td>
            <td class="kecil"><?= e(JENIS_KEGIATAN[$k['jenis']]) ?></td>
            <td class="kanan"><?= (int) $k['peserta'] ?></td>
            <td class="kanan tebal"><?= (int) $k['hadir'] ?></td>
            <td class="kanan"><span class="chip <?= $pr >= 75 ? 'chip-hijau' : ($pr >= 50 ? 'chip-kuning' : 'chip-merah') ?>"><?= $pr ?>%</span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$keg): ?><tr><td colspan="6" class="kosong" style="border:0">Tidak ada kegiatan pada periode ini.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>

  <div style="display:grid;gap:16px">
    <div class="kartu">
      <h3>Anggota teraktif</h3>
      <div class="lst mt">
        <?php foreach ($teraktif as $i => $t): ?>
          <div><span><b class="kecil"><?= ($i + 1) . '. ' . e($t['nama']) ?></b><br><span class="kecil muted"><?= e($t['sekolah']) ?></span></span>
            <span class="kanan nowrap"><b class="kecil"><?= (int) $t['hadir'] ?>/<?= (int) $t['ikut'] ?></b><br><span class="kecil muted"><?= (int) $t['jp'] ?> JP</span></span></div>
        <?php endforeach; ?>
        <?php if (!$teraktif): ?><p class="muted kecil m0">Belum ada data kehadiran.</p><?php endif; ?>
      </div>
    </div>
    <div class="kartu">
      <h3>Perlu dihubungi</h3>
      <p class="kecil muted m0">Kehadiran di bawah 60% pada periode ini.</p>
      <div class="lst mt">
        <?php foreach ($malas as $m): ?>
          <div><span><b class="kecil"><?= e($m['nama']) ?></b><br><span class="kecil muted"><?= e($m['sekolah']) ?></span></span>
            <span class="chip chip-merah"><?= (int) $m['hadir'] ?>/<?= (int) $m['ikut'] ?></span></div>
        <?php endforeach; ?>
        <?php if (!$malas): ?><p class="muted kecil m0">Semua anggota cukup aktif. Bagus!</p><?php endif; ?>
      </div>
    </div>
    <div class="kartu">
      <h3>Komposisi jenis kegiatan</h3>
      <div class="lst mt">
        <?php foreach ($perJenis as $j): ?>
          <div><span class="kecil"><?= e(JENIS_KEGIATAN[$j['jenis']] ?? $j['jenis']) ?></span><b class="kecil"><?= (int) $j['n'] ?> kegiatan</b></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="kartu mt">
  <h3>Partisipasi per sekolah</h3>
  <div class="tabel-w mt">
    <table class="t">
      <tr><th>Sekolah</th><th class="kanan">Anggota</th><th class="kanan">Kehadiran</th><th style="width:160px">Tingkat kehadiran</th></tr>
      <?php foreach ($perSekolah as $s2): $pr = $s2['ikut'] ? round($s2['hadir'] / $s2['ikut'] * 100) : 0; ?>
        <tr>
          <td class="tebal"><?= e($s2['sekolah']) ?></td>
          <td class="kanan"><?= (int) $s2['anggota'] ?></td>
          <td class="kanan"><?= (int) $s2['hadir'] ?>/<?= (int) $s2['ikut'] ?></td>
          <td><div class="bar"><i class="<?= $pr >= 75 ? 'hijau' : ($pr >= 50 ? 'kuning' : 'merah') ?>" style="width:<?= $pr ?>%"></i></div></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

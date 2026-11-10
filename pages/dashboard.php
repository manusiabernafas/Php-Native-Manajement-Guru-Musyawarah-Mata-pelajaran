<?php
$me = user();
$s = setting();
$hariIni = date('Y-m-d');

$akanDatang = q("SELECT k.*, p.status status_saya, (SELECT COUNT(*) FROM peserta x WHERE x.kegiatan_id = k.id) peserta
                 FROM kegiatan k LEFT JOIN peserta p ON p.kegiatan_id = k.id AND p.anggota_id = ?
                 WHERE k.tanggal >= ? AND k.status IN ('dibuka','rencana') ORDER BY k.tanggal LIMIT 4", [$me['id'], $hariIni])->fetchAll();
$presensiBuka = q("SELECT k.*, p.status status_saya FROM kegiatan k LEFT JOIN peserta p ON p.kegiatan_id = k.id AND p.anggota_id = ?
                   WHERE k.kode_presensi IS NOT NULL AND k.presensi_tutup >= NOW() ORDER BY k.tanggal LIMIT 3", [$me['id']])->fetchAll();
$rekap = rekap_hadir((int) $me['id']);
$iuranBelum = q("SELECT COUNT(*) n, COALESCE(SUM(jumlah),0) t FROM iuran WHERE anggota_id = ? AND status = 'belum'", [$me['id']])->fetch();
$kabar = q("SELECT * FROM pengumuman ORDER BY penting DESC, created_at DESC LIMIT 4")->fetchAll();
$materiBaru = q("SELECT m.*, a.nama penyusun FROM materi m LEFT JOIN anggota a ON a.id = m.anggota_id
                 WHERE m.status = 'disetujui' ORDER BY m.created_at DESC LIMIT 5")->fetchAll();

if (pengurus()) {
    $stat = [
        'anggota' => (int) q("SELECT COUNT(*) FROM anggota WHERE status = 'aktif'")->fetchColumn(),
        'pending' => (int) q("SELECT COUNT(*) FROM anggota WHERE status = 'pending'")->fetchColumn(),
        'kegiatan_bulan' => (int) q("SELECT COUNT(*) FROM kegiatan WHERE DATE_FORMAT(tanggal,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn(),
        'moderasi' => (int) q("SELECT COUNT(*) FROM materi WHERE status = 'menunggu'")->fetchColumn(),
        'saldo' => saldo_kas(),
        'tunggakan' => (int) q("SELECT COALESCE(SUM(jumlah),0) FROM iuran WHERE status = 'belum'")->fetchColumn(),
    ];
    $hadirRata = q("SELECT ROUND(AVG(p.status = 'hadir') * 100) FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id WHERE k.status = 'selesai'")->fetchColumn();
    $perKegiatan = q("SELECT k.judul, k.tanggal, SUM(p.status='hadir') hadir, COUNT(*) total FROM kegiatan k
                      JOIN peserta p ON p.kegiatan_id = k.id WHERE k.status = 'selesai'
                      GROUP BY k.id ORDER BY k.tanggal DESC LIMIT 8")->fetchAll();
    $perKegiatan = array_reverse($perKegiatan);
}
$title = 'Halo, ' . explode(',', explode(' ', $me['nama'])[0])[0] . '.';
$sub = tgl_hari($hariIni) . ' · ' . $s['nama'];
require __DIR__ . '/../includes/header.php';
?>
<?php if ($presensiBuka): ?>
  <?php foreach ($presensiBuka as $k): ?>
    <div class="snack info">
      <?= icon('clock') ?>
      <span class="grow"><b>Presensi dibuka:</b> <?= e($k['judul']) ?> — ditutup <?= date('H:i', strtotime($k['presensi_tutup'])) ?> WIB.</span>
      <a class="btn kecil" href="<?= url('kegiatan.detail', ['id' => $k['id']]) ?>">
        <?= ($k['status_saya'] ?? '') === 'hadir' ? 'Sudah absen' : 'Isi presensi' ?>
      </a>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (pengurus()): ?>
  <div class="grid g4 mb">
    <div class="stat biru"><div class="l"><?= icon('users', 15) ?> Anggota aktif</div><div class="v"><?= $stat['anggota'] ?></div><div class="s"><?= $stat['pending'] ?> menunggu verifikasi</div></div>
    <div class="stat stabilo"><div class="l"><?= icon('calendar', 15) ?> Kegiatan bulan ini</div><div class="v"><?= $stat['kegiatan_bulan'] ?></div><div class="s">rata-rata hadir <?= (int) $hadirRata ?>%</div></div>
    <div class="stat"><div class="l"><?= icon('folder', 15) ?> Materi menunggu</div><div class="v"><?= $stat['moderasi'] ?></div><div class="s">perlu dimoderasi pengurus</div></div>
    <div class="stat mint"><div class="l"><?= icon('wallet', 15) ?> Saldo kas</div><div class="v"><?= rupiah($stat['saldo']) ?></div><div class="s">tunggakan iuran <?= rupiah($stat['tunggakan']) ?></div></div>
  </div>
<?php else: ?>
  <div class="grid g4 mb">
    <div class="stat biru"><div class="l"><?= icon('check', 15) ?> Kehadiran saya</div><div class="v"><?= (int) $rekap['persen'] ?>%</div><div class="s"><?= (int) $rekap['hadir'] ?> dari <?= (int) $rekap['total'] ?> kegiatan</div></div>
    <div class="stat stabilo"><div class="l"><?= icon('award', 15) ?> Jam pelatihan</div><div class="v"><?= (int) $rekap['jp'] ?> JP</div><div class="s">terkumpul dari kegiatan MGMP</div></div>
    <div class="stat"><div class="l"><?= icon('calendar', 15) ?> Agenda terdekat</div><div class="v"><?= count($akanDatang) ?></div><div class="s">kegiatan menanti pendaftaran</div></div>
    <div class="stat <?= $iuranBelum['n'] ? 'mint' : '' ?>"><div class="l"><?= icon('wallet', 15) ?> Iuran belum lunas</div><div class="v"><?= (int) $iuranBelum['n'] ?></div><div class="s"><?= rupiah($iuranBelum['t']) ?> perlu dibayar</div></div>
  </div>
<?php endif; ?>

<div class="grid g21">
  <div class="kartu map" data-tab="Agenda">
    <div class="kp"><h2>Kegiatan terdekat</h2><a class="kecil tebal" href="<?= url('kegiatan') ?>">Semua kegiatan</a></div>
    <?php if (!$akanDatang): ?><div class="kosong">Belum ada kegiatan terjadwal.</div><?php endif; ?>
    <div class="lst">
      <?php foreach ($akanDatang as $k): ?>
        <div>
          <span class="grow">
            <a class="tebal" href="<?= url('kegiatan.detail', ['id' => $k['id']]) ?>"><?= e($k['judul']) ?></a>
            <div class="kecil muted"><?= icon('calendar', 13) ?> <?= tgl_hari($k['tanggal']) ?> · <?= jam($k['jam_mulai']) ?> WIB · <?= e($k['tempat']) ?></div>
          </span>
          <span class="kanan nowrap">
            <?= $k['status_saya'] ? '<span class="chip chip-hijau">Terdaftar</span>' : ($k['kuota'] && $k['peserta'] >= $k['kuota'] ? '<span class="chip chip-merah">Penuh</span>' : '<span class="chip chip-abu">' . (int) $k['peserta'] . ' peserta</span>') ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (pengurus() && !empty($perKegiatan)): ?>
      <h3 style="font-size:17px;margin:22px 0 4px">Kehadiran 8 kegiatan terakhir</h3>
      <div class="bars">
        <?php foreach ($perKegiatan as $p): $pr = $p['total'] ? round($p['hadir'] / $p['total'] * 100) : 0; ?>
          <div class="b" title="<?= e($p['judul']) ?>: <?= (int) $p['hadir'] ?>/<?= (int) $p['total'] ?>">
            <i class="<?= $pr < 70 ? 'k' : '' ?>" style="height:<?= $pr ?>%"></i><small><?= $pr ?>%</small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:grid;gap:16px">
    <div class="kartu">
      <div class="kp"><h3>Pengumuman</h3><a class="kecil tebal" href="<?= url('pengumuman') ?>">Semua</a></div>
      <div class="lst">
        <?php foreach ($kabar as $p): ?>
          <div><span><b class="kecil"><?= e($p['judul']) ?></b><br><span class="kecil muted"><?= tgl($p['created_at']) ?></span></span><?= $p['penting'] ? '<span class="chip chip-kuning">Penting</span>' : '' ?></div>
        <?php endforeach; ?>
        <?php if (!$kabar): ?><p class="muted m0">Belum ada pengumuman.</p><?php endif; ?>
      </div>
    </div>
    <div class="kartu">
      <div class="kp"><h3>Materi terbaru</h3><a class="kecil tebal" href="<?= url('materi') ?>">Bank materi</a></div>
      <div class="lst">
        <?php foreach ($materiBaru as $m): ?>
          <div><span><b class="kecil"><?= e($m['judul']) ?></b><br><span class="kecil muted"><?= e(KATEGORI_MATERI[$m['kategori']]) ?> · <?= e(explode(',', (string) $m['penyusun'])[0]) ?></span></span><span class="chip chip-abu"><?= (int) $m['unduhan'] ?>x</span></div>
        <?php endforeach; ?>
        <?php if (!$materiBaru): ?><p class="muted m0">Belum ada materi.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

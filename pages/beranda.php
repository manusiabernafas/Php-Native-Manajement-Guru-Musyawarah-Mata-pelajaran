<?php
$s = setting();
$agenda = q("SELECT k.*, (SELECT COUNT(*) FROM peserta p WHERE p.kegiatan_id = k.id) peserta
             FROM kegiatan k WHERE k.status IN ('dibuka','rencana') AND k.tanggal >= CURDATE()
             ORDER BY k.tanggal LIMIT 6")->fetchAll();
$terakhir = q("SELECT k.*, (SELECT COUNT(*) FROM peserta p WHERE p.kegiatan_id = k.id AND p.status = 'hadir') hadir
               FROM kegiatan k WHERE k.status = 'selesai' ORDER BY k.tanggal DESC LIMIT 4")->fetchAll();
$materi = q("SELECT m.*, a.nama penyusun FROM materi m LEFT JOIN anggota a ON a.id = m.anggota_id
             WHERE m.status = 'disetujui' AND m.publik = 1 ORDER BY m.created_at DESC LIMIT 6")->fetchAll();
$kabar = q("SELECT * FROM pengumuman WHERE publik = 1 ORDER BY penting DESC, created_at DESC LIMIT 3")->fetchAll();
$angka = [
    'anggota' => (int) q("SELECT COUNT(*) FROM anggota WHERE status = 'aktif'")->fetchColumn(),
    'sekolah' => (int) q("SELECT COUNT(DISTINCT sekolah) FROM anggota WHERE status = 'aktif'")->fetchColumn(),
    'kegiatan' => (int) q("SELECT COUNT(*) FROM kegiatan WHERE status = 'selesai'")->fetchColumn(),
    'materi' => (int) q("SELECT COUNT(*) FROM materi WHERE status = 'disetujui'")->fetchColumn(),
];
$title = 'Beranda';
require __DIR__ . '/../includes/pub_header.php';
?>
<section class="hero">
  <div class="wadah">
    <div>
      <span class="chip chip-biru"><?= icon('school', 14) ?> Periode kepengurusan <?= e($s['periode']) ?></span>
      <h1>Satu <span class="st">ruang kerja</span> untuk seluruh guru <?= e($s['mapel']) ?>.</h1>
      <p class="lead"><?= e($s['tagline']) ?>. Daftar kegiatan, presensi dengan kode, bank materi bersama, sampai pencatatan iuran dan kas ada dalam satu aplikasi.</p>
      <div class="aksi">
        <a class="btn" href="<?= url('daftar') ?>"><?= icon('plus', 18) ?> Daftar jadi anggota</a>
        <a class="btn putih" href="#agenda"><?= icon('calendar', 18) ?> Lihat agenda</a>
      </div>
    </div>
    <div class="angka">
      <div><b><?= $angka['anggota'] ?></b><span>guru anggota aktif</span></div>
      <div><b><?= $angka['sekolah'] ?></b><span>sekolah tergabung</span></div>
      <div><b><?= $angka['kegiatan'] ?></b><span>kegiatan terlaksana</span></div>
      <div><b><?= $angka['materi'] ?></b><span>materi dibagikan</span></div>
    </div>
  </div>
</section>

<?php if ($kabar): ?>
<section class="pub-sec" style="padding-top:10px">
  <div class="wadah">
    <div class="grid g3">
      <?php foreach ($kabar as $p): ?>
        <div class="kartu">
          <?php if ($p['penting']): ?><span class="chip chip-kuning"><?= icon('alert', 14) ?> Penting</span><?php endif; ?>
          <h3 style="font-size:17px;margin:8px 0 6px"><?= e($p['judul']) ?></h3>
          <p class="kecil muted m0"><?= e(mb_strimwidth($p['isi'], 0, 160, '…')) ?></p>
          <div class="kecil muted mt"><?= icon('clock', 14) ?> <?= tgl($p['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="pub-sec" id="agenda">
  <div class="wadah">
    <div class="judul"><span class="kick">Agenda</span><h2>Kegiatan terdekat</h2><p>Anggota yang sudah masuk dapat langsung mendaftar lewat menu Kegiatan. Kehadiran dicatat memakai kode presensi yang dibuka panitia di lokasi.</p></div>
    <div class="kg">
      <?php foreach ($agenda as $k): $sisa = $k['kuota'] ? $k['kuota'] - $k['peserta'] : null; ?>
        <div class="k">
          <div class="tg">
            <span class="d"><b><?= date('j', strtotime($k['tanggal'])) ?></b><small><?= substr(BULAN_ID[(int) date('n', strtotime($k['tanggal']))], 0, 3) ?></small></span>
            <span><span class="chip chip-abu"><?= e(JENIS_KEGIATAN[$k['jenis']]) ?></span><div class="kecil muted" style="margin-top:4px"><?= icon('clock', 13) ?> <?= jam($k['jam_mulai']) ?> - <?= jam($k['jam_selesai']) ?> WIB</div></span>
          </div>
          <h3><?= e($k['judul']) ?></h3>
          <div class="kecil muted"><?= icon('pin', 14) ?> <?= e($k['tempat']) ?></div>
          <?php if ($k['narasumber']): ?><div class="kecil muted"><?= icon('user', 14) ?> <?= e($k['narasumber']) ?></div><?php endif; ?>
          <div class="kecil">
            <?php if ($k['jam_pelatihan']): ?><span class="chip chip-hijau"><?= (int) $k['jam_pelatihan'] ?> JP</span> <?php endif; ?>
            <?php if ($k['biaya']): ?><span class="chip chip-kuning">Kontribusi <?= rupiah($k['biaya']) ?></span> <?php endif; ?>
            <?php if ($sisa !== null): ?><span class="chip <?= $sisa > 0 ? 'chip-biru' : 'chip-merah' ?>"><?= $sisa > 0 ? 'Sisa ' . $sisa . ' kursi' : 'Kuota penuh' ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><div class="kosong" style="grid-column:1/-1">Belum ada agenda terjadwal. Pantau terus halaman ini.</div><?php endif; ?>
    </div>
  </div>
</section>

<section class="pub-sec" id="materi">
  <div class="wadah">
    <div class="judul"><span class="kick">Bank materi</span><h2>Perangkat ajar hasil karya anggota</h2><p>Materi yang ditandai publik dapat dilihat siapa saja; berkasnya diunduh setelah masuk sebagai anggota.</p></div>
    <div class="grid g3">
      <?php foreach ($materi as $m): ?>
        <div class="kartu">
          <span class="chip chip-abu"><?= e(KATEGORI_MATERI[$m['kategori']]) ?></span>
          <?php if ($m['kelas']): ?> <span class="chip chip-biru">Kelas <?= e($m['kelas']) ?></span><?php endif; ?>
          <h3 style="font-size:16.5px;margin:9px 0 6px"><?= e($m['judul']) ?></h3>
          <p class="kecil muted m0"><?= e(mb_strimwidth((string) $m['deskripsi'], 0, 110, '…')) ?></p>
          <div class="kecil muted mt"><?= icon('user', 13) ?> <?= e($m['penyusun'] ?? 'Pengurus') ?> · <?= icon('download', 13) ?> <?= (int) $m['unduhan'] ?>x diunduh</div>
        </div>
      <?php endforeach; ?>
      <?php if (!$materi): ?><div class="kosong" style="grid-column:1/-1">Belum ada materi publik.</div><?php endif; ?>
    </div>
  </div>
</section>

<section class="pub-sec">
  <div class="wadah grid g21">
    <div class="kartu map" data-tab="Tentang kami">
      <h2 style="font-size:24px"><?= e($s['nama']) ?></h2>
      <p class="muted" style="margin:10px 0 0"><?= nl2br(e($s['tentang'])) ?></p>
      <div class="grid g2 mt">
        <div><div class="kecil muted">Ketua</div><b><?= e($s['ketua']) ?></b></div>
        <div><div class="kecil muted">Sekretaris</div><b><?= e($s['sekretaris']) ?></b></div>
        <div><div class="kecil muted">Bendahara</div><b><?= e($s['bendahara']) ?></b></div>
        <div><div class="kecil muted">Pembina</div><b><?= e($s['pembina']) ?></b></div>
      </div>
    </div>
    <div style="display:grid;gap:16px">
      <div class="kartu">
        <h3>Sekretariat</h3>
        <div class="kecil mt"><?= icon('pin', 15) ?> <?= e($s['sekretariat']) ?></div>
        <div class="kecil mt"><?= icon('clock', 15) ?> <?= e($s['telepon']) ?> · <?= e($s['email']) ?></div>
        <?php if ($s['whatsapp']): ?><a class="btn hijau kecil mt" target="_blank" rel="noopener" href="<?= e(wa_link($s['whatsapp'], 'Halo pengurus ' . $s['nama'] . ', saya ingin bertanya.')) ?>"><?= icon('wa', 16) ?> Hubungi pengurus</a><?php endif; ?>
      </div>
      <div class="kartu">
        <h3>Kegiatan terakhir</h3>
        <div class="lst mt">
          <?php foreach ($terakhir as $k): ?>
            <div><span><b class="kecil"><?= e($k['judul']) ?></b><br><span class="kecil muted"><?= tgl($k['tanggal']) ?></span></span><span class="chip chip-hijau"><?= (int) $k['hadir'] ?> hadir</span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/pub_footer.php'; ?>

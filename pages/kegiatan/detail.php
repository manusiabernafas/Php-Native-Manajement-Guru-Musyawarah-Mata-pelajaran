<?php
$id = (int) ($_GET['id'] ?? 0);
$k = q('SELECT * FROM kegiatan WHERE id = ?', [$id])->fetch();
if (!$k) { flash('Kegiatan tidak ditemukan.', 'error'); redirect('kegiatan'); }

$me = user();
$saya = q('SELECT * FROM peserta WHERE kegiatan_id = ? AND anggota_id = ?', [$id, $me['id']])->fetch();
$peserta = q("SELECT p.*, a.nama, a.sekolah FROM peserta p JOIN anggota a ON a.id = p.anggota_id
              WHERE p.kegiatan_id = ? ORDER BY p.status = 'hadir' DESC, a.nama", [$id])->fetchAll();
$jml = count($peserta);
$hadir = count(array_filter($peserta, fn($p) => $p['status'] === 'hadir'));
$materi = q("SELECT m.*, a.nama penyusun FROM materi m LEFT JOIN anggota a ON a.id = m.anggota_id
             WHERE m.kegiatan_id = ? AND m.status = 'disetujui' ORDER BY m.created_at DESC", [$id])->fetchAll();
$penuh = $k['kuota'] > 0 && $jml >= $k['kuota'];
$buka = presensi_terbuka($k);

$title = $k['judul'];
$sub = JENIS_KEGIATAN[$k['jenis']] . ' · ' . tgl_hari($k['tanggal']);
$actions = '<a class="btn putih" href="' . url('kegiatan') . '">' . icon('arrow-left', 18) . ' Kembali</a>';
if (kelola_kegiatan()) {
    $actions .= '<a class="btn putih" href="' . url('kegiatan.form', ['id' => $id]) . '">' . icon('edit', 18) . ' Ubah</a>';
    $actions .= '<a class="btn" target="_blank" href="' . url('kegiatan.cetak', ['id' => $id, 'jenis' => 'hadir']) . '">' . icon('printer', 18) . ' Daftar hadir</a>';
}
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid g21">
  <div style="display:grid;gap:16px">
    <div class="kartu map" data-tab="<?= e(STATUS_KEGIATAN[$k['status']][0]) ?>">
      <div class="grid g2">
        <div><div class="kecil muted">Waktu</div><b><?= tgl_hari($k['tanggal']) ?></b><div class="kecil"><?= jam($k['jam_mulai']) ?> - <?= jam($k['jam_selesai']) ?> WIB</div></div>
        <div><div class="kecil muted">Tempat</div><b><?= e($k['tempat']) ?></b></div>
        <div><div class="kecil muted">Narasumber</div><b><?= e($k['narasumber'] ?: '-') ?></b></div>
        <div><div class="kecil muted">Jam pelatihan</div><b><?= $k['jam_pelatihan'] ? (int) $k['jam_pelatihan'] . ' JP' : '-' ?></b><?php if ($k['biaya']): ?><div class="kecil">Kontribusi <?= rupiah($k['biaya']) ?></div><?php endif; ?></div>
      </div>
      <?php if ($k['deskripsi']): ?><p class="muted" style="margin:16px 0 0"><?= nl2br(e($k['deskripsi'])) ?></p><?php endif; ?>
    </div>

    <?php if ($buka && (!$saya || $saya['status'] !== 'hadir') && $k['status'] !== 'batal'): ?>
      <div class="kartu" style="border-color:var(--stabilo);border-width:2px">
        <h3><?= icon('clock', 19) ?> Presensi sedang dibuka</h3>
        <p class="kecil muted" style="margin:6px 0 14px">Masukkan kode presensi yang ditampilkan panitia di lokasi kegiatan. Kode berlaku sampai <?= date('H:i', strtotime($k['presensi_tutup'])) ?> WIB.</p>
        <form method="post" action="<?= url('kegiatan.aksi') ?>" style="display:flex;gap:8px;flex-wrap:wrap">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="presensi">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input class="inp grow" name="kode" placeholder="Kode 6 huruf" maxlength="8" required style="text-transform:uppercase;min-width:160px;letter-spacing:.2em;font-weight:600">
          <button class="btn stabilo"><?= icon('check', 18) ?> Catat kehadiran saya</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="kartu">
      <div class="kp">
        <h3>Daftar peserta (<?= $jml ?><?= $k['kuota'] ? ' / ' . (int) $k['kuota'] : '' ?>)</h3>
        <?php if ($k['status'] === 'selesai'): ?><span class="chip chip-hijau"><?= $hadir ?> hadir · <?= $jml ? round($hadir / $jml * 100) : 0 ?>%</span><?php endif; ?>
      </div>
      <div class="tabel-w">
        <table class="t">
          <tr><th style="width:34px">#</th><th>Nama</th><th>Asal sekolah</th><th>Status</th><?php if (kelola_kegiatan()): ?><th class="kanan">Aksi</th><?php endif; ?></tr>
          <?php foreach ($peserta as $i => $p): ?>
            <tr>
              <td class="muted"><?= $i + 1 ?></td>
              <td class="tebal"><?= e($p['nama']) ?><?php if ($p['catatan']): ?><br><span class="kecil muted"><?= e($p['catatan']) ?></span><?php endif; ?></td>
              <td class="kecil"><?= e($p['sekolah']) ?></td>
              <td><?= chip(STATUS_PESERTA, $p['status']) ?><?php if ($p['hadir_at']): ?><div class="kecil muted"><?= date('H:i', strtotime($p['hadir_at'])) ?> WIB</div><?php endif; ?></td>
              <?php if (kelola_kegiatan()): ?>
                <td class="kanan nowrap">
                  <?php if ($k['status'] === 'selesai'): ?>
                    <form method="post" action="<?= url('kegiatan.aksi') ?>" style="display:inline">
                      <?= csrf_field() ?><input type="hidden" name="aksi" value="status_peserta"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="peserta" value="<?= $p['id'] ?>">
                      <select class="inp kecil" name="status" style="width:auto;min-height:30px;font-size:12.5px" onchange="this.form.submit()">
                        <?php foreach (STATUS_PESERTA as $kk => $vv): ?><option value="<?= $kk ?>" <?= $p['status'] === $kk ? 'selected' : '' ?>><?= e($vv[0]) ?></option><?php endforeach; ?>
                      </select>
                    </form>
                  <?php endif; ?>
                  <?php if ($p['status'] === 'hadir' && $k['jam_pelatihan']): ?>
                    <a class="btn putih kecil" target="_blank" href="<?= url('kegiatan.cetak', ['id' => $id, 'jenis' => 'sertifikat', 'peserta' => $p['id']]) ?>"><?= icon('award', 15) ?></a>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (!$peserta): ?><tr><td colspan="5" class="kosong" style="border:0">Belum ada yang mendaftar.</td></tr><?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <div style="display:grid;gap:16px">
    <div class="kartu">
      <h3>Status saya</h3>
      <?php if ($saya): ?>
        <p style="margin:10px 0"><?= chip(STATUS_PESERTA, $saya['status']) ?> <span class="kecil muted">terdaftar <?= tgl($saya['daftar_at']) ?></span></p>
        <?php if ($saya['status'] === 'hadir'): ?>
          <div class="kecil muted">Hadir dicatat <?= date('H:i', strtotime($saya['hadir_at'])) ?> WIB.</div>
          <?php if ($k['jam_pelatihan']): ?>
            <a class="btn stabilo blok mt" target="_blank" href="<?= url('kegiatan.cetak', ['id' => $id, 'jenis' => 'sertifikat', 'peserta' => $saya['id']]) ?>"><?= icon('award', 18) ?> Cetak sertifikat <?= (int) $k['jam_pelatihan'] ?> JP</a>
          <?php endif; ?>
        <?php elseif ($k['status'] !== 'selesai' && $k['status'] !== 'batal'): ?>
          <form method="post" action="<?= url('kegiatan.aksi') ?>" data-yakin="Batalkan pendaftaran kegiatan ini?">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="batal_daftar"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn merah blok mt"><?= icon('x', 18) ?> Batalkan pendaftaran</button>
          </form>
        <?php endif; ?>
      <?php elseif ($k['status'] === 'dibuka'): ?>
        <?php if ($penuh): ?>
          <div class="snack error mt"><?= icon('alert') ?> Kuota kegiatan ini sudah penuh.</div>
        <?php else: ?>
          <p class="kecil muted" style="margin:8px 0 12px">Daftar dulu supaya panitia bisa menyiapkan konsumsi dan materi.</p>
          <form method="post" action="<?= url('kegiatan.aksi') ?>">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="daftar"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn blok"><?= icon('check', 18) ?> Daftar kegiatan ini</button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted kecil" style="margin:8px 0 0">Anda tidak terdaftar pada kegiatan ini.</p>
      <?php endif; ?>
    </div>

    <?php if (kelola_kegiatan() && $k['status'] !== 'batal'): ?>
      <div class="kartu map" data-tab="Panitia">
        <h3>Kode presensi</h3>
        <?php if ($buka): ?>
          <p class="kecil muted" style="margin:8px 0 10px">Tampilkan kode ini di layar/papan tulis. Berlaku sampai <b><?= date('H:i', strtotime($k['presensi_tutup'])) ?> WIB</b>.</p>
          <div class="tengah" style="background:var(--stabilo);border:1.5px solid var(--tinta);border-radius:8px;padding:14px;font:700 32px 'Playfair Display',serif;letter-spacing:.22em"><?= e($k['kode_presensi']) ?></div>
          <button class="btn putih blok mt" type="button" data-salin="<?= e($k['kode_presensi']) ?>">Salin</button>
          <form method="post" action="<?= url('kegiatan.aksi') ?>" class="mt">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="tutup_presensi"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn merah blok"><?= icon('x', 18) ?> Tutup presensi sekarang</button>
          </form>
        <?php else: ?>
          <p class="kecil muted" style="margin:8px 0 12px">Buka presensi saat kegiatan dimulai. Sistem membuat kode acak yang berlaku <?= (int) setting()['menit_presensi'] ?> menit.</p>
          <form method="post" action="<?= url('kegiatan.aksi') ?>">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="buka_presensi"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn stabilo blok"><?= icon('key', 18) ?> Buka presensi</button>
          </form>
          <?php if ($k['kode_presensi']): ?><p class="kecil muted" style="margin:10px 0 0">Presensi terakhir ditutup <?= tgl($k['presensi_tutup']) ?> <?= date('H:i', strtotime($k['presensi_tutup'])) ?>.</p><?php endif; ?>
        <?php endif; ?>
        <?php if ($k['status'] !== 'selesai'): ?>
          <form method="post" action="<?= url('kegiatan.aksi') ?>" class="mt" data-yakin="Tandai kegiatan selesai? Peserta yang belum presensi akan dicatat tanpa keterangan.">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="selesai"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn hijau blok"><?= icon('check', 18) ?> Tandai kegiatan selesai</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="kartu">
      <div class="kp"><h3>Materi kegiatan</h3><a class="kecil tebal" href="<?= url('materi.form', ['kegiatan' => $id]) ?>">Unggah</a></div>
      <div class="lst">
        <?php foreach ($materi as $m): ?>
          <div><span><b class="kecil"><?= e($m['judul']) ?></b><br><span class="kecil muted"><?= e(explode(',', (string) $m['penyusun'])[0]) ?> · <?= ukuran_file((int) $m['ukuran']) ?></span></span>
            <a class="btn putih kecil" href="<?= url('materi.unduh', ['id' => $m['id']]) ?>"><?= icon('download', 15) ?></a></div>
        <?php endforeach; ?>
        <?php if (!$materi): ?><p class="muted kecil m0">Belum ada materi untuk kegiatan ini.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

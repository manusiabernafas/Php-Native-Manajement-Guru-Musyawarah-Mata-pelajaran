<?php
require_role('kelola_kas', 'Buku kas hanya untuk ketua dan bendahara.');
$dari = $_GET['dari'] ?? date('Y-m-01', strtotime('-5 months'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if ($sampai < $dari) [$dari, $sampai] = [$sampai, $dari];

$list = q('SELECT k.*, a.nama pencatat FROM kas k LEFT JOIN anggota a ON a.id = k.dicatat_oleh
           WHERE k.tanggal BETWEEN ? AND ? ORDER BY k.tanggal DESC, k.id DESC LIMIT 300', [$dari, $sampai])->fetchAll();
$ring = q('SELECT COALESCE(SUM(CASE WHEN jenis = "masuk" THEN jumlah ELSE 0 END),0) masuk,
                  COALESCE(SUM(CASE WHEN jenis = "keluar" THEN jumlah ELSE 0 END),0) keluar
           FROM kas WHERE tanggal BETWEEN ? AND ?', [$dari, $sampai])->fetch();
$awal = (int) q('SELECT COALESCE(SUM(CASE WHEN jenis = "masuk" THEN jumlah ELSE -jumlah END),0) FROM kas WHERE tanggal < ?', [$dari])->fetchColumn();
$perKategori = q('SELECT kategori, jenis, SUM(jumlah) total FROM kas WHERE tanggal BETWEEN ? AND ?
                  GROUP BY kategori, jenis ORDER BY total DESC LIMIT 10', [$dari, $sampai])->fetchAll();

$title = 'Buku kas MGMP';
$sub = 'Periode ' . tgl($dari) . ' - ' . tgl($sampai);
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid g4 mb">
  <div class="stat"><div class="l"><?= icon('wallet', 15) ?> Saldo awal</div><div class="v"><?= rupiah($awal) ?></div><div class="s">sebelum <?= tgl($dari) ?></div></div>
  <div class="stat mint"><div class="l"><?= icon('arrow-right', 15) ?> Kas masuk</div><div class="v"><?= rupiah($ring['masuk']) ?></div></div>
  <div class="stat stabilo"><div class="l"><?= icon('arrow-left', 15) ?> Kas keluar</div><div class="v"><?= rupiah($ring['keluar']) ?></div></div>
  <div class="stat biru"><div class="l"><?= icon('chart', 15) ?> Saldo akhir</div><div class="v"><?= rupiah(saldo_kas()) ?></div><div class="s">seluruh periode</div></div>
</div>

<div class="grid g21">
  <div style="display:grid;gap:16px">
    <form class="filter m0" method="get" data-auto>
      <input type="hidden" name="page" value="kas">
      <label class="kecil tebal">Dari <input class="inp" type="date" name="dari" value="<?= e($dari) ?>"></label>
      <label class="kecil tebal">Sampai <input class="inp" type="date" name="sampai" value="<?= e($sampai) ?>"></label>
      <a class="btn putih kecil" href="<?= url('laporan.csv', ['jenis' => 'kas', 'dari' => $dari, 'sampai' => $sampai]) ?>"><?= icon('download', 16) ?> CSV</a>
    </form>
    <div class="kartu">
      <div class="tabel-w">
        <table class="t">
          <tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="kanan">Masuk</th><th class="kanan">Keluar</th><th></th></tr>
          <?php foreach ($list as $k): ?>
            <tr>
              <td class="nowrap kecil"><?= tgl($k['tanggal']) ?></td>
              <td><span class="chip <?= $k['jenis'] === 'masuk' ? 'chip-hijau' : 'chip-kuning' ?>"><?= e($k['kategori']) ?></span></td>
              <td class="kecil"><?= e($k['keterangan'] ?: '-') ?><?php if ($k['pencatat']): ?><br><span class="muted">oleh <?= e(explode(',', $k['pencatat'])[0]) ?></span><?php endif; ?></td>
              <td class="kanan nowrap tebal"><?= $k['jenis'] === 'masuk' ? rupiah($k['jumlah']) : '-' ?></td>
              <td class="kanan nowrap tebal"><?= $k['jenis'] === 'keluar' ? rupiah($k['jumlah']) : '-' ?></td>
              <td class="kanan">
                <?php if (!$k['iuran_id']): ?>
                  <form method="post" action="<?= url('iuran.aksi') ?>" data-yakin="Hapus transaksi kas ini?">
                    <?= csrf_field() ?><input type="hidden" name="aksi" value="kas_hapus"><input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <button class="btn merah kecil"><?= icon('trash', 15) ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$list): ?><tr><td colspan="6" class="kosong" style="border:0">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <div style="display:grid;gap:16px">
    <form method="post" action="<?= url('iuran.aksi') ?>" class="kartu map" data-tab="Catat transaksi">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="kas_tambah">
      <label class="f"><span>Tanggal</span><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label class="f"><span>Jenis</span><select name="jenis"><option value="keluar">Kas keluar</option><option value="masuk">Kas masuk</option></select></label>
      <label class="f"><span>Kategori</span><input name="kategori" list="kat" maxlength="40" required placeholder="Konsumsi / Honor narasumber">
        <datalist id="kat"><option>Konsumsi</option><option>Honor narasumber</option><option>Sewa tempat</option><option>Administrasi</option><option>Transport</option><option>Kegiatan</option><option>Bantuan</option></datalist>
      </label>
      <label class="f"><span>Jumlah (Rp)</span><input type="number" name="jumlah" min="1" step="1000" required></label>
      <label class="f"><span>Keterangan</span><input name="keterangan" maxlength="200"></label>
      <button class="btn"><?= icon('plus', 18) ?> Catat transaksi</button>
    </form>
    <div class="kartu">
      <h3>Kategori terbesar</h3>
      <div class="lst mt">
        <?php foreach ($perKategori as $c): ?>
          <div><span><b class="kecil"><?= e($c['kategori']) ?></b><br><span class="kecil muted"><?= $c['jenis'] === 'masuk' ? 'Pemasukan' : 'Pengeluaran' ?></span></span><b class="kecil"><?= rupiah($c['total']) ?></b></div>
        <?php endforeach; ?>
        <?php if (!$perKategori): ?><p class="muted kecil m0">Belum ada data.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

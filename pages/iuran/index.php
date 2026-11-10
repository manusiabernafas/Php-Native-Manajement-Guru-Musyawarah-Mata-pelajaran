<?php
$s = setting();
$semua = kelola_kas();
$periode = $_GET['periode'] ?? '';
$f_status = $_GET['status'] ?? '';

$daftarPeriode = q('SELECT DISTINCT periode FROM iuran ORDER BY periode DESC LIMIT 24')->fetchAll(PDO::FETCH_COLUMN);
if ($periode && !in_array($periode, $daftarPeriode, true)) $periode = '';

$w = [];
$p = [];
if (!$semua) { $w[] = 'i.anggota_id = ?'; $p[] = user()['id']; }
if ($periode) { $w[] = 'i.periode = ?'; $p[] = $periode; }
if ($f_status === 'belum' || $f_status === 'lunas') { $w[] = 'i.status = ?'; $p[] = $f_status; }
$where = $w ? implode(' AND ', $w) : '1=1';

$list = q("SELECT i.*, a.nama, a.sekolah, a.no_hp FROM iuran i JOIN anggota a ON a.id = i.anggota_id
           WHERE $where ORDER BY i.periode DESC, a.nama LIMIT 400", $p)->fetchAll();
$ring = q("SELECT COUNT(*) n, COALESCE(SUM(jumlah),0) total,
                  COALESCE(SUM(status = 'lunas'),0) lunas,
                  COALESCE(SUM(CASE WHEN status = 'lunas' THEN jumlah ELSE 0 END),0) terkumpul
           FROM iuran i WHERE $where", $p)->fetch();

$title = $semua ? 'Iuran anggota' : 'Iuran saya';
$sub = 'Iuran ' . rupiah($s['iuran_bulanan']) . ' per bulan · dikelola bendahara';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid g4 mb">
  <div class="stat biru"><div class="l"><?= icon('wallet', 15) ?> Terkumpul</div><div class="v"><?= rupiah($ring['terkumpul']) ?></div><div class="s"><?= (int) $ring['lunas'] ?> dari <?= (int) $ring['n'] ?> tagihan</div></div>
  <div class="stat stabilo"><div class="l"><?= icon('alert', 15) ?> Belum dibayar</div><div class="v"><?= rupiah($ring['total'] - $ring['terkumpul']) ?></div><div class="s"><?= (int) $ring['n'] - (int) $ring['lunas'] ?> tagihan</div></div>
  <div class="stat"><div class="l"><?= icon('chart', 15) ?> Tingkat kepatuhan</div><div class="v"><?= $ring['n'] ? round($ring['lunas'] / $ring['n'] * 100) : 0 ?>%</div><div class="s">dari tagihan yang tampil</div></div>
  <?php if ($semua): ?><div class="stat mint"><div class="l"><?= icon('chart', 15) ?> Saldo kas</div><div class="v"><?= rupiah(saldo_kas()) ?></div><div class="s"><a href="<?= url('kas') ?>">Lihat buku kas</a></div></div><?php endif; ?>
</div>

<?php if ($semua): ?>
  <div class="kartu map mb" data-tab="Bendahara">
    <div class="kp"><h3>Terbitkan tagihan iuran</h3></div>
    <form method="post" action="<?= url('iuran.aksi') ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="terbitkan">
      <label class="f m0"><span>Periode</span><input class="inp" type="month" name="periode" value="<?= date('Y-m') ?>" required></label>
      <label class="f m0"><span>Nominal per anggota</span><input class="inp" type="number" name="jumlah" min="0" step="1000" value="<?= (int) $s['iuran_bulanan'] ?>" required></label>
      <button class="btn"><?= icon('plus', 18) ?> Terbitkan untuk semua anggota aktif</button>
    </form>
    <p class="kecil muted" style="margin:10px 0 0">Tagihan yang sudah ada tidak akan dibuat ulang, jadi aman ditekan dua kali.</p>
  </div>
<?php endif; ?>

<form class="filter" method="get" data-auto>
  <input type="hidden" name="page" value="iuran">
  <select class="inp" name="periode"><option value="">Semua periode</option>
    <?php foreach ($daftarPeriode as $pr): ?><option value="<?= e($pr) ?>" <?= $periode === $pr ? 'selected' : '' ?>><?= e(periode_id($pr)) ?></option><?php endforeach; ?>
  </select>
  <select class="inp" name="status"><option value="">Semua status</option>
    <option value="belum" <?= $f_status === 'belum' ? 'selected' : '' ?>>Belum dibayar</option>
    <option value="lunas" <?= $f_status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
  </select>
</form>

<div class="kartu">
  <div class="tabel-w">
    <table class="t">
      <tr><th>Periode</th><?php if ($semua): ?><th>Anggota</th><?php endif; ?><th class="kanan">Jumlah</th><th>Status</th><th>Dibayar</th><?php if ($semua): ?><th class="kanan">Aksi</th><?php endif; ?></tr>
      <?php foreach ($list as $i): ?>
        <tr>
          <td class="tebal nowrap"><?= e(periode_id($i['periode'])) ?></td>
          <?php if ($semua): ?><td><?= e($i['nama']) ?><br><span class="kecil muted"><?= e($i['sekolah']) ?></span></td><?php endif; ?>
          <td class="kanan nowrap"><?= rupiah($i['jumlah']) ?></td>
          <td><?= $i['status'] === 'lunas' ? '<span class="chip chip-hijau">Lunas</span>' : '<span class="chip chip-kuning">Belum</span>' ?></td>
          <td class="kecil"><?= $i['dibayar_at'] ? tgl($i['dibayar_at']) . ' · ' . e(ucfirst((string) $i['metode'])) : '-' ?></td>
          <?php if ($semua): ?>
            <td class="kanan nowrap">
              <?php if ($i['status'] === 'belum'): ?>
                <form method="post" action="<?= url('iuran.aksi') ?>" style="display:inline-flex;gap:5px">
                  <?= csrf_field() ?><input type="hidden" name="aksi" value="bayar"><input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <select class="inp" name="metode" style="width:auto;min-height:32px;font-size:12.5px"><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select>
                  <button class="btn hijau kecil"><?= icon('check', 15) ?> Lunas</button>
                </form>
                <?php if ($i['no_hp']): ?>
                  <a class="btn putih kecil" target="_blank" rel="noopener" href="<?= e(wa_link($i['no_hp'], 'Yth. ' . $i['nama'] . ', mohon konfirmasi iuran MGMP periode ' . periode_id($i['periode']) . ' sebesar ' . rupiah($i['jumlah']) . '. Terima kasih.')) ?>"><?= icon('wa', 15) ?></a>
                <?php endif; ?>
              <?php else: ?>
                <form method="post" action="<?= url('iuran.aksi') ?>" style="display:inline" data-yakin="Batalkan status lunas? Catatan kas terkait ikut dihapus.">
                  <?= csrf_field() ?><input type="hidden" name="aksi" value="batal_bayar"><input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <button class="btn merah kecil">Batalkan</button>
                </form>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$list): ?><tr><td colspan="6" class="kosong" style="border:0">Belum ada tagihan iuran.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

<?php
$f_status = $_GET['status'] ?? '';
$f_jenis = $_GET['jenis'] ?? '';
$cari = trim($_GET['q'] ?? '');
$hal = max(1, (int) ($_GET['hal'] ?? 1));
$per = 12;

$w = ['1=1'];
$p = [];
if ($f_status && isset(STATUS_KEGIATAN[$f_status])) { $w[] = 'k.status = ?'; $p[] = $f_status; }
if ($f_jenis && isset(JENIS_KEGIATAN[$f_jenis])) { $w[] = 'k.jenis = ?'; $p[] = $f_jenis; }
if ($cari !== '') { $w[] = '(k.judul LIKE ? OR k.tempat LIKE ? OR k.narasumber LIKE ?)'; array_push($p, "%$cari%", "%$cari%", "%$cari%"); }
$where = implode(' AND ', $w);

$total = (int) q("SELECT COUNT(*) FROM kegiatan k WHERE $where", $p)->fetchColumn();
$list = q("SELECT k.*,
                  (SELECT COUNT(*) FROM peserta x WHERE x.kegiatan_id = k.id) peserta,
                  (SELECT COUNT(*) FROM peserta x WHERE x.kegiatan_id = k.id AND x.status = 'hadir') hadir,
                  (SELECT p.status FROM peserta p WHERE p.kegiatan_id = k.id AND p.anggota_id = ?) status_saya
           FROM kegiatan k WHERE $where ORDER BY k.tanggal DESC LIMIT $per OFFSET " . (($hal - 1) * $per),
    array_merge([user()['id']], $p))->fetchAll();

$title = 'Kegiatan & presensi';
$sub = $total . ' kegiatan tercatat';
$actions = kelola_kegiatan() ? '<a class="btn" href="' . url('kegiatan.form') . '">' . icon('plus', 18) . ' Kegiatan baru</a>' : '';
require __DIR__ . '/../../includes/header.php';
?>
<form class="filter" method="get" data-auto>
  <input type="hidden" name="page" value="kegiatan">
  <input class="inp" name="q" value="<?= e($cari) ?>" placeholder="Cari judul / tempat / narasumber" style="min-width:220px">
  <select class="inp" name="status"><option value="">Semua status</option>
    <?php foreach (STATUS_KEGIATAN as $k => $v): ?><option value="<?= $k ?>" <?= $f_status === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?>
  </select>
  <select class="inp" name="jenis"><option value="">Semua jenis</option>
    <?php foreach (JENIS_KEGIATAN as $k => $v): ?><option value="<?= $k ?>" <?= $f_jenis === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
  </select>
  <button class="btn putih kecil"><?= icon('search', 16) ?> Cari</button>
</form>

<div class="kg">
  <?php foreach ($list as $k): $sisa = $k['kuota'] ? $k['kuota'] - $k['peserta'] : null; ?>
    <div class="k">
      <div class="tg">
        <span class="d"><b><?= date('j', strtotime($k['tanggal'])) ?></b><small><?= substr(BULAN_ID[(int) date('n', strtotime($k['tanggal']))], 0, 3) ?></small></span>
        <span class="grow">
          <span class="chip chip-abu"><?= e(JENIS_KEGIATAN[$k['jenis']]) ?></span> <?= chip(STATUS_KEGIATAN, $k['status']) ?>
          <div class="kecil muted" style="margin-top:4px"><?= icon('clock', 13) ?> <?= jam($k['jam_mulai']) ?>-<?= jam($k['jam_selesai']) ?> WIB</div>
        </span>
      </div>
      <h3 style="font-size:17px"><a style="text-decoration:none;color:inherit" href="<?= url('kegiatan.detail', ['id' => $k['id']]) ?>"><?= e($k['judul']) ?></a></h3>
      <div class="kecil muted"><?= icon('pin', 14) ?> <?= e($k['tempat']) ?></div>
      <div class="kecil">
        <?php if ($k['status'] === 'selesai'): ?>
          <span class="chip chip-hijau"><?= (int) $k['hadir'] ?> hadir</span>
        <?php elseif ($sisa !== null): ?>
          <span class="chip <?= $sisa > 0 ? 'chip-biru' : 'chip-merah' ?>"><?= $sisa > 0 ? 'Sisa ' . $sisa : 'Kuota penuh' ?></span>
        <?php else: ?>
          <span class="chip chip-abu"><?= (int) $k['peserta'] ?> peserta</span>
        <?php endif; ?>
        <?php if ($k['jam_pelatihan']): ?><span class="chip chip-abu"><?= (int) $k['jam_pelatihan'] ?> JP</span><?php endif; ?>
        <?php if (presensi_terbuka($k)): ?><span class="chip chip-kuning"><?= icon('clock', 13) ?> Presensi dibuka</span><?php endif; ?>
        <?php if ($k['status_saya']): ?><?= chip(STATUS_PESERTA, $k['status_saya']) ?><?php endif; ?>
      </div>
      <a class="btn putih kecil" href="<?= url('kegiatan.detail', ['id' => $k['id']]) ?>">Lihat detail</a>
    </div>
  <?php endforeach; ?>
  <?php if (!$list): ?><div class="kosong" style="grid-column:1/-1">Tidak ada kegiatan dengan filter ini.</div><?php endif; ?>
</div>

<?php $hlm = (int) ceil($total / $per); if ($hlm > 1): ?>
  <nav class="pagin">
    <?php for ($i = 1; $i <= $hlm; $i++): ?>
      <?php if ($i === $hal): ?><span class="on"><?= $i ?></span>
      <?php else: ?><a href="<?= url('kegiatan', ['q' => $cari, 'status' => $f_status, 'jenis' => $f_jenis, 'hal' => $i]) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

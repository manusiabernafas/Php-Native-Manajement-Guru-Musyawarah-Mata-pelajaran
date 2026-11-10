<?php
$cari = trim($_GET['q'] ?? '');
$f_status = $_GET['status'] ?? '';
$f_sekolah = trim($_GET['sekolah'] ?? '');

$w = [];
$p = [];
if (!pengurus()) { $w[] = "a.status = 'aktif'"; }
elseif ($f_status && isset(STATUS_ANGGOTA[$f_status])) { $w[] = 'a.status = ?'; $p[] = $f_status; }
if ($cari !== '') { $w[] = '(a.nama LIKE ? OR a.sekolah LIKE ? OR a.email LIKE ?)'; array_push($p, "%$cari%", "%$cari%", "%$cari%"); }
if ($f_sekolah !== '') { $w[] = 'a.sekolah = ?'; $p[] = $f_sekolah; }
$where = $w ? implode(' AND ', $w) : '1=1';

$list = q("SELECT a.*,
             (SELECT COUNT(*) FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id WHERE p.anggota_id = a.id AND p.status = 'hadir' AND k.status = 'selesai') hadir,
             (SELECT COUNT(*) FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id WHERE p.anggota_id = a.id AND k.status = 'selesai') ikut,
             (SELECT COUNT(*) FROM iuran i WHERE i.anggota_id = a.id AND i.status = 'belum') tunggak
           FROM anggota a WHERE $where ORDER BY FIELD(a.status,'pending','aktif','nonaktif'), a.nama LIMIT 300", $p)->fetchAll();
$sekolah = q('SELECT DISTINCT sekolah FROM anggota ORDER BY sekolah')->fetchAll(PDO::FETCH_COLUMN);
$pending = (int) q("SELECT COUNT(*) FROM anggota WHERE status = 'pending'")->fetchColumn();

$title = 'Anggota MGMP';
$sub = count($list) . ' anggota ditampilkan dari ' . count($sekolah) . ' sekolah';
$actions = kelola_kegiatan() ? '<a class="btn" href="' . url('anggota.form') . '">' . icon('plus', 18) . ' Tambah anggota</a>' : '';
require __DIR__ . '/../../includes/header.php';
?>
<?php if (kelola_kegiatan() && $pending): ?>
  <div class="snack info"><?= icon('info') ?> <span class="grow"><b><?= $pending ?> pendaftar baru</b> menunggu verifikasi keanggotaan.</span>
    <a class="btn kecil" href="<?= url('anggota', ['status' => 'pending']) ?>">Tinjau</a></div>
<?php endif; ?>

<form class="filter" method="get" data-auto>
  <input type="hidden" name="page" value="anggota">
  <input class="inp" name="q" value="<?= e($cari) ?>" placeholder="Cari nama / sekolah / email" style="min-width:220px">
  <select class="inp" name="sekolah"><option value="">Semua sekolah</option>
    <?php foreach ($sekolah as $sk): ?><option value="<?= e($sk) ?>" <?= $f_sekolah === $sk ? 'selected' : '' ?>><?= e($sk) ?></option><?php endforeach; ?>
  </select>
  <?php if (pengurus()): ?>
    <select class="inp" name="status"><option value="">Semua status</option>
      <?php foreach (STATUS_ANGGOTA as $k => $v): ?><option value="<?= $k ?>" <?= $f_status === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?>
    </select>
  <?php endif; ?>
  <button class="btn putih kecil"><?= icon('search', 16) ?> Cari</button>
</form>

<div class="kartu">
  <div class="tabel-w">
    <table class="t">
      <tr><th>Nama</th><th>Sekolah</th><th>Kontak</th><th class="kanan">Kehadiran</th><?php if (pengurus()): ?><th>Status</th><th class="kanan">Aksi</th><?php endif; ?></tr>
      <?php foreach ($list as $a): ?>
        <tr class="<?= $a['status'] === 'nonaktif' ? 'mati' : '' ?>">
          <td>
            <b><?= e($a['nama']) ?></b>
            <?php if ($a['role'] !== 'anggota'): ?> <span class="chip chip-biru"><?= e(LABEL_ROLE[$a['role']]) ?></span><?php endif; ?>
            <div class="kecil muted"><?= e($a['jabatan']) ?><?= $a['nuptk'] ? ' · NUPTK ' . e($a['nuptk']) : '' ?></div>
          </td>
          <td class="kecil"><?= e($a['sekolah']) ?><?php if ($a['kecamatan']): ?><br><span class="muted">Kec. <?= e($a['kecamatan']) ?></span><?php endif; ?></td>
          <td class="kecil"><?= e($a['email']) ?><br><span class="muted"><?= e($a['no_hp']) ?></span></td>
          <td class="kanan nowrap">
            <?php $pr = $a['ikut'] ? round($a['hadir'] / $a['ikut'] * 100) : 0; ?>
            <b><?= (int) $a['hadir'] ?></b><span class="muted">/<?= (int) $a['ikut'] ?></span>
            <div class="bar" style="width:60px;margin-left:auto;margin-top:4px"><i class="<?= $pr >= 75 ? 'hijau' : 'kuning' ?>" style="width:<?= $pr ?>%"></i></div>
          </td>
          <?php if (pengurus()): ?>
            <td>
              <?= chip(STATUS_ANGGOTA, $a['status']) ?>
              <?php if ($a['tunggak']): ?><div class="kecil" style="color:var(--merah);margin-top:3px"><?= (int) $a['tunggak'] ?> iuran belum lunas</div><?php endif; ?>
            </td>
            <td class="kanan nowrap">
              <?php if (kelola_kegiatan()): ?>
                <?php if ($a['status'] === 'pending'): ?>
                  <form method="post" action="<?= url('anggota.aksi') ?>" style="display:inline"><?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="verifikasi"><input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button class="btn hijau kecil"><?= icon('check', 15) ?> Verifikasi</button></form>
                <?php endif; ?>
                <a class="btn putih kecil" href="<?= url('anggota.form', ['id' => $a['id']]) ?>"><?= icon('edit', 15) ?></a>
              <?php endif; ?>
              <?php if ($a['no_hp']): ?><a class="btn putih kecil" target="_blank" rel="noopener" href="<?= e(wa_link($a['no_hp'], 'Yth. ' . $a['nama'] . ', ')) ?>"><?= icon('wa', 15) ?></a><?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$list): ?><tr><td colspan="6" class="kosong" style="border:0">Tidak ada anggota dengan filter ini.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

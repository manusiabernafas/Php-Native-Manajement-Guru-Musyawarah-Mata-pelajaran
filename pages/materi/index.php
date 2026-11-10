<?php
$f_kat = $_GET['kategori'] ?? '';
$f_st = $_GET['status'] ?? (kelola_kegiatan() ? '' : 'disetujui');
$cari = trim($_GET['q'] ?? '');
$milik = !empty($_GET['saya']);

$w = [];
$p = [];
if (!kelola_kegiatan()) {
    $w[] = '(m.status = "disetujui" OR m.anggota_id = ?)';
    $p[] = user()['id'];
} elseif ($f_st && isset(STATUS_MATERI[$f_st])) {
    $w[] = 'm.status = ?';
    $p[] = $f_st;
}
if ($milik) { $w[] = 'm.anggota_id = ?'; $p[] = user()['id']; }
if ($f_kat && isset(KATEGORI_MATERI[$f_kat])) { $w[] = 'm.kategori = ?'; $p[] = $f_kat; }
if ($cari !== '') { $w[] = '(m.judul LIKE ? OR m.deskripsi LIKE ?)'; array_push($p, "%$cari%", "%$cari%"); }
$where = $w ? implode(' AND ', $w) : '1=1';

$list = q("SELECT m.*, a.nama penyusun, k.judul kegiatan FROM materi m
           LEFT JOIN anggota a ON a.id = m.anggota_id LEFT JOIN kegiatan k ON k.id = m.kegiatan_id
           WHERE $where ORDER BY m.status = 'menunggu' DESC, m.created_at DESC LIMIT 60", $p)->fetchAll();
$menunggu = (int) q("SELECT COUNT(*) FROM materi WHERE status = 'menunggu'")->fetchColumn();

$title = 'Bank materi';
$sub = 'Perangkat ajar, modul, bank soal, dan media pembelajaran milik bersama';
$actions = '<a class="btn" href="' . url('materi.form') . '">' . icon('upload', 18) . ' Unggah materi</a>';
require __DIR__ . '/../../includes/header.php';
?>
<?php if (kelola_kegiatan() && $menunggu): ?>
  <div class="snack info"><?= icon('info') ?> <span class="grow"><b><?= $menunggu ?> materi</b> menunggu moderasi sebelum bisa diunduh anggota.</span>
    <a class="btn kecil" href="<?= url('materi', ['status' => 'menunggu']) ?>">Tinjau sekarang</a></div>
<?php endif; ?>

<form class="filter" method="get" data-auto>
  <input type="hidden" name="page" value="materi">
  <input class="inp" name="q" value="<?= e($cari) ?>" placeholder="Cari judul materi" style="min-width:220px">
  <select class="inp" name="kategori"><option value="">Semua kategori</option>
    <?php foreach (KATEGORI_MATERI as $k => $v): ?><option value="<?= $k ?>" <?= $f_kat === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
  </select>
  <?php if (kelola_kegiatan()): ?>
    <select class="inp" name="status"><option value="">Semua status</option>
      <?php foreach (STATUS_MATERI as $k => $v): ?><option value="<?= $k ?>" <?= $f_st === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?>
    </select>
  <?php endif; ?>
  <label class="cek kecil"><input type="checkbox" name="saya" value="1" <?= $milik ? 'checked' : '' ?> onchange="this.form.submit()"> Unggahan saya</label>
  <button class="btn putih kecil"><?= icon('search', 16) ?> Cari</button>
</form>

<div class="grid g3">
  <?php foreach ($list as $m): $punya = (int) $m['anggota_id'] === (int) user()['id']; ?>
    <div class="kartu" style="display:grid;gap:9px;align-content:start">
      <div class="kp" style="margin:0">
        <span class="chip chip-abu"><?= e(KATEGORI_MATERI[$m['kategori']]) ?></span>
        <?= $m['status'] === 'disetujui' ? ($m['publik'] ? '<span class="chip chip-biru">Publik</span>' : '') : chip(STATUS_MATERI, $m['status']) ?>
      </div>
      <h3 style="font-size:17px"><?= e($m['judul']) ?></h3>
      <?php if ($m['kelas']): ?><div class="kecil muted">Kelas <?= e($m['kelas']) ?></div><?php endif; ?>
      <p class="kecil muted m0"><?= e(mb_strimwidth((string) $m['deskripsi'], 0, 120, '…')) ?></p>
      <?php if ($m['kegiatan']): ?><div class="kecil muted"><?= icon('calendar', 13) ?> <?= e($m['kegiatan']) ?></div><?php endif; ?>
      <?php if ($m['catatan']): ?><div class="kecil" style="color:var(--merah)"><?= icon('alert', 13) ?> <?= e($m['catatan']) ?></div><?php endif; ?>
      <div class="kecil muted"><?= icon('user', 13) ?> <?= e(explode(',', (string) $m['penyusun'])[0] ?: 'Pengurus') ?> · <?= ukuran_file((int) $m['ukuran']) ?> · <?= (int) $m['unduhan'] ?>x diunduh</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php if ($m['status'] === 'disetujui' || $punya || kelola_kegiatan()): ?>
          <a class="btn putih kecil" href="<?= url('materi.unduh', ['id' => $m['id']]) ?>"><?= icon('download', 15) ?> Unduh</a>
        <?php endif; ?>
        <?php if (kelola_kegiatan() && $m['status'] === 'menunggu'): ?>
          <form method="post" action="<?= url('materi.aksi') ?>" style="display:inline"><?= csrf_field() ?>
            <input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button class="btn hijau kecil"><?= icon('check', 15) ?> Setujui</button></form>
          <form method="post" action="<?= url('materi.aksi') ?>" style="display:inline" data-yakin="Tolak materi ini?"><?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tolak"><input type="hidden" name="id" value="<?= $m['id'] ?>">
            <input type="hidden" name="catatan" value="Mohon lengkapi identitas penyusun dan rapikan format berkas.">
            <button class="btn merah kecil">Tolak</button></form>
        <?php endif; ?>
        <?php if ($punya || kelola_kegiatan()): ?>
          <form method="post" action="<?= url('materi.aksi') ?>" style="display:inline" data-yakin="Hapus materi ini?"><?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button class="btn merah kecil"><?= icon('trash', 15) ?></button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$list): ?><div class="kosong" style="grid-column:1/-1">Belum ada materi yang cocok dengan filter ini.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

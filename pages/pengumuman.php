<?php
$bisa = kelola_kegiatan();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('kelola_kegiatan', 'Pengumuman ditulis ketua & sekretaris.');
    $aksi = $_POST['aksi'] ?? 'simpan';
    if ($aksi === 'hapus') {
        q('DELETE FROM pengumuman WHERE id = ?', [(int) $_POST['id']]);
        flash('Pengumuman dihapus.');
    } else {
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        if (mb_strlen($judul) < 5 || mb_strlen($isi) < 10) {
            flash('Judul minimal 5 karakter dan isi minimal 10 karakter.', 'error');
        } else {
            q('INSERT INTO pengumuman (judul, isi, penting, publik, dibuat_oleh, created_at) VALUES (?,?,?,?,?,NOW())',
                [mb_substr($judul, 0, 150), mb_substr($isi, 0, 3000), isset($_POST['penting']) ? 1 : 0,
                 isset($_POST['publik']) ? 1 : 0, user()['id']]);
            flash('Pengumuman diterbitkan.');
        }
    }
    redirect('pengumuman');
}
$list = q('SELECT p.*, a.nama penulis FROM pengumuman p LEFT JOIN anggota a ON a.id = p.dibuat_oleh
           ORDER BY p.penting DESC, p.created_at DESC LIMIT 60')->fetchAll();
$title = 'Pengumuman';
$sub = 'Kabar resmi dari pengurus untuk seluruh anggota';
require __DIR__ . '/../includes/header.php';
?>
<div class="grid <?= $bisa ? 'g21' : '' ?>">
  <div style="display:grid;gap:14px">
    <?php foreach ($list as $p): ?>
      <div class="kartu">
        <div class="kp" style="margin-bottom:8px">
          <h3 style="font-size:18px"><?= e($p['judul']) ?></h3>
          <span>
            <?php if ($p['penting']): ?><span class="chip chip-kuning"><?= icon('alert', 13) ?> Penting</span><?php endif; ?>
            <?php if ($p['publik']): ?><span class="chip chip-biru">Publik</span><?php endif; ?>
          </span>
        </div>
        <p class="m0" style="white-space:pre-line"><?= e($p['isi']) ?></p>
        <div class="kecil muted mt"><?= icon('user', 13) ?> <?= e($p['penulis'] ?? 'Pengurus') ?> · <?= tgl($p['created_at'], true) ?>
          <?php if ($bisa): ?>
            <form method="post" style="display:inline;margin-left:8px" data-yakin="Hapus pengumuman ini?">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn merah kecil"><?= icon('trash', 14) ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$list): ?><div class="kosong">Belum ada pengumuman.</div><?php endif; ?>
  </div>

  <?php if ($bisa): ?>
    <form method="post" class="kartu map" data-tab="Tulis baru" style="align-self:start">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan">
      <label class="f"><span>Judul</span><input name="judul" maxlength="150" required></label>
      <label class="f"><span>Isi pengumuman</span><textarea name="isi" maxlength="3000" required placeholder="Tulis informasi lengkap: apa, kapan, di mana, dan apa yang perlu disiapkan anggota."></textarea></label>
      <label class="cek mb"><input type="checkbox" name="penting" value="1"> Tandai penting (tampil paling atas)</label>
      <label class="cek mb"><input type="checkbox" name="publik" value="1" checked> Tampilkan juga di halaman publik</label>
      <button class="btn"><?= icon('megaphone', 18) ?> Terbitkan</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

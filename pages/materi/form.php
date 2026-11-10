<?php
$kegId = (int) ($_GET['kegiatan'] ?? 0);
$err = [];
$in = ['judul' => '', 'kategori' => 'perangkat', 'kelas' => '', 'deskripsi' => '', 'kegiatan_id' => $kegId, 'publik' => 0];
$kegiatan = q("SELECT id, judul, tanggal FROM kegiatan ORDER BY tanggal DESC LIMIT 40")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['judul', 'kategori', 'kelas', 'deskripsi'] as $f) $in[$f] = trim($_POST[$f] ?? '');
    $in['kegiatan_id'] = (int) ($_POST['kegiatan_id'] ?? 0);
    $in['publik'] = isset($_POST['publik']) ? 1 : 0;

    if (mb_strlen($in['judul']) < 5) $err['judul'] = 'Judul materi minimal 5 karakter.';
    if (!isset(KATEGORI_MATERI[$in['kategori']])) $err['kategori'] = 'Kategori tidak valid.';
    if ($in['kegiatan_id'] && !q('SELECT 1 FROM kegiatan WHERE id = ?', [$in['kegiatan_id']])->fetchColumn()) $err['kegiatan_id'] = 'Kegiatan tidak ditemukan.';
    if (empty($_FILES['berkas']['name'])) $err['berkas'] = 'Pilih berkas materi yang akan diunggah.';

    if (!$err) {
        try {
            [$nama, $asli, $ukuran] = simpan_berkas($_FILES['berkas'], 'materi', EKSTENSI_MATERI, 10);
            // Materi dari pengurus langsung tayang, dari anggota menunggu moderasi
            $status = kelola_kegiatan() ? 'disetujui' : 'menunggu';
            q('INSERT INTO materi (judul, kategori, kelas, deskripsi, file, nama_asli, ukuran, anggota_id, kegiatan_id, status, publik, created_at)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$in['judul'], $in['kategori'], $in['kelas'] ?: null, $in['deskripsi'] ?: null, $nama, $asli, $ukuran,
                 user()['id'], $in['kegiatan_id'] ?: null, $status, $in['publik']]);
            flash($status === 'disetujui' ? 'Materi diunggah dan langsung tayang.' : 'Materi diunggah. Menunggu moderasi pengurus sebelum bisa diunduh anggota lain.');
            redirect('materi');
        } catch (RuntimeException $ex) {
            $err['berkas'] = $ex->getMessage();
        }
    }
}
$title = 'Unggah materi';
$sub = 'Berbagi perangkat ajar untuk dipakai seluruh anggota MGMP';
$actions = '<a class="btn putih" href="' . url('materi') . '">' . icon('arrow-left', 18) . ' Kembali</a>';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" enctype="multipart/form-data" class="kartu map" data-tab="Formulir materi" style="max-width:760px">
  <?= csrf_field() ?>
  <label class="f"><span>Judul materi</span><input name="judul" value="<?= e($in['judul']) ?>" maxlength="150" required><?php if (isset($err['judul'])): ?><span class="galat"><?= e($err['judul']) ?></span><?php endif; ?></label>
  <div class="grid g3">
    <label class="f"><span>Kategori</span><select name="kategori"><?php foreach (KATEGORI_MATERI as $k => $v): ?><option value="<?= $k ?>" <?= $in['kategori'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label class="f"><span>Kelas (opsional)</span><input name="kelas" value="<?= e($in['kelas']) ?>" maxlength="20" placeholder="VII / VIII / IX"></label>
    <label class="f"><span>Terkait kegiatan</span>
      <select name="kegiatan_id"><option value="0">- tidak terkait -</option>
        <?php foreach ($kegiatan as $k): ?><option value="<?= $k['id'] ?>" <?= (int) $in['kegiatan_id'] === (int) $k['id'] ? 'selected' : '' ?>><?= e(mb_strimwidth($k['judul'], 0, 40, '…')) ?> (<?= tgl($k['tanggal']) ?>)</option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <label class="f"><span>Deskripsi singkat</span><textarea name="deskripsi" maxlength="1000" placeholder="Isi materi, untuk siapa, dan bagaimana memakainya."><?= e($in['deskripsi']) ?></textarea></label>
  <label class="f"><span>Berkas</span><input type="file" name="berkas" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png">
    <small>Maksimal 10 MB. Format: <?= implode(', ', EKSTENSI_MATERI) ?>.</small>
    <?php if (isset($err['berkas'])): ?><span class="galat"><?= e($err['berkas']) ?></span><?php endif; ?></label>
  <label class="cek mb"><input type="checkbox" name="publik" value="1" <?= $in['publik'] ? 'checked' : '' ?>> Tampilkan judulnya di halaman publik (berkas tetap hanya untuk anggota)</label>
  <button class="btn"><?= icon('upload', 18) ?> Unggah materi</button>
  <?php if (!kelola_kegiatan()): ?><p class="kecil muted" style="margin:12px 0 0">Materi akan ditinjau pengurus terlebih dahulu.</p><?php endif; ?>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

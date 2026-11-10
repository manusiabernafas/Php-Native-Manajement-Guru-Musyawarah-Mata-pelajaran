<?php
require_role('kelola_kegiatan', 'Hanya ketua & sekretaris yang dapat mengelola kegiatan.');
$id = (int) ($_GET['id'] ?? 0);
$k = $id ? q('SELECT * FROM kegiatan WHERE id = ?', [$id])->fetch() : null;
if ($id && !$k) { flash('Kegiatan tidak ditemukan.', 'error'); redirect('kegiatan'); }
$k = $k ?: ['judul' => '', 'jenis' => 'rutin', 'tanggal' => date('Y-m-d'), 'jam_mulai' => '08:00:00', 'jam_selesai' => '12:00:00',
            'tempat' => '', 'narasumber' => '', 'kuota' => 0, 'biaya' => 0, 'jam_pelatihan' => 0, 'deskripsi' => '', 'status' => 'dibuka'];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['judul', 'jenis', 'tanggal', 'jam_mulai', 'jam_selesai', 'tempat', 'narasumber', 'deskripsi', 'status'] as $f) {
        $k[$f] = trim($_POST[$f] ?? '');
    }
    foreach (['kuota', 'biaya', 'jam_pelatihan'] as $f) $k[$f] = (int) ($_POST[$f] ?? 0);

    if (mb_strlen($k['judul']) < 5) $err['judul'] = 'Judul kegiatan minimal 5 karakter.';
    if (!isset(JENIS_KEGIATAN[$k['jenis']])) $err['jenis'] = 'Jenis kegiatan tidak valid.';
    if (!isset(STATUS_KEGIATAN[$k['status']])) $err['status'] = 'Status tidak valid.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $k['tanggal'])) $err['tanggal'] = 'Tanggal tidak valid.';
    if ($k['jam_selesai'] <= $k['jam_mulai']) $err['jam_selesai'] = 'Jam selesai harus setelah jam mulai.';
    if (mb_strlen($k['tempat']) < 3) $err['tempat'] = 'Isi tempat pelaksanaan.';
    if ($k['kuota'] < 0 || $k['kuota'] > 1000) $err['kuota'] = 'Kuota 0 - 1000 (0 berarti tanpa batas).';
    if ($k['jam_pelatihan'] < 0 || $k['jam_pelatihan'] > 200) $err['jam_pelatihan'] = 'Jam pelatihan 0 - 200.';
    if ($id && $k['kuota'] > 0) {
        $jml = (int) q('SELECT COUNT(*) FROM peserta WHERE kegiatan_id = ?', [$id])->fetchColumn();
        if ($k['kuota'] < $jml) $err['kuota'] = "Sudah ada $jml peserta terdaftar, kuota tidak boleh lebih kecil.";
    }

    if (!$err) {
        $p = [$k['judul'], $k['jenis'], $k['tanggal'], $k['jam_mulai'], $k['jam_selesai'], $k['tempat'],
              $k['narasumber'] ?: null, $k['kuota'], $k['biaya'], $k['jam_pelatihan'], $k['deskripsi'] ?: null, $k['status']];
        if ($id) {
            q('UPDATE kegiatan SET judul=?, jenis=?, tanggal=?, jam_mulai=?, jam_selesai=?, tempat=?, narasumber=?,
               kuota=?, biaya=?, jam_pelatihan=?, deskripsi=?, status=? WHERE id=?', array_merge($p, [$id]));
            flash('Kegiatan diperbarui.');
        } else {
            q('INSERT INTO kegiatan (judul, jenis, tanggal, jam_mulai, jam_selesai, tempat, narasumber, kuota, biaya,
               jam_pelatihan, deskripsi, status, dibuat_oleh, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
                array_merge($p, [user()['id']]));
            $id = (int) db()->lastInsertId();
            flash('Kegiatan dibuat. Bagikan agendanya ke anggota.');
        }
        redirect('kegiatan.detail', ['id' => $id]);
    }
}
$title = $id ? 'Ubah kegiatan' : 'Kegiatan baru';
$actions = '<a class="btn putih" href="' . url($id ? 'kegiatan.detail' : 'kegiatan', $id ? ['id' => $id] : []) . '">' . icon('arrow-left', 18) . ' Kembali</a>';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="kartu map" data-tab="Formulir kegiatan" style="max-width:820px">
  <?= csrf_field() ?>
  <label class="f"><span>Judul kegiatan</span><input name="judul" value="<?= e($k['judul']) ?>" maxlength="150" required><?php if (isset($err['judul'])): ?><span class="galat"><?= e($err['judul']) ?></span><?php endif; ?></label>
  <div class="grid g3">
    <label class="f"><span>Jenis</span><select name="jenis"><?php foreach (JENIS_KEGIATAN as $kk => $vv): ?><option value="<?= $kk ?>" <?= $k['jenis'] === $kk ? 'selected' : '' ?>><?= e($vv) ?></option><?php endforeach; ?></select></label>
    <label class="f"><span>Tanggal</span><input type="date" name="tanggal" value="<?= e(substr($k['tanggal'], 0, 10)) ?>" required><?php if (isset($err['tanggal'])): ?><span class="galat"><?= e($err['tanggal']) ?></span><?php endif; ?></label>
    <label class="f"><span>Status</span><select name="status"><?php foreach (STATUS_KEGIATAN as $kk => $vv): ?><option value="<?= $kk ?>" <?= $k['status'] === $kk ? 'selected' : '' ?>><?= e($vv[0]) ?></option><?php endforeach; ?></select></label>
  </div>
  <div class="grid g2">
    <label class="f"><span>Jam mulai</span><input type="time" name="jam_mulai" value="<?= e(substr($k['jam_mulai'], 0, 5)) ?>" required></label>
    <label class="f"><span>Jam selesai</span><input type="time" name="jam_selesai" value="<?= e(substr($k['jam_selesai'], 0, 5)) ?>" required><?php if (isset($err['jam_selesai'])): ?><span class="galat"><?= e($err['jam_selesai']) ?></span><?php endif; ?></label>
  </div>
  <label class="f"><span>Tempat</span><input name="tempat" value="<?= e($k['tempat']) ?>" maxlength="150" required><?php if (isset($err['tempat'])): ?><span class="galat"><?= e($err['tempat']) ?></span><?php endif; ?></label>
  <label class="f"><span>Narasumber (opsional)</span><input name="narasumber" value="<?= e($k['narasumber']) ?>" maxlength="120"></label>
  <div class="grid g3">
    <label class="f"><span>Kuota peserta</span><input type="number" name="kuota" min="0" max="1000" value="<?= (int) $k['kuota'] ?>"><small>0 = tanpa batas</small><?php if (isset($err['kuota'])): ?><span class="galat"><?= e($err['kuota']) ?></span><?php endif; ?></label>
    <label class="f"><span>Kontribusi peserta (Rp)</span><input type="number" name="biaya" min="0" value="<?= (int) $k['biaya'] ?>"></label>
    <label class="f"><span>Jam pelatihan (JP)</span><input type="number" name="jam_pelatihan" min="0" max="200" value="<?= (int) $k['jam_pelatihan'] ?>"><small>Dicantumkan di sertifikat</small><?php if (isset($err['jam_pelatihan'])): ?><span class="galat"><?= e($err['jam_pelatihan']) ?></span><?php endif; ?></label>
  </div>
  <label class="f"><span>Deskripsi</span><textarea name="deskripsi" maxlength="2000"><?= e($k['deskripsi']) ?></textarea></label>
  <button class="btn"><?= icon('check', 18) ?> Simpan kegiatan</button>
</form>

<?php if ($id): ?>
  <form method="post" action="<?= url('kegiatan.aksi') ?>" class="mt" data-yakin="Hapus kegiatan ini beserta seluruh data pesertanya?">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn merah"><?= icon('trash', 18) ?> Hapus kegiatan</button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

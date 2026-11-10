<?php
// Unduhan selalu lewat PHP supaya hak akses diperiksa dan nama berkas asli dipulihkan
$id = (int) ($_GET['id'] ?? 0);
$m = q('SELECT * FROM materi WHERE id = ?', [$id])->fetch();
if (!$m) { flash('Materi tidak ditemukan.', 'error'); redirect('materi'); }
if ($m['status'] !== 'disetujui' && (int) $m['anggota_id'] !== (int) user()['id'] && !kelola_kegiatan()) {
    flash('Materi ini belum disetujui pengurus.', 'error');
    redirect('materi');
}
$path = __DIR__ . '/../../uploads/materi/' . basename($m['file']);
if (!is_file($path)) { flash('Berkas tidak ditemukan di server.', 'error'); redirect('materi'); }

q('UPDATE materi SET unduhan = unduhan + 1 WHERE id = ?', [$id]);
$nama = preg_replace('/[^A-Za-z0-9._ -]/', '', $m['nama_asli']) ?: ('materi-' . $id . '.' . pathinfo($m['file'], PATHINFO_EXTENSION));
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nama . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;

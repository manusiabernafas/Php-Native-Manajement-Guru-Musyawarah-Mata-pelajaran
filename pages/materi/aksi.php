<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('materi');
$id = (int) ($_POST['id'] ?? 0);
$m = q('SELECT * FROM materi WHERE id = ?', [$id])->fetch();
if (!$m) { flash('Materi tidak ditemukan.', 'error'); redirect('materi'); }
$punya = (int) $m['anggota_id'] === (int) user()['id'];

switch ($_POST['aksi'] ?? '') {
    case 'setujui':
        require_role('kelola_kegiatan', 'Moderasi materi hanya untuk pengurus.');
        q('UPDATE materi SET status = "disetujui", catatan = NULL WHERE id = ?', [$id]);
        flash('Materi disetujui dan sudah bisa diunduh anggota.');
        break;
    case 'tolak':
        require_role('kelola_kegiatan', 'Moderasi materi hanya untuk pengurus.');
        q('UPDATE materi SET status = "ditolak", catatan = ? WHERE id = ?', [mb_substr(trim($_POST['catatan'] ?? 'Belum sesuai ketentuan.'), 0, 200), $id]);
        flash('Materi ditolak beserta catatan perbaikan.');
        break;
    case 'hapus':
        if (!$punya && !kelola_kegiatan()) { flash('Anda hanya bisa menghapus materi sendiri.', 'error'); break; }
        hapus_berkas('materi', $m['file']);
        q('DELETE FROM materi WHERE id = ?', [$id]);
        flash('Materi dihapus.');
        break;
    default:
        flash('Aksi tidak dikenal.', 'error');
}
redirect('materi');

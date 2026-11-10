<?php
require_role('kelola_kegiatan', 'Data anggota dikelola ketua & sekretaris.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('anggota');
$id = (int) ($_POST['id'] ?? 0);
$a = q('SELECT * FROM anggota WHERE id = ?', [$id])->fetch();
if (!$a) { flash('Anggota tidak ditemukan.', 'error'); redirect('anggota'); }

if ($a['role'] !== 'anggota' && role() !== 'ketua' && ($_POST['aksi'] ?? '') !== 'verifikasi') {
    flash('Akun pengurus hanya bisa diubah oleh ketua.', 'error');
    redirect('anggota');
}

switch ($_POST['aksi'] ?? '') {
    case 'verifikasi':
        if ($a['status'] !== 'pending') { flash('Anggota ini sudah diverifikasi.', 'error'); break; }
        q('UPDATE anggota SET status = "aktif" WHERE id = ?', [$id]);
        flash('Keanggotaan ' . $a['nama'] . ' diverifikasi. Sekarang bisa masuk ke aplikasi.');
        break;
    case 'nonaktif':
        if ((int) $id === (int) user()['id']) { flash('Anda tidak bisa menonaktifkan akun sendiri.', 'error'); break; }
        q('UPDATE anggota SET status = "nonaktif" WHERE id = ?', [$id]);
        flash('Anggota dinonaktifkan.');
        break;
    case 'hapus':
        if ((int) $id === (int) user()['id']) { flash('Anda tidak bisa menghapus akun sendiri.', 'error'); break; }
        if ($a['role'] === 'ketua') { flash('Akun ketua tidak bisa dihapus. Ubah perannya lebih dulu.', 'error'); break; }
        q('DELETE FROM anggota WHERE id = ?', [$id]);
        flash('Anggota dihapus.');
        break;
    default:
        flash('Aksi tidak dikenal.', 'error');
}
redirect('anggota');

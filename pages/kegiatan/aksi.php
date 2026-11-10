<?php
// Semua perubahan data kegiatan & peserta lewat POST satu pintu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kegiatan');

$id = (int) ($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$me = user();
$k = q('SELECT * FROM kegiatan WHERE id = ?', [$id])->fetch();
if (!$k) { flash('Kegiatan tidak ditemukan.', 'error'); redirect('kegiatan'); }
$kembali = ['kegiatan.detail', ['id' => $id]];

switch ($aksi) {
    case 'daftar':
        if ($k['status'] !== 'dibuka') { flash('Pendaftaran kegiatan ini sedang tidak dibuka.', 'error'); break; }
        $jml = (int) q('SELECT COUNT(*) FROM peserta WHERE kegiatan_id = ?', [$id])->fetchColumn();
        if ($k['kuota'] > 0 && $jml >= $k['kuota']) { flash('Kuota kegiatan sudah penuh.', 'error'); break; }
        try {
            q('INSERT INTO peserta (kegiatan_id, anggota_id, daftar_at, status) VALUES (?,?,NOW(),"terdaftar")', [$id, $me['id']]);
            flash('Anda terdaftar pada kegiatan ini. Simpan tanggalnya ya.');
        } catch (PDOException $e) {
            flash('Anda sudah terdaftar pada kegiatan ini.', 'error');
        }
        break;

    case 'batal_daftar':
        $p = q('SELECT * FROM peserta WHERE kegiatan_id = ? AND anggota_id = ?', [$id, $me['id']])->fetch();
        if (!$p) { flash('Anda belum terdaftar.', 'error'); break; }
        if ($p['status'] === 'hadir') { flash('Kehadiran sudah tercatat, pendaftaran tidak bisa dibatalkan.', 'error'); break; }
        q('DELETE FROM peserta WHERE id = ?', [$p['id']]);
        flash('Pendaftaran dibatalkan.');
        break;

    case 'presensi':
        $kode = strtoupper(trim($_POST['kode'] ?? ''));
        if (!presensi_terbuka($k)) { flash('Presensi kegiatan ini sudah ditutup.', 'error'); break; }
        if (!batas_coba('presensi_' . $id, 8, 600)) { flash('Terlalu banyak percobaan kode. Tunggu 10 menit.', 'error'); break; }
        if (!hash_equals($k['kode_presensi'], $kode)) { flash('Kode presensi salah. Tanyakan kembali ke panitia.', 'error'); break; }
        reset_coba('presensi_' . $id);
        $p = q('SELECT * FROM peserta WHERE kegiatan_id = ? AND anggota_id = ?', [$id, $me['id']])->fetch();
        if ($p && $p['status'] === 'hadir') { flash('Kehadiran Anda sudah tercatat sebelumnya.', 'info'); break; }
        if ($p) {
            q('UPDATE peserta SET status = "hadir", hadir_at = NOW() WHERE id = ?', [$p['id']]);
        } else {
            q('INSERT INTO peserta (kegiatan_id, anggota_id, daftar_at, hadir_at, status) VALUES (?,?,NOW(),NOW(),"hadir")', [$id, $me['id']]);
        }
        flash('Kehadiran tercatat. Terima kasih sudah hadir!');
        break;

    case 'buka_presensi':
        require_role('kelola_kegiatan');
        $menit = max(5, (int) setting()['menit_presensi']);
        q('UPDATE kegiatan SET kode_presensi = ?, presensi_tutup = DATE_ADD(NOW(), INTERVAL ? MINUTE), status = IF(status = "rencana", "dibuka", status) WHERE id = ?',
            [kode_acak(6), $menit, $id]);
        flash('Presensi dibuka selama ' . $menit . ' menit.');
        break;

    case 'tutup_presensi':
        require_role('kelola_kegiatan');
        q('UPDATE kegiatan SET presensi_tutup = NOW() WHERE id = ?', [$id]);
        flash('Presensi ditutup.');
        break;

    case 'selesai':
        require_role('kelola_kegiatan');
        db()->beginTransaction();
        q('UPDATE peserta SET status = "alfa" WHERE kegiatan_id = ? AND status = "terdaftar"', [$id]);
        q('UPDATE kegiatan SET status = "selesai", presensi_tutup = NOW() WHERE id = ?', [$id]);
        db()->commit();
        flash('Kegiatan ditandai selesai. Peserta yang tidak presensi dicatat tanpa keterangan.');
        break;

    case 'status_peserta':
        require_role('kelola_kegiatan');
        $pid = (int) ($_POST['peserta'] ?? 0);
        $st = $_POST['status'] ?? '';
        if (!isset(STATUS_PESERTA[$st])) { flash('Status peserta tidak valid.', 'error'); break; }
        $p = q('SELECT * FROM peserta WHERE id = ? AND kegiatan_id = ?', [$pid, $id])->fetch();
        if (!$p) { flash('Peserta tidak ditemukan.', 'error'); break; }
        q('UPDATE peserta SET status = ?, hadir_at = ? WHERE id = ?',
            [$st, $st === 'hadir' ? ($p['hadir_at'] ?: date('Y-m-d H:i:s')) : null, $pid]);
        flash('Status peserta diperbarui.');
        break;

    case 'hapus':
        require_role('kelola_kegiatan');
        q('DELETE FROM kegiatan WHERE id = ?', [$id]);
        flash('Kegiatan dihapus beserta data pesertanya.');
        redirect('kegiatan');

    default:
        flash('Aksi tidak dikenal.', 'error');
}
redirect($kembali[0], $kembali[1]);

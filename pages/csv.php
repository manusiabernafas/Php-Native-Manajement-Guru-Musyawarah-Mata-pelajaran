<?php
require_role('pengurus', 'Ekspor data hanya untuk pengurus.');
$jenis = $_GET['jenis'] ?? 'kegiatan';
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-6 months'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if ($sampai < $dari) [$dari, $sampai] = [$sampai, $dari];

/** Sel diawali = + - @ diberi kutip satu agar tidak dieksekusi Excel sebagai rumus. */
function sel($v): string
{
    $s = (string) $v;
    return preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;
}

if ($jenis === 'kehadiran') {
    $judul = ['Tanggal', 'Kegiatan', 'Jenis', 'JP', 'Nama', 'Sekolah', 'Status', 'Jam hadir', 'No sertifikat'];
    $rows = q("SELECT k.tanggal, k.judul, k.jenis, k.jam_pelatihan, a.nama, a.sekolah, p.status, p.hadir_at, p.no_sertifikat
               FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id JOIN anggota a ON a.id = p.anggota_id
               WHERE k.tanggal BETWEEN ? AND ? ORDER BY k.tanggal DESC, a.nama", [$dari, $sampai])->fetchAll();
    $data = array_map(fn($r) => [$r['tanggal'], $r['judul'], JENIS_KEGIATAN[$r['jenis']] ?? $r['jenis'], $r['jam_pelatihan'],
        $r['nama'], $r['sekolah'], STATUS_PESERTA[$r['status']][0] ?? $r['status'], $r['hadir_at'], $r['no_sertifikat']], $rows);
    $nama = 'kehadiran';
} elseif ($jenis === 'kas') {
    require_role('kelola_kas', 'Buku kas hanya untuk ketua dan bendahara.');
    $judul = ['Tanggal', 'Jenis', 'Kategori', 'Jumlah', 'Keterangan', 'Dicatat oleh'];
    $rows = q('SELECT k.*, a.nama pencatat FROM kas k LEFT JOIN anggota a ON a.id = k.dicatat_oleh
               WHERE k.tanggal BETWEEN ? AND ? ORDER BY k.tanggal, k.id', [$dari, $sampai])->fetchAll();
    $data = array_map(fn($r) => [$r['tanggal'], $r['jenis'], $r['kategori'], $r['jumlah'], $r['keterangan'], $r['pencatat']], $rows);
    $nama = 'buku-kas';
} elseif ($jenis === 'iuran') {
    require_role('kelola_kas', 'Data iuran hanya untuk ketua dan bendahara.');
    $judul = ['Periode', 'Nama', 'Sekolah', 'Jumlah', 'Status', 'Dibayar', 'Metode'];
    $rows = q('SELECT i.*, a.nama, a.sekolah FROM iuran i JOIN anggota a ON a.id = i.anggota_id ORDER BY i.periode DESC, a.nama')->fetchAll();
    $data = array_map(fn($r) => [$r['periode'], $r['nama'], $r['sekolah'], $r['jumlah'], $r['status'], $r['dibayar_at'], $r['metode']], $rows);
    $nama = 'iuran';
} else {
    $judul = ['Tanggal', 'Judul', 'Jenis', 'Tempat', 'Narasumber', 'JP', 'Status', 'Peserta', 'Hadir', 'Izin'];
    $rows = q("SELECT k.*, COUNT(p.id) peserta, SUM(p.status='hadir') hadir, SUM(p.status='izin') izin
               FROM kegiatan k LEFT JOIN peserta p ON p.kegiatan_id = k.id
               WHERE k.tanggal BETWEEN ? AND ? GROUP BY k.id ORDER BY k.tanggal DESC", [$dari, $sampai])->fetchAll();
    $data = array_map(fn($r) => [$r['tanggal'], $r['judul'], JENIS_KEGIATAN[$r['jenis']] ?? $r['jenis'], $r['tempat'],
        $r['narasumber'], $r['jam_pelatihan'], $r['status'], $r['peserta'], (int) $r['hadir'], (int) $r['izin']], $rows);
    $nama = 'kegiatan';
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nama . '-' . $dari . '-' . $sampai . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, $judul, ';', '"', '');
foreach ($data as $r) fputcsv($out, array_map('sel', $r), ';', '"', '');
fclose($out);
exit;

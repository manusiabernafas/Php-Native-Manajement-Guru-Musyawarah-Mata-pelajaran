<?php
require __DIR__ . '/includes/bootstrap.php';

// Halaman publik bisa dibuka tanpa login
$publik = ['beranda', 'daftar', 'cek', 'login'];

$routes = [
    'beranda'           => 'pages/beranda.php',
    'daftar'            => 'pages/daftar.php',
    'cek'               => 'pages/cek.php',
    'login'             => 'pages/login.php',
    'logout'            => 'pages/logout.php',
    'dashboard'         => 'pages/dashboard.php',
    'kegiatan'          => 'pages/kegiatan/index.php',
    'kegiatan.detail'   => 'pages/kegiatan/detail.php',
    'kegiatan.form'     => 'pages/kegiatan/form.php',
    'kegiatan.aksi'     => 'pages/kegiatan/aksi.php',
    'kegiatan.cetak'    => 'pages/kegiatan/cetak.php',
    'materi'            => 'pages/materi/index.php',
    'materi.form'       => 'pages/materi/form.php',
    'materi.aksi'       => 'pages/materi/aksi.php',
    'materi.unduh'      => 'pages/materi/unduh.php',
    'iuran'             => 'pages/iuran/index.php',
    'iuran.aksi'        => 'pages/iuran/aksi.php',
    'kas'               => 'pages/iuran/kas.php',
    'anggota'           => 'pages/anggota/index.php',
    'anggota.form'      => 'pages/anggota/form.php',
    'anggota.aksi'      => 'pages/anggota/aksi.php',
    'pengumuman'        => 'pages/pengumuman.php',
    'laporan'           => 'pages/laporan.php',
    'laporan.csv'       => 'pages/csv.php',
    'profil'            => 'pages/profil.php',
    'pengaturan'        => 'pages/pengaturan.php',
];

$page = $_GET['page'] ?? (user() ? 'dashboard' : 'beranda');
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = user() ? 'dashboard' : 'beranda';
}
if (!in_array($page, $publik, true)) require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];

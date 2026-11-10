<?php
$s = setting();
$navPending = kelola_kegiatan() ? (int) q("SELECT COUNT(*) FROM anggota WHERE status = 'pending'")->fetchColumn() : 0;
$navMateri  = kelola_kegiatan() ? (int) q("SELECT COUNT(*) FROM materi WHERE status = 'menunggu'")->fetchColumn() : 0;
$navIuran   = (int) q("SELECT COUNT(*) FROM iuran WHERE status = 'belum'" . (kelola_kas() ? '' : ' AND anggota_id = ' . (int) user()['id']))->fetchColumn();

$navMenu = ['Kegiatan' => [
    ['dashboard', 'Beranda', 'home', 0],
    ['kegiatan', 'Kegiatan & presensi', 'calendar', 0],
    ['materi', 'Bank materi', 'folder', $navMateri],
    ['pengumuman', 'Pengumuman', 'megaphone', 0],
]];
$navMenu['Keanggotaan'] = [['anggota', 'Anggota', 'users', $navPending], ['iuran', 'Iuran saya', 'wallet', $navIuran]];
if (kelola_kas()) {
    $navMenu['Keanggotaan'][1] = ['iuran', 'Iuran anggota', 'wallet', $navIuran];
    $navMenu['Keanggotaan'][] = ['kas', 'Buku kas', 'chart', 0];
}
if (pengurus()) {
    $navMenu['Pengurus'] = [['laporan', 'Laporan', 'chart', 0]];
    if (role() === 'ketua') $navMenu['Pengurus'][] = ['pengaturan', 'Pengaturan MGMP', 'settings', 0];
}
$navMenu['Akun'] = [['profil', 'Profil & kata sandi', 'user', 0]];

$navCur = explode('.', $_GET['page'] ?? 'dashboard')[0];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'Beranda') ?> · <?= e($s['nama']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="drawer" id="drawer">
    <a class="head" href="<?= url('dashboard') ?>"><span class="lg"><?= icon('school', 22) ?></span><span><b><?= e($s['mapel']) ?></b><small>MGMP <?= e($s['jenjang'] . ' ' . $s['wilayah']) ?></small></span></a>
    <?php if (kelola_kegiatan()): ?><a class="btn stabilo" href="<?= url('kegiatan.form') ?>" style="margin:4px 4px 8px"><?= icon('plus', 18) ?> Kegiatan baru</a><?php endif; ?>
    <nav>
      <?php foreach ($navMenu as $sec => $links): ?>
        <div class="sec"><?= e($sec) ?></div>
        <?php foreach ($links as [$_nk, $_nl, $_ni, $_nn]): ?>
          <a href="<?= url($_nk) ?>" class="<?= $navCur === $_nk ? 'active' : '' ?>"><?= icon($_ni, 20) ?> <?= e($_nl) ?><?php if ($_nn): ?><span class="n"><?= $_nn ?></span><?php endif; ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="grow"></div>
    <div class="me">
      <span class="av"><?= e(mb_strtoupper(mb_substr(user()['nama'], 0, 1))) ?></span>
      <span class="grow"><b><?= e(explode(',', user()['nama'])[0]) ?></b><small><?= e(LABEL_ROLE[role()] ?? role()) ?></small></span>
      <a href="<?= url('logout') ?>" title="Keluar"><?= icon('logout', 20) ?></a>
    </div>
  </aside>
  <main class="main">
    <div class="appbar">
      <button class="ibtn menu-toggle" type="button" aria-label="Menu"><?= icon('menu') ?></button>
      <h1><?= e($title ?? 'Beranda') ?><?php if (!empty($sub)): ?><small><?= e($sub) ?></small><?php endif; ?></h1>
      <div class="acts"><?= $actions ?? '' ?></div>
    </div>
    <?php if ($flash): ?><div class="snack <?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : '') ?>"><?= icon($flash['type'] === 'error' ? 'alert' : 'check') ?> <?= e($flash['msg']) ?></div><?php endif; ?>

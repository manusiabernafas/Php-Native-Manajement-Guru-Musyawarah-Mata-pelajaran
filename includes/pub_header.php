<?php $s = setting(); $flash = flash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? $s['nama']) ?> · <?= e($s['nama']) ?></title>
<meta name="description" content="<?= e($s['tagline']) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="pub-top">
  <div class="wadah">
    <a class="merek" href="<?= url('beranda') ?>"><span class="lg"><?= icon('school', 22) ?></span><span><b>MGMP <?= e($s['mapel']) ?> <?= e($s['jenjang']) ?></b><small><?= e($s['wilayah']) ?></small></span></a>
    <nav>
      <a href="<?= url('beranda') ?>#agenda">Agenda</a>
      <a href="<?= url('beranda') ?>#materi">Materi</a>
      <a href="<?= url('cek') ?>">Cek sertifikat</a>
      <a href="<?= url('daftar') ?>">Daftar anggota</a>
    </nav>
    <a class="btn" href="<?= url('login') ?>"><?= icon('key', 18) ?> Masuk</a>
  </div>
</header>
<?php if ($flash): ?><div class="wadah" style="margin-top:16px"><div class="snack <?= $flash['type'] === 'error' ? 'error' : '' ?>"><?= e($flash['msg']) ?></div></div><?php endif; ?>

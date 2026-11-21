<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body class="login">
<form class="kotak-login" method="post" action="<?= base_url('login') ?>">
  <?= csrf_field() ?>
  <h1>Aplikasi PBJ Sekolah</h1>
  <p class="sub">Pengadaan Barang/Jasa — Panitia P2SP</p>
  <?php if (session()->getFlashdata('galat')): ?>
    <div class="pesan galat"><?= esc(session()->getFlashdata('galat')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('sukses')): ?>
    <div class="pesan sukses"><?= esc(session()->getFlashdata('sukses')) ?></div>
  <?php endif; ?>
  <label>Username <input type="text" name="username" required autofocus></label>
  <label>Kata Sandi <input type="password" name="sandi" required></label>
  <button type="submit">Masuk</button>
</form>
</body>
</html>

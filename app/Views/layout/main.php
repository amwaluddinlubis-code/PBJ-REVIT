<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul ?? 'Aplikasi PBJ Sekolah') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body>
<div class="kerangka">
  <aside class="samping">
    <div class="merek">Aplikasi PBJ Sekolah</div>
    <nav>
      <a href="<?= base_url('/') ?>">Dashboard</a>
      <a href="<?= base_url('bahan') ?>">Master Bahan</a>
      <a href="<?= base_url('spk') ?>">Register SPK</a>
      <a href="<?= base_url('belum-bernomor') ?>">Belum Bernomor SPK</a>
      <a href="<?= base_url('survei') ?>">Survei Harga</a>
      <a href="<?= base_url('negosiasi') ?>">Negosiasi</a>
      <a href="<?= base_url('dokumen') ?>">BAP &amp; BAST / Cetak</a>
      <a href="<?= base_url('invoice') ?>">Invoice</a>
      <a href="<?= base_url('invoice/jurnal') ?>">Jurnal Pembayaran</a>
      <a href="<?= base_url('material') ?>">Buku Material</a>
      <a href="<?= base_url('sisa-bahan') ?>">Sisa Bahan</a>
      <a href="<?= base_url('pengaturan') ?>">Identitas &amp; Pengaturan</a>
    </nav>
  </aside>
  <main class="isi">
    <header class="atas">
      <div><?= esc($judul ?? '') ?></div>
      <div class="pengguna"><?= esc($pengguna['nama'] ?? '') ?> · <a href="<?= base_url('logout') ?>">Keluar</a></div>
    </header>
    <?php if (session()->getFlashdata('sukses')): ?>
      <div class="pesan sukses"><?= esc(session()->getFlashdata('sukses')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('galat')): ?>
      <div class="pesan galat"><?= esc(session()->getFlashdata('galat')) ?></div>
    <?php endif; ?>
    <?= $konten ?? '' ?>
  </main>
</div>
</body>
</html>

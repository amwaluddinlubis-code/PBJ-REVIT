<?php
/**
 * Partial layout: kepala dokumen (doctype, head, CSS, skrip awal tema & zoom).
 * Dipakai layout/main.php lewat $this->include() — variabel tampilan
 * ($judul) diwarisi dari view pemanggil.
 */
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul ?? 'Analisa Perencana') ?></title>
<link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>?v=<?= file_exists(FCPATH . 'favicon.svg') ? filemtime(FCPATH . 'favicon.svg') : 0 ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32.png') ?>?v=<?= file_exists(FCPATH . 'favicon-32.png') ? filemtime(FCPATH . 'favicon-32.png') : 0 ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png') ?>?v=<?= file_exists(FCPATH . 'apple-touch-icon.png') ? filemtime(FCPATH . 'apple-touch-icon.png') : 0 ?>">
<meta name="theme-color" content="#0049b8">
<link rel="stylesheet" href="<?= base_url('assets/idds.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= file_exists(FCPATH . 'assets/app.css') ? filemtime(FCPATH . 'assets/app.css') : 0 ?>">
<script>
try {
  if (localStorage.getItem('ap-tema') === 'gelap') document.documentElement.classList.add('gelap');
  var _z = parseFloat(localStorage.getItem('ap-zoom') || '1');
  if (_z && _z !== 1) {
    document.documentElement.style.zoom = _z;
    document.documentElement.style.setProperty('--ap-zoom-balik', String(1 / _z));
  }
} catch (e) {}
</script>
</head>

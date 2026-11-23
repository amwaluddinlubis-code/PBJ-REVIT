<?php
/**
 * Layout utama aplikasi (pola komponen ala TALL di CI4): halaman ini
 * hanya merangkai partial bersama di folder layout/ —
 *   _kepala            : doctype, head, CSS, skrip awal tema & zoom
 *   _sidebar           : kerangka, logo, pemilih sekolah, navigasi, profil
 *   _atas              : topbar, judul, toast, dan konten halaman
 *   _skrip             : aset JS bersama + skrip perilaku layout
 *   _modal_konfirmasi  : modal konfirmasi bersama + skrip toast/modal
 * Partial dirender dengan $this->include() agar mewarisi seluruh
 * variabel tampilan dari BaseController::tampil() ($judul, $pengguna,
 * $aksesMenu, $sekolahAktif, $semuaSekolah, $semuaGedung, $konten, $q).
 * Ikon SVG bersama tersedia sebagai fungsi global ikonSvg() di
 * app/Common.php.
 */ ?><?= $this->include('layout/_kepala') ?>
<body>
<?= $this->include('layout/_sidebar') ?>
<?= $this->include('layout/_atas') ?>
<?= $this->include('layout/_skrip') ?>
<?= $this->include('layout/_modal_konfirmasi') ?>
</body>
</html>

<?php
/* Halaman Biaya Non-Gedung dirangkai dari partial (pola komponen):
   _hero (KPI), _kosong (keadaan kosong), _kelompok (tabel per kelompok),
   _modal_biaya, _skrip. Agregat hero dihitung di _hero sendiri dari
   data $verifikasi; format $rp/$angka didefinisikan di partial pemakai. */
?>
<div data-bulk-akar x-data="komponenBulk('form-bulk-biaya','ids-biaya')">

<?= $this->include('biayalain/_hero') ?>
<?= $this->include('biayalain/_kosong') ?>
<?= $this->include('biayalain/_kelompok') ?>

<?= $this->include('biayalain/_modal_biaya') ?>

<?= $this->include('biayalain/_skrip') ?>

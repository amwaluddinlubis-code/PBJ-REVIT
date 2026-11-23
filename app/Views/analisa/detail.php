<?php

$lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb'];
$total = 0.0;
foreach ($komponen as $k) {
    $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0);
}
?>
<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('analisa') ?>">Analisa Pekerjaan</a> <span aria-hidden="true">/</span> <span><?= esc($a['kode']) ?></span></nav>
<?= $this->include('analisa/_panel_komponen') ?>

<?= $this->include('analisa/_panel_pemakai') ?>

<?= $this->include('analisa/_modal_ubah') ?>

<?= $this->include('analisa/_modal_tambah') ?>

<?= $this->include('analisa/_skrip') ?>

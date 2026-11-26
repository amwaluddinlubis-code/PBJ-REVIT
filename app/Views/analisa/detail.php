<?php

$lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb'];
$total = 0.0;
foreach ($komponen as $k) {
    $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0);
}
?>
<?= $this->include('analisa/_panel_komponen') ?>

<?= $this->include('analisa/_panel_pemakai') ?>

<?= $this->include('analisa/_modal_ubah') ?>

<?= $this->include('analisa/_modal_tambah') ?>

<?= $this->include('analisa/_skrip') ?>

<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>SPJ Lengkap <?= esc($nomorSpk) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<style>
  @media print {
    .lembar { page-break-after: always; }
    .lembar.akhir { page-break-after: auto; }
  }
  @media screen {
    .lembar + .lembar { border-top: 2px dashed #999; margin-top: 26px; padding-top: 20px; }
  }
</style>
<?php foreach ($lembar as $i => $isi): ?>
<div class="lembar<?= $i === count($lembar) - 1 ? ' akhir' : '' ?>"><?= $isi ?></div>
<?php endforeach; ?>
</body></html>

<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <table>
    <tr><th>Kode</th><th>Nama Bahan</th><th class="angka">Qty RAB</th><th class="angka">Masuk SPK</th><th class="angka">Sisa vs RAB</th><th>Status</th><th class="angka">Stok Gudang (Buku Material)</th></tr>
    <?php foreach ($bahan as $b): $sisa = (float) $b['qty_rab'] - (float) $b['masuk_spk']; $stok = (float) $b['gudang_masuk'] - (float) $b['gudang_keluar'];
      $status = $sisa < 0 ? 'Melebihi RAB' : ($sisa == 0 ? 'Sudah penuh' : ($b['masuk_spk'] > 0 ? 'Sebagian' : 'Belum masuk SPK')); ?>
    <tr<?= $sisa < 0 ? ' class="negatif"' : '' ?>>
      <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td>
      <td class="angka"><?= $fmt($b['qty_rab']) ?></td><td class="angka"><?= $fmt($b['masuk_spk']) ?></td>
      <td class="angka"><?= $fmt($sisa) ?></td><td><?= $status ?></td>
      <td class="angka"><?= $fmt($stok) ?> <?= esc($b['satuan'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

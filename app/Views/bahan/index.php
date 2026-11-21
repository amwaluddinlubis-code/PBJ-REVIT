<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><a class="tombol" href="<?= base_url('bahan/baru') ?>">+ Tambah Bahan</a></div>
<div class="panel">
<table data-paginasi="10">
  <tr><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="angka">Harga RAB</th><th class="angka">Qty RAB</th><th class="angka">Anggaran</th><th class="angka">Terpakai (SPK)</th><th class="angka">Sisa</th><th></th></tr>
  <?php foreach ($bahan as $b): $sisa = (float) $b['qty_total'] - (float) $b['terpakai']; ?>
  <tr<?= $sisa < 0 ? ' class="negatif"' : '' ?>>
    <td><?= esc($b['kode']) ?></td>
    <td><?= esc($b['nama']) ?></td>
    <td><?= esc($b['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($b['harga_rab']) ?></td>
    <td class="angka"><?= number_format((float) $b['qty_total'], 2, ',', '.') ?></td>
    <td class="angka"><?= $rp($b['anggaran']) ?></td>
    <td class="angka"><?= number_format((float) $b['terpakai'], 2, ',', '.') ?></td>
    <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    <td><a href="<?= base_url('bahan/ubah/' . $b['id']) ?>">Ubah</a></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

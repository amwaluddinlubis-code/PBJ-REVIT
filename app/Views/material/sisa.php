<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <p class="kecil-teks" style="margin-top:0">Qty RAB mengikuti kebutuhan analisa gedung didanai (sama seperti Master Bahan); Masuk SPK dari rincian SPK; stok gudang = penerimaan BAST dikurangi catatan keluar di <a href="<?= base_url('material') ?>">Buku Material</a>. Nama bahan membuka <a href="<?= base_url('bahan') ?>">Master Bahan</a>; kebutuhan &amp; belanja direncanakan di <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a>.<?php if (($jmlSembunyi ?? 0) > 0): ?> <?php if (! empty($tampilSemua)): ?>Menampilkan semua bahan, termasuk <?= (int) $jmlSembunyi ?> bahan tanpa kebutuhan. <a href="<?= base_url('sisa-bahan') ?>">Sembunyikan yang tanpa kebutuhan</a>.<?php else: ?><?= (int) $jmlSembunyi ?> bahan tanpa kebutuhan disembunyikan. <a href="<?= base_url('sisa-bahan?semua=1') ?>">Tampilkan semua</a>.<?php endif; ?><?php endif; ?></p>
  <table data-paginasi="10" data-cari="Cari bahan…">
    <tr><th>Kode</th><th>Nama Bahan</th><th class="angka">Qty RAB</th><th class="angka">Masuk SPK</th><th class="angka">Sisa vs RAB</th><th>Status</th><th class="angka">Stok Gudang (BAST − Keluar)</th></tr>
    <?php foreach ($bahan as $b): $sisa = (float) $b['qty_rab'] - (float) $b['masuk_spk']; $stok = (float) $b['gudang_masuk'] - (float) $b['gudang_keluar'];
      $status = $sisa < 0 ? 'Melebihi RAB' : (((float) $b['qty_rab'] == 0 && (float) $b['masuk_spk'] == 0) ? 'Tidak ada kebutuhan' : ($sisa == 0 ? 'Sudah penuh' : ($b['masuk_spk'] > 0 ? 'Sebagian' : 'Belum masuk SPK'))); ?>
    <tr<?= $sisa < 0 ? ' class="negatif"' : '' ?>>
      <td><?= esc($b['kode']) ?></td><td><a href="<?= base_url('bahan') ?>"><?= esc($b['nama']) ?></a></td>
      <td class="angka"><?= $fmt($b['qty_rab']) ?></td><td class="angka"><?= $fmt($b['masuk_spk']) ?></td>
      <td class="angka"><?= $fmt($sisa) ?></td><td><?= $status ?></td>
      <td class="angka"><?= $fmt($stok) ?> <?= esc($b['satuan'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

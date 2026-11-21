<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><a class="tombol" href="<?= base_url('invoice/baru') ?>">+ Invoice Baru</a>
<a class="tombol abu" href="<?= base_url('invoice/jurnal') ?>">Jurnal Pembayaran</a></div>
<div class="panel">
<table data-paginasi="10">
  <tr><th>No.</th><th>No. Invoice Toko</th><th>Tanggal</th><th>SPK</th><th>Penyedia</th><th class="angka">Nilai Invoice</th><th class="angka">Total SPK Terkait</th><th>Status</th><th></th></tr>
  <?php foreach ($inv as $i): $beda = (float) $i['nilai'] !== (float) $i['total_spk']; ?>
  <tr<?= $beda ? ' class="negatif"' : '' ?>>
    <td><?= (int) $i['nomor_urut'] ?></td>
    <td><?= esc($i['no_toko'] ?? '—') ?></td>
    <td><?= esc($i['tanggal'] ?? '—') ?></td>
    <td><a href="<?= base_url('spk/detail/' . $i['spk_id']) ?>"><?= (int) $i['spk_nomor'] ?></a></td>
    <td><?= esc($i['penyedia'] ?? '—') ?></td>
    <td class="angka"><?= $rp($i['nilai']) ?></td>
    <td class="angka"><?= $rp($i['total_spk']) ?><?= $beda ? ' ⚠' : '' ?></td>
    <td><?= esc($i['status']) ?></td>
    <td><a href="<?= base_url('invoice/ubah/' . $i['id']) ?>">Ubah</a> · <a href="<?= base_url('invoice/cetak/' . $i['id']) ?>" target="_blank">Cetak</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="kecil-teks">Baris merah + tanda ⚠ berarti nilai invoice tidak sama dengan total SPK terkait — sama seperti pengendali di workbook v3, cek dokumen fisiknya sebelum membayar.</p>
</div>

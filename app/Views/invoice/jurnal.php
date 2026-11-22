<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); $tot = 0; ?>
<div class="panel">
  <h2>Catat Pembayaran</h2>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('invoice/jurnal-tambah') ?>">
    <?= csrf_field() ?>
    <label>Invoice Belum Bayar
      <select data-select-cari name="invoice_id" required>
        <option value="">— pilih invoice —</option>
        <?php foreach ($belum as $b): ?>
          <option value="<?= (int) $b['id'] ?>">Invoice <?= (int) $b['nomor_urut'] ?> (SPK <?= (int) $b['spk_nomor'] ?>) — <?= $rp($b['nilai']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Tanggal Bayar <input type="date" name="tanggal"></label>
    <label>Keterangan <input name="keterangan"></label>
    <div class="aksi md:col-span-2"><button type="submit">Catat Bayar</button></div>
  </form>
</div>
<div class="panel">
  <h2>Jurnal Pembayaran</h2>
  <table data-paginasi="10">
    <tr><th>Tanggal</th><th>Invoice</th><th>SPK</th><th class="angka">Nilai Tagihan</th><th class="angka">PPN Dipungut</th><th class="angka">DPP</th><th class="angka">PPh 22 (1,5% dari DPP)</th><th class="angka">Nilai Dibayar</th><th>Keterangan</th><th></th></tr>
    <?php foreach ($baris as $r): $tot += (float) $r['dibayar']; ?>
    <tr>
      <td><?= esc($r['tanggal'] ?? '— (belum diisi)') ?></td>
      <td><?= (int) $r['nomor_urut'] ?> (<?= esc($r['no_toko'] ?? '') ?>)</td>
      <td><?= (int) $r['spk_nomor'] ?></td>
      <td class="angka"><?= $rp($r['nilai']) ?><?php if ((float) $r['nilai'] >= 5000000): ?><br><span class="kecil-teks">kuitansi ≥ Rp5 jt: materai Rp10.000</span><?php endif; ?></td>
      <td class="angka"><?= $rp($r['ppn']) ?></td>
      <td class="angka"><?= $rp($r['dpp']) ?></td>
      <td class="angka"><?= $rp($r['pph22']) ?><?php if ((float) $r['pph22'] <= 0 && (float) $r['dpp'] > 0): ?><br><span class="kecil-teks">bebas: DPP ≤ Rp2 jt</span><?php endif; ?></td>
      <td class="angka"><?= $rp($r['dibayar']) ?></td>
      <td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('invoice/jurnal-hapus/' . $r['id']) ?>" data-konfirmasi="Hapus baris jurnal ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <tr><th colspan="7" style="text-align:right">TOTAL DIBAYAR</th><th class="angka"><?= $rp($tot) ?></th><th colspan="2"></th></tr>
  </table>
  <p class="kecil-teks">Menurut Juknis Revitalisasi SMP 2026: PPh 22 sebesar 1,5% dihitung dari DPP (nilai di luar PPN) dan tidak dipungut untuk pembelian sampai dengan Rp2.000.000; PPN 11% dipungut bendahara dari bagian harga yang kena PPN; nilai dibayar = nilai tagihan − PPN dipungut − PPh 22.</p>
</div>

<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Invoice <?= esc($nomor) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); $pph = round((float) $i['nilai'] * 0.015); ?>
<h1>Invoice</h1>
<div class="nomor">Nomor : <?= esc($nomor) ?><?= $i['no_toko'] ? ' · No. Toko: ' . esc($i['no_toko']) : '' ?></div>
<table>
  <tr><td style="width:170px">Dari (Penyedia)</td><td>: <strong><?= esc($i['penyedia'] ?? '—') ?></strong><?= $i['penyedia_alamat'] ? ', ' . esc($i['penyedia_alamat']) : '' ?></td></tr>
  <tr><td>Kepada</td><td>: Panitia P2SP <?= esc($idn['nama_sekolah'] ?? '') ?></td></tr>
  <tr><td>Dasar</td><td>: SPK Nomor <?= esc($nomorSpk) ?> tanggal <?= esc($i['spk_tanggal'] ?? '—') ?> — <?= esc($i['paket'] ?? '') ?></td></tr>
  <tr><td>Tanggal Invoice</td><td>: <?= esc($i['tanggal'] ?? '—') ?></td></tr>
</table>
<table class="baris">
  <tr><th>No</th><th>Nama Bahan</th><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Jumlah</th></tr>
  <?php $no = 1; foreach ($detail as $d): ?>
  <tr><td><?= $no++ ?></td><td><?= esc($d['nama']) ?></td><td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td><td><?= esc($d['satuan'] ?? '') ?></td><td class="angka"><?= $rp($d['harga']) ?></td><td class="angka"><?= $rp($d['total']) ?></td></tr>
  <?php endforeach; ?>
  <tr><th colspan="5" style="text-align:right">Total SPK</th><th class="angka"><?= $rp($totalSpk) ?></th></tr>
  <tr><th colspan="5" style="text-align:right">Nilai Tagihan (Invoice)</th><th class="angka"><?= $rp($i['nilai']) ?></th></tr>
</table>
<?php if ((float) $i['nilai'] !== (float) $totalSpk): ?>
<p><strong>Catatan:</strong> nilai invoice berbeda dari total SPK terkait — periksa kembali dokumen fisik sebelum membayar.</p>
<?php endif; ?>
<p>Nilai tagihan sebesar <strong><?= $rp($i['nilai']) ?></strong> (terbilang: <em><?= esc(trim($terbilang)) ?> Rupiah</em>). Pada saat pembayaran dipotong PPh 22 sebesar 1,5% (<?= $rp($pph) ?>), sehingga nilai bersih yang diterima penyedia sebesar <?= $rp((float) $i['nilai'] - $pph) ?>. Status saat dicetak: <?= esc($i['status']) ?>.</p>
<div class="ttd">
  <div class="kol tengah">Penyedia<br><?= esc($i['penyedia'] ?? '') ?><div class="ruang"></div><strong><?= esc($i['ttd_penyedia'] ?? '') ?></strong></div>
  <div class="kol tengah">Ketua Panitia P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
</div>
</body></html>

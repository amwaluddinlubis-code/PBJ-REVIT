<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Daftar Pembayaran Upah <?= esc($bulan) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); ?>
<h1>DAFTAR PEMBAYARAN UPAH</h1>
<div class="nomor">Bulan <?= esc(date('F Y', strtotime($bulan . '-01'))) ?> · Program Revitalisasi Satuan Pendidikan SMP Tahun <?= esc($idn['tahun_anggaran'] ?? '2026') ?></div>
<table class="baris">
  <tr><th style="width:34px">No</th><th>Tanggal</th><th>Jenis Tenaga</th><th>Penerima</th><th class="angka">OH</th><th class="angka">Tarif/OH</th><th class="angka">Jumlah Bruto</th><th class="angka">PPh 21</th><th class="angka">Bersih Diterima</th><th style="width:130px">Tanda Tangan Penerima</th></tr>
  <?php $no = 0; foreach ($rows as $u): $no++; ?>
  <tr>
    <td><?= $no ?></td>
    <td><?= esc(tgl($u['tanggal'])) ?></td>
    <td><?= esc($u['jenis_tenaga']) ?></td>
    <td><?= esc($u['penerima'] ?? '') ?></td>
    <td class="angka"><?= $angka($u['orang_hari']) ?></td>
    <td class="angka"><?= rp($u['tarif']) ?></td>
    <td class="angka"><?= rp($u['jumlah']) ?></td>
    <td class="angka"><?= $u['pph21'] > 0 ? rp($u['pph21']) : '—' ?></td>
    <td class="angka"><?= rp($u['bersih']) ?></td>
    <td style="height:34px"></td>
  </tr>
  <?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="10">Tidak ada pembayaran upah pada bulan ini.</td></tr><?php endif; ?>
  <tr><th colspan="6" style="text-align:right">TOTAL</th><th class="angka"><?= rp($total['bruto']) ?></th><th class="angka"><?= rp($total['pph21']) ?></th><th class="angka"><?= rp($total['bersih']) ?></th><th></th></tr>
</table>
<p class="kecil">PPh 21 upah harian menurut Juknis: upah sampai dengan Rp450.000 per hari tidak dipotong; di atasnya dipotong 5% dari kelebihan per hari.</p>
<div class="ttd">
  <div class="kol">Ketua P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
  <div class="kol">Bendahara<div class="ruang"></div><strong><?= esc($idn['sekretaris_nama'] ?? '') ?></strong></div>
  <div class="kol">Mengetahui,<br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
</div>
</body></html>

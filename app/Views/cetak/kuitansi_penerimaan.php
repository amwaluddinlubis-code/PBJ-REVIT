<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Kuitansi Penerimaan Dana Tahap <?= (int) ($k['tahap'] ?? 0) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<h1>KUITANSI</h1>
<table>
  <tr><td style="width:170px">Telah terima dari</td><td>: Kuasa Pengguna Anggaran Satuan Kerja Direktorat Sekolah Menengah Pertama</td></tr>
  <tr><td>Uang sebesar</td><td>: <strong><?= $rp($k['jumlah']) ?></strong></td></tr>
  <tr><td>Terbilang</td><td>: <em><?= esc(trim($terbilang)) ?> Rupiah</em></td></tr>
  <tr><td>Untuk pembayaran</td><td>: Tahap <?= (int) $k['tahap'] === 1 ? 'I (Pertama)' : 'II (Kedua)' ?> Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan Sekolah Menengah Pertama (SMP) Tahun <?= esc($idn['tahun_anggaran'] ?? '2026') ?></td></tr>
  <tr><td>Tanggal diterima</td><td>: <?= esc(tgl($k['tanggal'])) ?></td></tr>
  <?php if (! empty($k['keterangan'])): ?><tr><td>Keterangan</td><td>: <?= esc($k['keterangan']) ?></td></tr><?php endif; ?>
</table>
<div class="ttd">
  <div class="kol tengah">Setuju dibayar,<br>Pejabat Pembuat Komitmen<br>Direktorat SMP<div class="ruang"></div><strong>………………………</strong><br>NIP …………………</div>
  <div class="kol tengah">Yang menerima,<br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
</div>
</body></html>

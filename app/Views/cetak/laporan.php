<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Laporan Rekap PBJ</title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<h1>Laporan Rekap Pengadaan Barang/Jasa</h1>
<div class="nomor"><?= esc($idn['paket_pekerjaan'] ?? '') ?> — Tahun Anggaran <?= esc($idn['tahun_anggaran'] ?? '') ?></div>
<table class="baris">
  <tr><th style="text-align:left">Uraian</th><th class="angka">Nilai</th></tr>
  <tr><td>Total Anggaran (RAB)</td><td class="angka"><?= $rp($totalRab) ?></td></tr>
  <tr><td>Total SPK Bernomor</td><td class="angka"><?= $rp($totalSpk) ?></td></tr>
  <tr><td>Total Invoice</td><td class="angka"><?= $rp($totalInvoice) ?></td></tr>
  <tr><td>Realisasi Bersih (setelah PPh 22 1,5%)</td><td class="angka"><?= $rp($realisasi) ?></td></tr>
  <tr><td><strong>Sisa Anggaran</strong></td><td class="angka"><strong><?= $rp($sisa) ?></strong></td></tr>
</table>
<h1 style="margin-top:20px">Anggaran per Gedung</h1>
<table class="baris">
  <tr><th style="text-align:left">Gedung</th><th class="angka">Anggaran RAB</th><th class="angka">Porsi</th></tr>
  <?php foreach ($gedung as $g): ?>
  <tr><td><?= esc($g['label']) ?></td><td class="angka"><?= $rp($g['anggaran']) ?></td><td class="angka"><?= number_format($g['persen'], 1, ',', '.') ?>%</td></tr>
  <?php endforeach; ?>
</table>
<h1 style="margin-top:20px">Rekap per SPK</h1>
<table class="baris">
  <tr><th>No. SPK</th><th style="text-align:left">Paket</th><th style="text-align:left">Penyedia</th><th class="angka">Nilai SPK</th><th class="angka">Nilai Invoice</th></tr>
  <?php foreach ($perSpk as $s): ?>
  <tr><td><?= (int) $s['nomor'] ?></td><td><?= esc($s['paket'] ?? '—') ?></td><td><?= esc($s['penyedia'] ?? '—') ?></td><td class="angka"><?= $rp($s['nilai_spk']) ?></td><td class="angka"><?= $rp($s['nilai_invoice']) ?></td></tr>
  <?php endforeach; ?>
</table>
<div class="ttd">
  <div class="kol tengah">Ketua Panitia P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
  <div class="kol tengah">Mengetahui,<br>Kepala Sekolah<div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><br>NIP. <?= esc($idn['kepsek_nip'] ?? '') ?></div>
</div>
</body></html>

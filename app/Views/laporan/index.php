<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="kartu-grid">
  <div class="kartu"><span>Total RAB</span><strong><?= $rp($totalRab) ?></strong></div>
  <div class="kartu"><span>Total SPK (bernomor)</span><strong><?= $rp($totalSpk) ?></strong></div>
  <div class="kartu"><span>Total Invoice</span><strong><?= $rp($totalInvoice) ?></strong></div>
  <div class="kartu"><span>Realisasi Bersih (stl. PPh 22)</span><strong><?= $rp($realisasi) ?></strong></div>
  <div class="kartu"><span>Sisa Anggaran</span><strong><?= $rp($sisa) ?></strong></div>
</div>

<div class="panel">
  <h2>Anggaran per Gedung (dari RAB)</h2>
  <table data-cari="Cari gedung…">
    <tr><th>Gedung</th><th class="angka">Anggaran RAB</th><th class="angka">Porsi</th></tr>
    <?php foreach ($gedung as $g): ?>
    <tr><td><?= esc($g['label']) ?></td><td class="angka"><?= $rp($g['anggaran']) ?></td><td class="angka"><?= number_format($g['persen'], 1, ',', '.') ?>%</td></tr>
    <?php endforeach; ?>
    <tr><th style="text-align:right">TOTAL</th><th class="angka"><?= $rp($totalRab) ?></th><th></th></tr>
  </table>
</div>

<div class="panel">
  <h2>Rekap per SPK</h2>
  <table data-paginasi="25" data-cari="Cari SPK…">
    <tr><th>SPK</th><th>Paket</th><th>Penyedia</th><th>Status</th><th class="angka">Nilai SPK</th><th class="angka">Nilai Invoice</th><th class="angka">Sudah Dibayar (kali)</th></tr>
    <?php foreach ($perSpk as $s): ?>
    <tr><td><?= (int) $s['nomor'] ?></td><td><?= esc($s['paket'] ?? '—') ?></td><td><?= esc($s['penyedia'] ?? '—') ?></td><td><?= esc($s['status']) ?></td>
      <td class="angka"><?= $rp($s['nilai_spk']) ?></td><td class="angka"><?= $rp($s['nilai_invoice']) ?></td><td class="angka"><?= (int) $s['jumlah_bayar'] ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="panel">
  <h2>Cetak &amp; Unduh</h2>
  <div class="aksi md:col-span-2" style="margin:0">
    <a class="tombol" href="<?= base_url('laporan/cetak') ?>" target="_blank">Cetak Laporan</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-spk') ?>">Excel: Rekap SPK</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-material') ?>">Excel: Buku Material</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-sisa') ?>">Excel: Sisa Bahan</a>
  </div>
  <p class="kecil-teks">Semua angka dan unduhan mengikuti sekolah aktif: <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?>.</p>
</div>

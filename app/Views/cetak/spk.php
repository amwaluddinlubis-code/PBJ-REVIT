<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>SPK <?= esc($nomor) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<h1>Surat Perintah Kerja (SPK)</h1>
<div class="nomor">Nomor : <?= esc($nomor) ?></div>
<p>Pada hari ini, <?= esc($hariTanggal) ?>, kami yang bertanda tangan di bawah ini:</p>
<table>
  <tr><td style="width:170px">Nama</td><td>: <strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></td></tr>
  <tr><td>Jabatan</td><td>: Ketua Panitia P2SP <?= esc($idn['nama_sekolah'] ?? '') ?></td></tr>
  <tr><td>Alamat</td><td>: <?= esc($idn['alamat'] ?? '') ?>, <?= esc($idn['kecamatan'] ?? '') ?></td></tr>
</table>
<p style="text-align:center">Selanjutnya disebut <strong>PIHAK KESATU</strong></p>
<table>
  <tr><td style="width:170px">Nama Perusahaan</td><td>: <strong><?= esc($s['penyedia'] ?? '') ?></strong></td></tr>
  <tr><td>Nama Penandatangan</td><td>: <?= esc($s['ttd_penyedia'] ?? '') ?></td></tr>
  <tr><td>Alamat</td><td>: <?= esc($s['penyedia_alamat'] ?? '') ?></td></tr>
</table>
<p style="text-align:center">Selanjutnya disebut <strong>PIHAK KEDUA</strong></p>
<p>PIHAK KESATU memerintahkan PIHAK KEDUA untuk melaksanakan pekerjaan <strong><?= esc($s['paket'] ?? '') ?></strong> pada paket <?= esc($idn['paket_pekerjaan'] ?? '') ?> Tahun Anggaran <?= esc($idn['tahun_anggaran'] ?? '') ?>, dengan rincian:</p>
<table class="baris">
  <tr><th>No</th><th>Nama Bahan</th><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Jumlah</th></tr>
  <?php $no = 1; $multiGedung = count(array_unique(array_filter(array_map(static fn ($x) => $x['gedung_rincian'] ?? null, $detail)))) > 1; $gedungLalu = null; foreach ($detail as $d): ?>
  <?php if ($multiGedung && ($d['gedung_rincian'] ?? null) !== $gedungLalu): $gedungLalu = $d['gedung_rincian'] ?? null; ?>
  <tr><th colspan="6" style="text-align:left">Untuk Gedung: <?= esc($gedungLalu ?? '—') ?></th></tr>
  <?php endif; ?>
  <tr><td><?= $no++ ?></td><td><?= esc($d['nama']) ?></td><td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td><td><?= esc($d['satuan'] ?? '') ?></td><td class="angka"><?= $rp($d['harga']) ?></td><td class="angka"><?= $rp($d['total']) ?></td></tr>
  <?php endforeach; ?>
  <tr><th colspan="5" style="text-align:right">Nilai Kontrak</th><th class="angka"><?= $rp($total) ?></th></tr>
</table>
<?php if (! empty($tenaga)): ?>
<p style="margin-bottom:4px">Perkiraan kebutuhan tenaga kerja untuk paket ini:</p>
<table class="baris">
  <tr><th style="width:40px">No</th><th>Jenis Tenaga</th><th>Keahlian</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Perkiraan Orang</th></tr>
  <?php $noT = 1; $totOhT = 0; $totOrangT = 0; foreach ($tenaga as $t): $totOhT += (float) $t['oh']; $totOrangT += (int) ($t['jumlah_orang'] ?? 0); ?>
  <tr><td><?= $noT++ ?></td><td><?= esc($t['jenis']) ?></td><td><?= esc($t['keahlian'] ?? '—') ?></td><td class="angka"><?= number_format((float) $t['oh'], 2, ',', '.') ?></td><td class="angka"><?= $t['jumlah_orang'] === null ? '—' : (int) $t['jumlah_orang'] ?></td></tr>
  <?php endforeach; ?>
  <tr><th colspan="3" style="text-align:right">Total</th><th class="angka"><?= number_format($totOhT, 2, ',', '.') ?></th><th class="angka"><?= $totOrangT ?></th></tr>
</table>
<?php endif; ?>
<p>Nilai kontrak sebesar <strong><?= $rp($total) ?></strong> (terbilang: <em><?= esc(trim($terbilang)) ?> Rupiah</em>). Jangka waktu pelaksanaan: <?= esc(tgl($s['tgl_mulai'] ?? null)) ?> s.d. <?= esc(tgl($s['tgl_selesai'] ?? null)) ?>. Pembayaran dilakukan setelah barang diterima dan dituangkan dalam Berita Acara Serah Terima.</p>
<div class="ttd">
  <div class="kol tengah">PIHAK KEDUA<br><?= esc($s['penyedia'] ?? '') ?><div class="ruang"></div><strong><?= esc($s['ttd_penyedia'] ?? '') ?></strong></div>
  <div class="kol tengah">PIHAK KESATU<br>Ketua Panitia P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
</div>
<div class="ttd"><div class="kol tengah" style="margin:0 auto">Mengetahui,<br>Kepala Sekolah<div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><br>NIP. <?= esc($idn['kepsek_nip'] ?? '') ?></div></div>
</body></html>

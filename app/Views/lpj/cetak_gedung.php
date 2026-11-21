<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>LPJ Gedung <?= esc($g['nama']) ?></title>
<style>
body { font-family: "Times New Roman", serif; margin: 28px 34px; color: #111; font-size: 13px; }
.kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 14px; }
.kop .pem { font-size: 14px; } .kop .nama { font-size: 17px; font-weight: bold; }
.kop .alamat { font-size: 11.5px; }
h1 { font-size: 15px; text-align: center; margin: 10px 0 2px; text-transform: uppercase; }
.sub { text-align: center; margin-bottom: 14px; font-size: 12.5px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
th, td { border: 1px solid #555; padding: 5px 7px; }
th { background: #eee; font-size: 12px; }
td.angka, th.angka { text-align: right; }
tr.total td { font-weight: bold; background: #f4f4f4; }
tr.bab td { font-weight: bold; background: #f8f8f8; }
.ttd { display: flex; justify-content: space-between; margin-top: 30px; }
.ttd div { width: 45%; text-align: center; }
.ttd .nama { margin-top: 62px; font-weight: bold; text-decoration: underline; }
@media print { body { margin: 12px; } }
</style>
</head>
<body>
<?php $rp = static fn ($n) => number_format((float) $n, 0, ',', '.'); ?>
<div class="kop">
  <div class="pem"><?= esc($sekolah['kop_pemerintah'] ?? 'PEMERINTAH KABUPATEN MANDAILING NATAL') ?></div>
  <div class="pem"><?= esc($sekolah['kop_dinas'] ?? 'DINAS PENDIDIKAN') ?></div>
  <div class="nama"><?= esc($sekolah['nama_sekolah'] ?? '') ?></div>
  <div class="alamat"><?= esc($sekolah['alamat'] ?? '') ?>, Desa <?= esc($sekolah['desa'] ?? '') ?>, Kec. <?= esc($sekolah['kecamatan'] ?? '') ?>, Kab. <?= esc($sekolah['kabupaten'] ?? '') ?> · NPSN <?= esc($sekolah['npsn'] ?? '') ?></div>
</div>
<h1>LPJ Gedung — <?= esc($g['nama']) ?></h1>
<p class="sub">Nilai BOQ Rp <?= $rp($g['nilai_boq']) ?> · Realisasi Rp <?= $rp($g['nilai_realisasi']) ?> (<?= number_format($g['persen_fisik'], 1, ',', '.') ?>%) · Kebutuhan Bahan Rp <?= $rp($g['nilai_kebutuhan_bahan']) ?> · SPK Dipesan Rp <?= $rp($g['nilai_spk']) ?> · Upah Dibayar Rp <?= $rp($g['upah_dibayar']) ?></p>
<table>
  <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th class="angka">%</th><th class="angka">Nilai BOQ</th><th class="angka">Nilai Realisasi</th></tr>
  <?php $babLalu = null; foreach ($pekerjaan as $p): $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0; ?>
    <?php if ($p['bab'] !== $babLalu): $babLalu = $p['bab']; ?>
    <tr class="bab"><td colspan="6"><?= esc($babLalu ?: '—') ?></td></tr>
    <?php endif; ?>
    <tr>
      <td><?= esc($p['nama']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume_terealisasi'], 3, ',', '.'), '0'), ',') ?></td>
      <td class="angka"><?= number_format($persen, 1, ',', '.') ?>%</td>
      <td class="angka"><?= $rp($p['jumlah']) ?></td>
      <td class="angka"><?= $rp((float) $p['volume_terealisasi'] * (float) $p['harga_satuan']) ?></td>
    </tr>
  <?php endforeach; ?>
  <tr class="total"><td colspan="4">TOTAL</td><td class="angka"><?= $rp($g['nilai_boq']) ?></td><td class="angka"><?= $rp($g['nilai_realisasi']) ?></td></tr>
</table>
<div class="ttd">
  <div>Mengetahui,<br>Kepala Sekolah<div class="nama"><?= esc($sekolah['kepsek_nama'] ?? '') ?></div>NIP. <?= esc($sekolah['kepsek_nip'] ?? '') ?></div>
  <div><?= esc($sekolah['desa'] ?? '') ?>, <?= date('d') ?> <?= ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int) date('n')] ?> <?= date('Y') ?><br>Ketua Panitia P2SP<div class="nama"><?= esc($sekolah['ketua_nama'] ?? '') ?></div></div>
</div>
<script>window.onload = () => setTimeout(() => window.print(), 350);</script>
</body>
</html>

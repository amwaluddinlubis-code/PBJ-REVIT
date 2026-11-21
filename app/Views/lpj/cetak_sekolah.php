<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>LPJ Revitalisasi — <?= esc($sekolah['nama_sekolah'] ?? '') ?></title>
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
<h1>Laporan Pertanggungjawaban (LPJ) Revitalisasi Sekolah</h1>
<p class="sub">Tahun Anggaran <?= esc($sekolah['tahun_anggaran'] ?? date('Y')) ?> · Total Anggaran (Bantuan) Rp <?= $rp($anggaran) ?> · Belanja Terbayar Rp <?= $rp($terbayar) ?> · Sisa Pagu Rp <?= $rp($anggaran - $terbayar) ?></p>
<table>
  <tr><th>No</th><th>Gedung</th><th class="angka">Nilai BOQ</th><th class="angka">Realisasi Fisik</th><th class="angka">Kemajuan %</th><th class="angka">Kebutuhan Bahan</th><th class="angka">SPK Dipesan</th><th class="angka">Invoice Terbayar</th><th class="angka">Upah Dibayar</th></tr>
  <?php $no = 0; foreach ($rekap as $g): $no++; ?>
  <tr>
    <td><?= $no ?></td><td><?= esc($g['nama']) ?></td>
    <td class="angka"><?= $rp($g['nilai_boq']) ?></td>
    <td class="angka"><?= $rp($g['nilai_realisasi']) ?></td>
    <td class="angka"><?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</td>
    <td class="angka"><?= $rp($g['nilai_kebutuhan_bahan']) ?></td>
    <td class="angka"><?= $rp($g['nilai_spk']) ?></td>
    <td class="angka"><?= $rp($g['invoice_terbayar']) ?></td>
    <td class="angka"><?= $rp($g['upah_dibayar']) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="2">TOTAL</td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'nilai_boq'))) ?></td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'nilai_realisasi'))) ?></td>
    <td></td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'nilai_kebutuhan_bahan'))) ?></td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'nilai_spk'))) ?></td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'invoice_terbayar'))) ?></td>
    <td class="angka"><?= $rp(array_sum(array_column($rekap, 'upah_dibayar'))) ?></td>
  </tr>
</table>
<p>Kebutuhan bahan dihitung dari BOQ × koefisien analisa (AHSP) dokumen perencana pada harga acuan aplikasi. Realisasi fisik berdasarkan volume pekerjaan yang dicatat panitia.</p>
<div class="ttd">
  <div>Mengetahui,<br>Kepala Sekolah<div class="nama"><?= esc($sekolah['kepsek_nama'] ?? '') ?></div>NIP. <?= esc($sekolah['kepsek_nip'] ?? '') ?></div>
  <div><?= esc($sekolah['desa'] ?? '') ?>, <?= date('d') ?> <?= ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int) date('n')] ?> <?= date('Y') ?><br>Ketua Panitia P2SP<div class="nama"><?= esc($sekolah['ketua_nama'] ?? '') ?></div></div>
</div>
<script>window.onload = () => setTimeout(() => window.print(), 350);</script>
</body>
</html>

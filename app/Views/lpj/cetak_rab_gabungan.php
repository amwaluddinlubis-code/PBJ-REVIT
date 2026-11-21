<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>RAB Gabungan — <?= esc($sekolah['nama_sekolah'] ?? '') ?></title>
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
.ttd .ruang { height: 70px; }
</style>
</head>
<body>
<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); ?>
<div class="kop">
  <div class="pem"><?= esc($sekolah['kop_pemerintah'] ?? 'PEMERINTAH KABUPATEN') ?></div>
  <div class="pem"><?= esc($sekolah['kop_dinas'] ?? 'DINAS PENDIDIKAN') ?></div>
  <div class="nama"><?= esc($sekolah['nama_sekolah'] ?? '') ?></div>
  <div class="alamat"><?= esc(trim(($sekolah['alamat'] ?? '') . ' ' . ($sekolah['desa'] ?? '') . ' Kec. ' . ($sekolah['kecamatan'] ?? '') . ' Kab. ' . ($sekolah['kabupaten'] ?? ''))) ?> · NPSN <?= esc($sekolah['npsn'] ?? '') ?></div>
</div>
<h1>Rencana Anggaran Biaya (RAB) Gabungan</h1>
<div class="sub">Program Revitalisasi Satuan Pendidikan SMP Tahun <?= esc($sekolah['tahun_anggaran'] ?? '2026') ?> · Gedung: <?= esc(implode(', ', array_column($perGedung, 'nama'))) ?></div>

<table>
  <tr><th style="width:34px">No</th><th>Gedung</th><th class="angka">Nilai BOQ (RAB)</th></tr>
  <?php $no = 0; $totBoq = 0; foreach ($perGedung as $g): $no++; $totBoq += (float) $g['nilai_boq']; ?>
  <tr><td><?= $no ?></td><td><?= esc($g['nama']) ?></td><td class="angka"><?= $rp($g['nilai_boq']) ?></td></tr>
  <?php endforeach; ?>
  <tr class="total"><td colspan="2" style="text-align:right">TOTAL BOQ GEDUNG TERPILIH</td><td class="angka"><?= $rp($totBoq) ?></td></tr>
</table>

<table>
  <tr><th style="width:34px">No</th><th>Pekerjaan</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Volume Gabungan</th><th class="angka">Jumlah</th></tr>
  <?php $no = 0; foreach ($gabungan as $p): $no++; ?>
  <tr>
    <td><?= $no ?></td>
    <td><?= esc($p['nama']) ?></td>
    <td><?= esc($p['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($p['harga_satuan']) ?></td>
    <td class="angka"><?= $angka($p['volume']) ?></td>
    <td class="angka"><?= $rp($p['jumlah']) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total"><td colspan="5" style="text-align:right">TOTAL RAB GABUNGAN</td><td class="angka"><?= $rp($total) ?></td></tr>
</table>

<div class="ttd">
  <div>Ketua P2SP<div class="ruang"></div><strong><?= esc($sekolah['ketua_nama'] ?? '') ?></strong></div>
  <div>Kepala <?= esc($sekolah['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($sekolah['kepsek_nama'] ?? '') ?></strong><?= ! empty($sekolah['kepsek_nip']) ? '<br>NIP ' . esc($sekolah['kepsek_nip']) : '' ?></div>
</div>
</body>
</html>

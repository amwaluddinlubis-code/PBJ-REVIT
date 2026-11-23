<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Papan Nama Kegiatan</title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#000}
  .papan{border:4px solid #000;padding:28px 24px;text-align:center;max-width:900px;margin:0 auto}
  .papan h1{font-size:30px;margin:6px 0;letter-spacing:.5px}
  .papan h2{font-size:22px;margin:4px 0;font-weight:normal}
  .papan .kegiatan{font-size:24px;font-weight:bold;margin:14px 0 4px}
  .papan table{margin:18px auto 6px;text-align:left;font-size:19px;border-collapse:collapse}
  .papan td{padding:5px 10px;vertical-align:top}
  .papan .sumber{font-size:20px;font-weight:bold;margin-top:16px}
  @media print{body{margin:0}.papan{border-width:3px}}
</style></head><body>
<div class="papan">
  <h2><?= esc($idn['kop_pemerintah'] ?? 'PEMERINTAH KABUPATEN') ?></h2>
  <h1><?= esc($idn['kop_dinas'] ?? 'DINAS PENDIDIKAN') ?></h1>
  <div class="kegiatan">KEGIATAN REVITALISASI SATUAN PENDIDIKAN<br>SEKOLAH MENENGAH PERTAMA (SMP) TAHUN <?= esc($idn['tahun_anggaran'] ?? '2026') ?></div>
  <table>
    <tr><td>Nama Sekolah</td><td>: <strong><?= esc($idn['nama_sekolah'] ?? '') ?></strong> (NPSN <?= esc($idn['npsn'] ?? '') ?>)</td></tr>
    <tr><td>Paket Pekerjaan</td><td>: <?= esc($idn['paket_pekerjaan'] ?? 'Revitalisasi Satuan Pendidikan') ?></td></tr>
    <tr><td>Alamat</td><td>: <?= esc(trim(($idn['alamat'] ?? '') . ' ' . ($idn['desa'] ?? '') . ' Kec. ' . ($idn['kecamatan'] ?? '') . ' Kab. ' . ($idn['kabupaten'] ?? ''))) ?></td></tr>
    <tr><td>Pelaksana</td><td>: Panitia Pembangunan Satuan Pendidikan (P2SP) <?= esc($idn['nama_sekolah'] ?? '') ?></td></tr>
    <tr><td>Ketua P2SP</td><td>: <?= esc($idn['ketua_nama'] ?? '') ?></td></tr>
    <tr><td>Jumlah Dana Bantuan</td><td>: <?= rp($idn['total_anggaran'] ?? 0) ?></td></tr>
    <tr><td>Waktu Pelaksanaan</td><td>: Tahun Anggaran <?= esc($idn['tahun_anggaran'] ?? '2026') ?></td></tr>
  </table>
  <div class="sumber">SUMBER DANA : APBN — BANTUAN PEMERINTAH PROGRAM REVITALISASI SATUAN PENDIDIKAN<br>DIREKTORAT SEKOLAH MENENGAH PERTAMA, KEMENTERIAN PENDIDIKAN</div>
</div>
</body></html>

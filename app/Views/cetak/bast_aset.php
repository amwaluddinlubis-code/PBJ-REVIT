<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Berita Acara Serah Terima Aset</title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>BERITA ACARA SERAH TERIMA ASET</h1>
<div class="nomor">Nomor : 422/BAST-ASET/<?= (int) $g['id'] ?>/<?= esc($idn['tahun_anggaran'] ?? '2026') ?></div>
<p>Pada <?= esc($hariTanggal) ?>, kami yang bertanda tangan di bawah ini:</p>
<table>
  <tr><td style="width:190px">Nama</td><td>: <?= esc($idn['kepsek_nama'] ?? '') ?></td></tr>
  <tr><td>Jabatan</td><td>: Kepala <?= esc($idn['nama_sekolah'] ?? '') ?></td></tr>
  <tr><td>Selanjutnya disebut</td><td>: <strong>PIHAK PERTAMA</strong> (yang menyerahkan)</td></tr>
</table>
<p>menyerahkan aset hasil Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan Sekolah Menengah Pertama (SMP) Tahun <?= esc($idn['tahun_anggaran'] ?? '2026') ?> kepada Dinas Pendidikan Kabupaten <?= esc($idn['kabupaten'] ?? '') ?> selaku <strong>PIHAK KEDUA</strong> (yang menerima), dengan rincian aset sebagai berikut:</p>
<table class="baris">
  <tr><th>Nama Aset</th><th>Lokasi</th><th class="angka">Kemajuan Fisik</th><th class="angka">Nilai Aset (Realisasi BOQ)</th></tr>
  <tr>
    <td><?= esc($g['nama']) ?></td>
    <td><?= esc(trim(($idn['alamat'] ?? '') . ' ' . ($idn['desa'] ?? '') . ' ' . ($idn['kecamatan'] ?? ''))) ?></td>
    <td class="angka"><?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</td>
    <td class="angka"><?= rp($g['nilai_realisasi']) ?></td>
  </tr>
</table>
<p>Aset tersebut merupakan hasil pembangunan/revitalisasi yang dibiayai dari Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan dan selanjutnya dicatat sebagai barang milik daerah sesuai ketentuan pengelolaan barang milik daerah. Nilai aset mengikuti nilai realisasi pekerjaan (BOQ) pada aplikasi; penyesuaian nilai perolehan akhir mengikuti hasil pemeriksaan dan ketentuan pencatatan aset yang berlaku.</p>
<p>Demikian Berita Acara Serah Terima Aset ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
<div class="ttd">
  <div class="kol">PIHAK KEDUA<br>Yang Menerima,<br>Dinas Pendidikan Kab. <?= esc($idn['kabupaten'] ?? '') ?><div class="ruang"></div><strong>………………………</strong><br>NIP …………………</div>
  <div class="kol">PIHAK PERTAMA<br>Yang Menyerahkan,<br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
</div>
</body></html>

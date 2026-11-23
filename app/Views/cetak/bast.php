<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>BAST <?= esc($nomor) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>Berita Acara Serah Terima Barang</h1>
<div class="nomor">Nomor : <?= esc($nomor) ?></div>
<p>Pada hari ini, <?= esc($hariTanggal) ?>, kami yang bertanda tangan di bawah ini:</p>
<table>
  <tr><td style="width:170px">Nama</td><td>: <strong><?= esc($s['ttd_penyedia'] ?? '') ?></strong></td></tr>
  <tr><td>Perusahaan</td><td>: <?= esc($s['penyedia'] ?? '') ?></td></tr>
  <tr><td>Jabatan</td><td>: Penyedia Barang — selanjutnya disebut <strong>PIHAK KESATU</strong></td></tr>
</table>
<table>
  <tr><td style="width:170px">Nama</td><td>: <strong><?= esc($idn['pelaksana_nama'] ?? '') ?></strong></td></tr>
  <tr><td>Jabatan</td><td>: Kepala Pelaksana P2SP <?= esc($idn['nama_sekolah'] ?? '') ?> — selanjutnya disebut <strong>PIHAK KEDUA</strong></td></tr>
</table>
<p>PIHAK KESATU menyerahkan kepada PIHAK KEDUA, dan PIHAK KEDUA menerima dari PIHAK KESATU, barang berdasarkan Surat Perintah Kerja Nomor <?= esc($nomorSpk) ?> tanggal <?= esc(tgl($s['tanggal'] ?? null)) ?> sebagai berikut:</p>
<table class="baris">
  <tr><th>No</th><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th></tr>
  <?php $no = 1; foreach ($items as $it): ?>
  <tr><td><?= $no++ ?></td><td><?= esc($it['nama']) ?></td><td class="angka"><?= number_format((float) $it['qty'], 2, ',', '.') ?></td><td><?= esc($it['satuan'] ?? '') ?></td></tr>
  <?php endforeach; ?>
</table>
<p>Dengan ditandatanganinya berita acara ini, barang tersebut menjadi tanggung jawab PIHAK KEDUA.</p>
<div class="ttd">
  <div class="kol tengah">PIHAK KEDUA<br>Kepala Pelaksana<div class="ruang"></div><strong><?= esc($idn['pelaksana_nama'] ?? '') ?></strong></div>
  <div class="kol tengah">PIHAK KESATU<br><?= esc($s['penyedia'] ?? '') ?><div class="ruang"></div><strong><?= esc($s['ttd_penyedia'] ?? '') ?></strong></div>
</div>
<div class="ttd"><div class="kol tengah" style="margin:0 auto">Mengetahui,<br>Ketua Panitia P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div></div>
</body></html>

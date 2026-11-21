<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>BAP <?= esc($nomor) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>Berita Acara Pemeriksaan Barang</h1>
<div class="nomor">Nomor : <?= esc($nomor) ?></div>
<p>Pada hari ini, <?= esc($hariTanggal) ?>, Tim Pemeriksa Barang <?= esc($idn['nama_sekolah'] ?? '') ?> yang terdiri dari:</p>
<table>
  <?php $no = 1; foreach (['pemeriksa1', 'pemeriksa2', 'pemeriksa3'] as $k): if (! empty($idn[$k])): ?>
  <tr><td style="width:30px"><?= $no++ ?>.</td><td><?= esc($idn[$k]) ?></td><td>Anggota Pemeriksa</td></tr>
  <?php endif; endforeach; ?>
</table>
<p>telah memeriksa barang yang diserahkan oleh <strong><?= esc($s['penyedia'] ?? '') ?></strong> berdasarkan Surat Perintah Kerja Nomor <?= esc($nomorSpk) ?> tanggal <?= esc($s['tanggal'] ?? '—') ?>, dengan hasil sebagai berikut:</p>
<table class="baris">
  <tr><th>No</th><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th><th>Kondisi</th><th>Keterangan</th></tr>
  <?php $no = 1; foreach ($items as $it): ?>
  <tr><td><?= $no++ ?></td><td><?= esc($it['nama']) ?></td><td class="angka"><?= number_format((float) $it['qty'], 2, ',', '.') ?></td><td><?= esc($it['satuan'] ?? '') ?></td><td><?= esc($it['kondisi']) ?></td><td><?= esc($it['keterangan'] ?? '') ?></td></tr>
  <?php endforeach; ?>
</table>
<p>Berdasarkan pemeriksaan di atas, barang dinyatakan sesuai dengan kondisi sebagaimana daftar tersebut dan dapat diterima untuk dituangkan dalam Berita Acara Serah Terima.</p>
<div class="ttd">
  <div class="kol">Tim Pemeriksa:
    <?php foreach (['pemeriksa1', 'pemeriksa2', 'pemeriksa3'] as $i => $k): if (! empty($idn[$k])): ?>
      <div style="margin-top:26px"><?= $i + 1 ?>. <?= esc($idn[$k]) ?><br><span style="border-top:1px dotted #000; display:inline-block; width:70%; margin-top:26px">&nbsp;</span></div>
    <?php endif; endforeach; ?>
  </div>
  <div class="kol tengah">Kepala Pelaksana<div class="ruang"></div><strong><?= esc($idn['pelaksana_nama'] ?? '') ?></strong>
    <div style="margin-top:30px">Penyedia<br><?= esc($s['penyedia'] ?? '') ?><div class="ruang"></div><strong><?= esc($s['ttd_penyedia'] ?? '') ?></strong></div>
  </div>
</div>
</body></html>

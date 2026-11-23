<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Laporan Akhir Pekerjaan</title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>LAPORAN AKHIR PEKERJAAN</h1>
<div class="nomor">Berita Acara Serah Terima Hasil Pekerjaan — Program Revitalisasi Satuan Pendidikan SMP Tahun <?= esc($idn['tahun_anggaran'] ?? '2026') ?></div>
<p>Yang bertanda tangan di bawah ini, Kepala <?= esc($idn['nama_sekolah'] ?? '') ?> selaku penerima Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan Sekolah Menengah Pertama, dengan ini melaporkan penyelesaian pekerjaan 100% (seratus persen) sebagai berikut:</p>
<table class="baris">
  <tr><td style="width:280px">Jumlah dana awal (total dana bantuan yang diterima)</td><td class="angka"><?= rp($diterima) ?></td></tr>
  <tr><td>Jumlah dana yang digunakan (realisasi pembayaran)</td><td class="angka"><?= rp($digunakan) ?></td></tr>
  <tr><td><strong>Sisa dana</strong></td><td class="angka"><strong><?= rp($sisa) ?></strong></td></tr>
  <?php if ($setor_sisa > 0): ?><tr><td>Sisa dana yang telah disetor ke kas negara</td><td class="angka"><?= rp($setor_sisa) ?></td></tr><?php endif; ?>
  <tr><td>Kemajuan penyelesaian pekerjaan</td><td class="angka"><?= number_format($fisik, 1, ',', '.') ?>%</td></tr>
</table>
<ol>
  <li>Pekerjaan telah selesai dilaksanakan sesuai dengan Perjanjian Kerja Sama (PKS) Pemberian Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan.</li>
  <li>Bukti-bukti pengeluaran atas penggunaan dana bantuan telah disimpan dengan baik dan tertib oleh penerima bantuan, dan siap diperiksa sewaktu-waktu.</li>
  <li>Foto dan/atau video pekerjaan yang telah diselesaikan dilampirkan sebagai bagian laporan pertanggungjawaban bantuan.</li>
  <?php if ($sisa > 0): ?><li>Sisa dana sebesar <?= rp($sisa) ?> <?= $setor_sisa >= $sisa ? 'telah' : 'akan' ?> disetorkan ke rekening kas negara<?= $setor_sisa > 0 ? ' (bukti surat setoran terlampir)' : '' ?>.</li><?php endif; ?>
</ol>
<p>Demikian laporan akhir pekerjaan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
<div class="ttd">
  <div class="kol">Ketua P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
  <div class="kol tengah"><?= esc($idn['kabupaten'] ?? '') ?>, <?= esc(date('d F Y')) ?><br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
</div>
</body></html>

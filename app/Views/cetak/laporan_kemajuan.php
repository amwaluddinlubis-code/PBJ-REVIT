<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Laporan Kemajuan Penyelesaian Pekerjaan</title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>LAPORAN KEMAJUAN PENYELESAIAN PEKERJAAN</h1>
<div class="nomor">No. : .................................................</div>
<p>Pada hari ini <?= esc(namaHariIndo(date('Y-m-d'))) ?>, tanggal <?= esc(date('d')) ?> bulan <?= esc(namaBulanIndo((int) date('n'))) ?> tahun <?= esc(date('Y')) ?>, yang bertanda tangan di bawah ini kami:</p>
<table>
  <tr><td style="width:120px">Nama</td><td>: <?= esc($idn['kepsek_nama'] ?? '') ?></td></tr>
  <tr><td>Jabatan</td><td>: Kepala <?= esc($idn['nama_sekolah'] ?? '') ?></td></tr>
  <tr><td>Alamat</td><td>: <?= esc(trim(($idn['alamat'] ?? '') . ' ' . ($idn['desa'] ?? '') . ' ' . ($idn['kecamatan'] ?? '') . ' ' . ($idn['kabupaten'] ?? ''))) ?></td></tr>
</table>
<p>dengan ini menyatakan sebagai berikut:</p>
<p>Berdasarkan Surat Keputusan Pejabat Pembuat Komitmen Direktorat Sekolah Menengah Pertama tentang Penetapan Penerima Bantuan Pemerintah Program Revitalisasi Satuan Pendidikan Sekolah Menengah Pertama (SMP) Nomor ............................ Tanggal ............................ dan Perjanjian Kerja Sama Pemberian Bantuan Pemerintah Program Revitalisasi Nomor ............................ Tanggal ............................ untuk Revitalisasi Satuan <?= esc($idn['nama_sekolah'] ?? '') ?> dengan nilai bantuan sebesar <?= rp($pagu) ?>.</p>
<ol>
  <li>Sampai dengan tanggal <?= esc(tglPanjang(date('Y-m-d'))) ?>, kemajuan penyelesaian pekerjaan Revitalisasi Satuan Pendidikan sebesar <strong><?= number_format($fisik, 1, ',', '.') ?>%</strong>.</li>
  <li>Apabila di kemudian hari, atas laporan penyelesaian pekerjaan yang telah dibuat mengakibatkan kerugian negara maka saya bersedia untuk dituntut penggantian kerugian negara sesuai dengan ketentuan peraturan perundang-undangan.</li>
</ol>
<p>Demikian Laporan Kemajuan Penyelesaian Pekerjaan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
<div class="ttd">
  <div class="kol"></div>
  <div class="kol tengah"><?= esc($idn['kabupaten'] ?? '') ?>, <?= esc(tglPanjang(date('Y-m-d'))) ?><br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
</div>

<h2 style="margin-top:28px">Lampiran — Rincian Kemajuan per Gedung</h2>
<table class="baris">
  <tr><th>Gedung / Paket</th><th class="angka">Nilai BOQ</th><th class="angka">Nilai Realisasi</th><th class="angka">Kemajuan</th></tr>
  <?php foreach ($gedung as $g): ?>
  <tr><td><?= esc($g['nama']) ?></td><td class="angka"><?= rp($g['nilai_boq']) ?></td><td class="angka"><?= rp($g['nilai_realisasi']) ?></td><td class="angka"><?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</td></tr>
  <?php endforeach; ?>
  <tr><th style="text-align:left">Total</th><th class="angka"><?= rp($total_boq) ?></th><th class="angka"><?= rp($total_realisasi) ?></th><th class="angka"><?= number_format($fisik, 1, ',', '.') ?>%</th></tr>
</table>
<p class="kecil">Lampiran foto dan/atau video pekerjaan yang telah diselesaikan menyertai laporan ini (dokumentasi kondisi sebelum, selama, dan sesudah pekerjaan).</p>
</body></html>

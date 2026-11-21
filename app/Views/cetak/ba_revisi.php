<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Berita Acara Revisi Pekerjaan</title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1>BERITA ACARA REVISI PEKERJAAN</h1>
<div class="nomor">Nomor : 422/REVISI/<?= (int) $r['id'] ?>/<?= esc(date('Y', strtotime($r['tanggal']))) ?></div>
<p>Pada hari ini <?= esc(date('l', strtotime($r['tanggal']))) ?>, tanggal <?= esc(date('d F Y', strtotime($r['tanggal']))) ?>, kami yang bertanda tangan di bawah ini — Kepala <?= esc($idn['nama_sekolah'] ?? '') ?>, Ketua Panitia Pembangunan Satuan Pendidikan (P2SP), dan Tim Teknis — telah menyepakati revisi pekerjaan pada Program Revitalisasi Satuan Pendidikan Tahun <?= esc($idn['tahun_anggaran'] ?? '2026') ?> dengan rincian sebagai berikut:</p>
<table class="baris">
  <tr><td style="width:180px">Gedung / Paket</td><td><?= esc($r['gedung_nama']) ?></td></tr>
  <?php if (! empty($r['pekerjaan_nama'])): ?><tr><td>Pekerjaan</td><td><?= esc($r['pekerjaan_nama']) ?></td></tr><?php endif; ?>
  <?php if (! empty($r['perubahan'])): ?><tr><td>Ringkasan Perubahan</td><td><?= esc($r['perubahan']) ?></td></tr><?php endif; ?>
  <tr><td>Uraian Revisi</td><td><?= nl2br(esc($r['uraian'])) ?></td></tr>
</table>
<p>Revisi pekerjaan ini telah dikonsultasikan kepada Pejabat Pembuat Komitmen (PPK) melalui fasilitator, dan menjadi dasar penyesuaian dokumen perencanaan beserta pelaksanaannya.</p>
<p>Demikian Berita Acara Revisi Pekerjaan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
<div class="ttd">
  <div class="kol">Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
  <div class="kol">Ketua P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
  <div class="kol">Tim Teknis<div class="ruang"></div><strong><?= esc($idn['pelaksana_nama'] ?? '') ?></strong></div>
</div>
</body></html>

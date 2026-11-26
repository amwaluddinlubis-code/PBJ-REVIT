<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title><?= esc($nama_buku) ?> <?= esc($bulan) ?></title></head><body>
<?= view('cetak/_gaya') ?>
<?= view('cetak/_kop', ['idn' => $idn]) ?>
<h1><?= esc(strtoupper($nama_buku)) ?></h1>
<div class="nomor">Bulan <?= esc(bulanTahun($bulan)) ?> · <?= esc($idn['nama_sekolah'] ?? '') ?></div>
<table class="baris">
  <tr><th style="width:60px">No. Bukti</th><th style="width:110px">Tanggal</th><th>Uraian</th><th class="angka">Penerimaan</th><th class="angka">Pengeluaran</th><th class="angka">Saldo</th></tr>
  <tr><td></td><td></td><td><strong>Saldo awal bulan</strong></td><td></td><td></td><td class="angka"><strong><?= rp($saldo_awal) ?></strong></td></tr>
  <?php foreach ($baris as $e): ?>
  <tr>
    <td><?= esc((string) ($e['nomor'] ?? '')) ?></td>
    <td><?= esc(tgl($e['tanggal'] ?? null)) ?></td>
    <td><?= esc($e['uraian']) ?></td>
    <td class="angka"><?= $e['masuk'] > 0 ? rp($e['masuk']) : '—' ?></td>
    <td class="angka"><?= $e['keluar'] > 0 ? rp($e['keluar']) : '—' ?></td>
    <td class="angka"><?= rp($e['saldo']) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr><th></th><th></th><th style="text-align:left">Saldo akhir bulan (penutupan buku)</th><th></th><th></th><th class="angka"><?= rp($saldo_akhir) ?></th></tr>
</table>
<?php if (! empty($tutup) && ($tutup['saldo_koran'] ?? null) !== null): ?>
<p><strong>Rekonsiliasi akhir bulan</strong> — saldo rekening koran bank: <?= rp($tutup['saldo_koran']) ?> · selisih bank: <?= rp($tutup['selisih_bank'] ?? 0) ?> · kas fisik hasil opname: <?= rp($tutup['kas_fisik'] ?? 0) ?> · selisih tunai: <?= rp($tutup['selisih_tunai'] ?? 0) ?><?= (float) ($tutup['bunga_bank'] ?? 0) > 0 ? ' · bunga bank: ' . rp($tutup['bunga_bank']) : '' ?><?= (float) ($tutup['biaya_bank'] ?? 0) > 0 ? ' · biaya bank: ' . rp($tutup['biaya_bank']) : '' ?><?= ! empty($tutup['penjelasan_rekon']) ? '<br>Penjelasan: ' . esc($tutup['penjelasan_rekon']) : '' ?></p>
<?php endif; ?>
<div class="ttd">
  <div class="kol">Mengetahui,<br>Kepala <?= esc($idn['nama_sekolah'] ?? '') ?><div class="ruang"></div><strong><?= esc($idn['kepsek_nama'] ?? '') ?></strong><?= ! empty($idn['kepsek_nip']) ? '<br>NIP ' . esc($idn['kepsek_nip']) : '' ?></div>
  <div class="kol">Ketua P2SP<div class="ruang"></div><strong><?= esc($idn['ketua_nama'] ?? '') ?></strong></div>
  <div class="kol">Bendahara<div class="ruang"></div><strong><?= esc($idn['sekretaris_nama'] ?? '') ?></strong></div>
</div>
</body></html>

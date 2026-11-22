<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<section class="dashboard-hero">
  <div>
    <span class="dashboard-kicker">Ringkasan aktivitas</span>
    <h1><?= esc($identitas['nama_sekolah'] ?? 'Sekolah') ?></h1>
    <p><?= esc($identitas['paket_pekerjaan'] ?? 'Belum ada paket pekerjaan') ?> · Tahun Anggaran <?= esc($identitas['tahun_anggaran'] ?? '') ?> · NPSN <?= esc($identitas['npsn'] ?? '') ?></p>
  </div>
  <div class="dashboard-aksi">
    <a class="tombol abu" href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a>
    <a class="tombol" href="<?= base_url('impor') ?>">+ Impor Dokumen</a>
  </div>
</section>
<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total RAB</div><div class="nilai"><?= $rp($totalRab) ?></div></div>
  <div class="kpi"><div class="label">Total SPK Terdaftar</div><div class="nilai"><?= $rp($totalSpk) ?></div></div>
  <div class="kpi"><div class="label">Realisasi Dibayar (bersih PPh 22)</div><div class="nilai"><?= $rp($realisasi) ?></div></div>
  <div class="kpi"><div class="label">Sisa Anggaran</div><div class="nilai"><?= $rp($sisa) ?></div></div>
</div>

<?php if ($tanpaSpk->j > 0 || $invoiceCek || $sisaNegatif || ! empty($tahap2JatuhTempo) || ! empty($pecahCek) || ! empty($masterKonflik) || ($tanpaHarga ?? 0) > 0 || ($tanpaTarif ?? 0) > 0): ?>
<div class="peringatan">
  <h2>Perlu perhatian</h2>
  <ul>
    <?php if (! empty($tahap2JatuhTempo)): ?>
      <li>Kemajuan fisik rata-rata sudah <strong><?= number_format((float) $fisikRata, 1, ',', '.') ?>%</strong> (≥ 50%) tetapi <strong>dana tahap 2 (30%) belum diterima</strong> —
        siapkan Laporan Kemajuan dan ajukan pencairan tahap 2 lewat menu <a href="<?= base_url('penerimaan-dana') ?>">Penerimaan Dana</a>.</li>
    <?php endif; ?>
    <?php if ($tanpaSpk->j > 0): ?>
      <li><strong><?= (int) $tanpaSpk->j ?> baris detail</strong> senilai <strong><?= $rp($tanpaSpk->t) ?></strong> belum bernomor SPK —
        <a href="<?= base_url('belum-bernomor') ?>">pasangkan nomornya</a>.</li>
    <?php endif; ?>
    <?php foreach ($invoiceCek as $c): ?>
      <li>Invoice No. <?= (int) $c['nomor_urut'] ?>: nilai <?= $rp($c['nilai']) ?> tidak sama dengan total SPK <?= (int) $c['spk_nomor'] ?> (<?= $rp($c['total_spk']) ?>) — cek dokumennya.</li>
    <?php endforeach; ?>
    <?php foreach ($sisaNegatif as $n): ?>
      <li>Sisa bahan negatif: <strong><?= esc($n['kode']) ?> <?= esc($n['nama']) ?></strong> — terpakai <?= rtrim(rtrim(number_format((float) $n['terpakai'], 2, ',', '.'), '0'), ',') ?> dari RAB <?= rtrim(rtrim(number_format((float) $n['qty_rab'], 2, ',', '.'), '0'), ',') ?>.</li>
    <?php endforeach; ?>
    <?php foreach ($pecahCek ?? [] as $p): ?>
      <li><?= esc($p) ?></li>
    <?php endforeach; ?>
    <?php if (($tanpaHarga ?? 0) > 0): ?>
      <li><strong><?= (int) $tanpaHarga ?> bahan</strong> belum punya harga acuan sehingga terhitung Rp 0 di kebutuhan &amp; rencana belanja —
        <a href="<?= base_url('bahan') ?>">isi lewat panel Kelengkapan Harga &amp; Tarif</a>.</li>
    <?php endif; ?>
    <?php if (($tanpaTarif ?? 0) > 0): ?>
      <li><strong><?= (int) $tanpaTarif ?> jenis upah</strong> belum punya tarif sehingga terhitung Rp 0 di kebutuhan upah —
        <a href="<?= base_url('bahan') ?>">isi lewat panel Kelengkapan Harga &amp; Tarif</a>.</li>
    <?php endif; ?>
    <?php foreach ($masterKonflik ?? [] as $k): ?>
      <li><strong>ALERT MASTER:</strong> <?= esc($k) ?> <a href="<?= base_url('bahan') ?>">Buka Master Bahan &amp; Upah</a></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if (! empty($semuaGedung)): ?>
<div class="panel">
  <h2>Pembangunan Gedung — Dari Dokumen Perencana</h2>
  <table data-paginasi="10" data-cari="Cari gedung…">
    <tr><th>Gedung</th><th class="angka">Nilai BOQ</th><th>Fisik</th><th>Aksi</th></tr>
    <?php foreach ($semuaGedung as $gd): ?>
    <tr>
      <td><b><?= esc($gd['nama']) ?></b></td>
      <td class="angka"><?= $rp($gd['nilai_boq']) ?></td>
      <td><div class="bar<?= $gd['persen_fisik'] >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= min(100, $gd['persen_fisik']) ?>%"></div></div><span class="kecil-teks"><?= number_format($gd['persen_fisik'], 1, ',', '.') ?>%</span></td>
      <td><a class="tombol kecil" href="<?= base_url('gedung/' . $gd['id']) ?>">Buka</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if (isset($verifikasi)): ?>
  <p class="kecil-teks" style="margin-bottom:0">Total BOQ <b><?= (int) $verifikasi['gedung'] ?> gedung aktif</b>: <b><?= $rp($verifikasi['total_boq']) ?></b> + biaya non-gedung <b><?= $rp($verifikasi['biaya_lain']) ?></b> = <b><?= $rp($verifikasi['total_semua']) ?></b> · Pagu anggaran: <b><?= $rp($verifikasi['pagu']) ?></b> · Selisih: <b><?= $rp($verifikasi['selisih']) ?></b><?php if ($verifikasi['pagu'] > 0 && $verifikasi['selisih'] < 0): ?> <span style="color:var(--ina-red-700,#a61e1e)"><b>(melebihi pagu)</b></span><?php elseif ($verifikasi['pagu'] > 0 && $verifikasi['selisih'] == 0): ?> <span style="color:var(--ina-green-700,#1e6b2a)"><b>(sesuai pagu)</b></span><?php endif; ?> — pantau pemakaiannya di <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a> dan <a href="<?= base_url('lpj') ?>">LPJ &amp; Realisasi</a>.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Ringkasan per SPK</h2>
  <table data-paginasi="10" data-cari="Cari SPK…">
    <tr><th>No. SPK</th><th>Tanggal</th><th>Penyedia</th><th>Paket</th><th class="angka">Jml Bahan</th><th class="angka">Nilai</th><th>Status</th></tr>
    <?php foreach ($perSpk as $s): ?>
    <tr>
      <td><a href="<?= base_url('spk') ?>"><?= (int) $s['nomor'] ?></a></td>
      <td><?= esc($s['tanggal'] ?? '—') ?></td>
      <td><?= esc($s['penyedia'] ?? '—') ?></td>
      <td><?= esc($s['paket'] ?? '—') ?></td>
      <td class="angka"><?= (int) $s['jumlah'] ?></td>
      <td class="angka"><?= $rp($s['nilai']) ?></td>
      <td><?= esc($s['status']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

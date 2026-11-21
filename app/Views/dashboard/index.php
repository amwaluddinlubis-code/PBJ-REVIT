<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2><?= esc($identitas['nama_sekolah'] ?? 'Sekolah') ?> — <?= esc($identitas['paket_pekerjaan'] ?? '') ?></h2>
  <div class="kecil-teks">Tahun Anggaran <?= esc($identitas['tahun_anggaran'] ?? '') ?> · NPSN <?= esc($identitas['npsn'] ?? '') ?></div>
</div>
<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total RAB</div><div class="nilai"><?= $rp($totalRab) ?></div></div>
  <div class="kpi"><div class="label">Total SPK Terdaftar</div><div class="nilai"><?= $rp($totalSpk) ?></div></div>
  <div class="kpi"><div class="label">Realisasi Dibayar (bersih PPh 22)</div><div class="nilai"><?= $rp($realisasi) ?></div></div>
  <div class="kpi"><div class="label">Sisa Anggaran</div><div class="nilai"><?= $rp($sisa) ?></div></div>
</div>

<?php if ($tanpaSpk->j > 0 || $invoiceCek || $sisaNegatif): ?>
<div class="peringatan">
  <h2>Perlu perhatian</h2>
  <ul>
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
  </ul>
</div>
<?php endif; ?>

<?php if (! empty($semuaGedung)): ?>
<div class="panel">
  <h2>Pembangunan Gedung — Dari Dokumen Perencana</h2>
  <table>
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
  <p class="kecil-teks" style="margin-bottom:0">Total anggaran (pagu): <b><?= $rp($identitas['total_anggaran'] ?? 0) ?></b> — pantau pemakaiannya di <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a> dan <a href="<?= base_url('lpj') ?>">LPJ &amp; Realisasi</a>.</p>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Ringkasan per SPK</h2>
  <table>
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

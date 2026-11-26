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
  <div class="kpi"><div class="label">Total RAB</div><div class="nilai"><?= rp($totalRab) ?></div></div>
  <div class="kpi"><div class="label">Total SPK Terdaftar</div><div class="nilai"><?= rp($totalSpk) ?></div></div>
  <div class="kpi"><div class="label">Realisasi Dibayar (bersih PPh 22)</div><div class="nilai"><?= rp($realisasi) ?></div></div>
  <div class="kpi"><div class="label">Sisa Anggaran</div><div class="nilai"><?= rp($sisa) ?></div></div>
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
      <li><strong><?= (int) $tanpaSpk->j ?> baris detail</strong> senilai <strong><?= rp($tanpaSpk->t) ?></strong> belum bernomor SPK —
        <a href="<?= base_url('belum-bernomor') ?>">pasangkan nomornya</a>.</li>
    <?php endif; ?>
    <?php foreach ($invoiceCek as $c): ?>
      <li>Invoice No. <?= (int) $c['nomor_urut'] ?>: nilai <?= rp($c['nilai']) ?> tidak sama dengan total SPK <?= (int) $c['spk_nomor'] ?> (<?= rp($c['total_spk']) ?>) — <a href="<?= base_url('invoice') ?>">periksa di Register Invoice</a>.</li>
    <?php endforeach; ?>
    <?php foreach ($sisaNegatif as $n): ?>
      <li>Sisa bahan negatif: <strong><?= esc($n['kode']) ?> <?= esc($n['nama']) ?></strong> — terpakai <?= rtrim(rtrim(number_format((float) $n['terpakai'], 2, ',', '.'), '0'), ',') ?> dari RAB <?= rtrim(rtrim(number_format((float) $n['qty_rab'], 2, ',', '.'), '0'), ',') ?> — <a href="<?= base_url('sisa-bahan') ?>">lihat di Sisa Bahan</a>.</li>
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

<?php if (! empty($panelGedung)): ?>
<details class="panel panel-lipat" data-lipat-id="dash-gedung" open>
  <summary>Pembangunan Gedung — Dari Dokumen Perencana</summary>
  <?php
  $selPerubahan = static function ($nilai, $dasar = null) {
      if (abs($nilai) < 0.5) {
          return '<span class="kecil-teks">—</span>';
      }
      $h = '<span class="' . ($nilai > 0 ? 'perubahan-buruk' : 'perubahan-baik') . '">' . ($nilai > 0 ? '+' : '−') . rp(abs($nilai)) . '</span>';
      if ($dasar !== null && $dasar > 0) {
          $h .= ' <span class="kecil-teks">(' . number_format(abs($nilai) / $dasar * 100, 1, ',', '.') . '%)</span>';
      }
      return $h;
  };
  $barisPanelGedung = [];
  foreach ($panelGedung as $gd) {
      $barisPanelGedung[] = ['sel' => [
          ['html' => '<b>' . esc($gd['nama']) . '</b>'],
          ['teks' => rp($gd['boq_asli']), 'angka' => true],
          ['teks' => rp($gd['nilai_analisa']), 'angka' => true],
          ['html' => $selPerubahan($gd['perubahan'], $gd['boq_asli']), 'angka' => true],
          ['html' => '<div class="bar' . ($gd['persen_fisik'] >= 99.5 ? ' hijau' : '') . '"><div style="width:' . min(100, $gd['persen_fisik']) . '%"></div></div><span class="kecil-teks">' . number_format($gd['persen_fisik'], 1, ',', '.') . '%</span>'],
          ['html' => '<a class="tombol kecil" href="' . base_url('gedung/' . $gd['id']) . '">Buka</a>'],
      ]];
  }
  $barisPanelGedung[] = ['total' => [
      ['html' => '<b>Total ' . count($panelGedung) . ' gedung</b>'],
      ['html' => '<b>' . rp($panelTotal['asli']) . '</b>', 'angka' => true],
      ['html' => '<b>' . rp($panelTotal['analisa']) . '</b>', 'angka' => true],
      ['html' => '<b>' . (abs($panelTotal['perubahan']) < 0.5 ? '—' : $selPerubahan($panelTotal['perubahan'])) . '</b>', 'angka' => true],
      '',
      '',
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari gedung…',
      'paginasi' => 10,
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Nilai BOQ Asli', 'angka' => true],
          ['judul' => 'Nilai Analisa', 'angka' => true],
          ['judul' => 'Perubahan', 'angka' => true],
          ['judul' => 'Fisik'],
          ['judul' => 'Aksi'],
      ],
      'baris' => $barisPanelGedung,
  ]);
  ?>
  <?php if (isset($verifikasi)): ?>
  <p class="kecil-teks" style="margin-bottom:0">Total BOQ <b><?= (int) $verifikasi['gedung'] ?> gedung aktif</b>: <b><?= rp($verifikasi['total_boq']) ?></b> + biaya non-gedung <b><?= rp($verifikasi['biaya_lain']) ?></b> = <b><?= rp($verifikasi['total_semua']) ?></b> · Pagu anggaran: <b><?= rp($verifikasi['pagu']) ?></b> · Selisih: <b><?= rp($verifikasi['selisih']) ?></b><?php if ($verifikasi['pagu'] > 0 && $verifikasi['selisih'] < 0): ?> <span class="perubahan-buruk"><b>(melebihi pagu)</b></span><?php elseif ($verifikasi['pagu'] > 0 && $verifikasi['selisih'] == 0): ?> <span class="perubahan-baik"><b>(sesuai pagu)</b></span><?php endif; ?> — pantau pemakaiannya di <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a> dan <a href="<?= base_url('lpj') ?>">LPJ &amp; Realisasi</a>.</p>
  <?php endif; ?>
</details>
<?php endif; ?>

<details class="panel panel-lipat" data-lipat-id="dash-biaya-lain" open>
  <summary>Biaya Non-Gedung</summary>
  <?php if (! empty($panelBiayaLain)): ?>
  <?php
  $barisBiayaLain = [];
  foreach ($panelBiayaLain as $bl) {
      $barisBiayaLain[] = ['sel' => [
          (string) $bl['kelompok'],
          (string) $bl['uraian'],
          ['teks' => rp((float) $bl['jumlah']), 'angka' => true],
      ]];
  }
  $barisBiayaLain[] = ['total' => [
      ['html' => '<b>Total Biaya Non-Gedung</b>', 'colspan' => 2],
      ['html' => '<b>' . rp($biayaNonGedung ?? 0) . '</b>', 'angka' => true],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari biaya non-gedung…',
      'kolom' => [
          ['judul' => 'Kelompok'],
          ['judul' => 'Uraian'],
          ['judul' => 'Jumlah', 'angka' => true],
      ],
      'baris' => $barisBiayaLain,
  ]);
  ?>
  <p class="kecil-teks" style="margin-bottom:0">Kelola rinciannya di <a href="<?= base_url('biaya-non-gedung') ?>">Biaya Non-Gedung</a>.</p>
  <?php else: ?>
  <p class="kecil-teks" style="margin:0">Belum ada biaya non-gedung. Tambahkan di <a href="<?= base_url('biaya-non-gedung') ?>">Biaya Non-Gedung</a>.</p>
  <?php endif; ?>
</details>

<details class="panel panel-lipat" data-lipat-id="dash-spk" open>
  <summary>Ringkasan per SPK</summary>
  <?php
  $barisPerSpkDash = [];
  foreach ($perSpk as $s) {
      $barisPerSpkDash[] = ['sel' => [
          ['html' => '<a href="' . base_url('spk') . '">' . (int) $s['nomor'] . '</a>'],
          (string) tgl($s['tanggal'] ?? null),
          (string) ($s['penyedia'] ?? '—'),
          (string) ($s['paket'] ?? '—'),
          ['teks' => (string) (int) $s['jumlah'], 'angka' => true],
          ['teks' => rp($s['nilai']), 'angka' => true],
          (string) $s['status'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari SPK…',
      'paginasi' => 10,
      'kolom' => [
          ['judul' => 'No. SPK'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Penyedia'],
          ['judul' => 'Paket'],
          ['judul' => 'Jml Bahan', 'angka' => true],
          ['judul' => 'Nilai', 'angka' => true],
          ['judul' => 'Status'],
      ],
      'baris' => $barisPerSpkDash,
  ]);
  ?>
</details>

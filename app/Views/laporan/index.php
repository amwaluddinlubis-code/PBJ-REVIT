<div class="kartu-grid">
  <div class="kartu"><span>Total RAB</span><strong><?= rp($totalRab) ?></strong></div>
  <div class="kartu"><span>Total SPK (bernomor)</span><strong><?= rp($totalSpk) ?></strong></div>
  <div class="kartu"><span>Total Invoice</span><strong><?= rp($totalInvoice) ?></strong></div>
  <div class="kartu"><span>Realisasi Bersih (stl. PPh 22)</span><strong><?= rp($realisasi) ?></strong></div>
  <div class="kartu"><span>Sisa Anggaran</span><strong><?= rp($sisa) ?></strong></div>
</div>

<div class="panel">
  <h2>Anggaran per Gedung (dari RAB)</h2>
  <?php
  $barisAnggaran = [];
  foreach ($gedung as $g) {
      $barisAnggaran[] = ['sel' => [
          (string) $g['label'],
          ['teks' => rp($g['anggaran']), 'angka' => true],
          ['teks' => number_format($g['persen'], 1, ',', '.') . '%', 'angka' => true],
      ]];
  }
  $barisAnggaran[] = ['total' => [
      ['teks' => 'TOTAL', 'gaya' => 'text-align:right'],
      ['teks' => rp($totalRab), 'angka' => true],
      '',
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari gedung…',
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Anggaran RAB', 'angka' => true],
          ['judul' => 'Porsi', 'angka' => true],
      ],
      'baris' => $barisAnggaran,
  ]);
  ?>
</div>

<div class="panel">
  <h2>Rekap per SPK</h2>
  <?php
  $barisPerSpk = [];
  foreach ($perSpk as $s) {
      $barisPerSpk[] = ['sel' => [
          (string) (int) $s['nomor'],
          (string) ($s['paket'] ?? '—'),
          (string) ($s['penyedia'] ?? '—'),
          (string) $s['status'],
          ['teks' => rp($s['nilai_spk']), 'angka' => true],
          ['teks' => rp($s['nilai_invoice']), 'angka' => true],
          ['teks' => (string) (int) $s['jumlah_bayar'], 'angka' => true],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari SPK…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'SPK'],
          ['judul' => 'Paket'],
          ['judul' => 'Penyedia'],
          ['judul' => 'Status'],
          ['judul' => 'Nilai SPK', 'angka' => true],
          ['judul' => 'Nilai Invoice', 'angka' => true],
          ['judul' => 'Sudah Dibayar (kali)', 'angka' => true],
      ],
      'baris' => $barisPerSpk,
  ]);
  ?>
</div>

<div class="panel">
  <h2>Cetak &amp; Unduh</h2>
  <div class="aksi md:col-span-2" style="margin:0">
    <a class="tombol" href="<?= base_url('laporan/cetak') ?>" target="_blank">Cetak Laporan</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-spk') ?>">Excel: Rekap SPK</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-material') ?>">Excel: Buku Material</a>
    <a class="tombol abu" href="<?= base_url('laporan/ekspor-sisa') ?>">Excel: Sisa Bahan</a>
  </div>
  <p class="kecil-teks">Semua angka dan unduhan mengikuti sekolah aktif: <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?>.</p>
</div>

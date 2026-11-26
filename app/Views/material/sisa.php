<?php /* Sisa Bahan: tabel daftar memakai helper tabel_bantu(). */ ?>
<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <p class="kecil-teks" style="margin-top:0">Qty RAB mengikuti kebutuhan analisa gedung didanai (sama seperti Master Bahan); Masuk SPK dari rincian SPK; stok gudang = penerimaan BAST dikurangi catatan keluar di <a href="<?= base_url('material') ?>">Buku Material</a>. Nama bahan membuka <a href="<?= base_url('bahan') ?>">Master Bahan</a>; kebutuhan &amp; belanja direncanakan di <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a>.<?php if (($jmlSembunyi ?? 0) > 0): ?> <?php if (! empty($tampilSemua)): ?>Menampilkan semua bahan, termasuk <?= (int) $jmlSembunyi ?> bahan tanpa kebutuhan. <a href="<?= base_url('sisa-bahan') ?>">Sembunyikan yang tanpa kebutuhan</a>.<?php else: ?><?= (int) $jmlSembunyi ?> bahan tanpa kebutuhan disembunyikan. <a href="<?= base_url('sisa-bahan?semua=1') ?>">Tampilkan semua</a>.<?php endif; ?><?php endif; ?></p>
  <?php
  $barisSisa = [];
  foreach ($bahan as $b) {
      $sisa = (float) $b['qty_rab'] - (float) $b['masuk_spk'];
      $stok = (float) $b['gudang_masuk'] - (float) $b['gudang_keluar'];
      $status = $sisa < 0 ? 'Melebihi RAB' : (((float) $b['qty_rab'] == 0 && (float) $b['masuk_spk'] == 0) ? 'Tidak ada kebutuhan' : ($sisa == 0 ? 'Sudah penuh' : ($b['masuk_spk'] > 0 ? 'Sebagian' : 'Belum masuk SPK')));
      $barisSisa[] = [
          'class' => $sisa < 0 ? 'negatif' : '',
          'sel' => [
              (string) $b['kode'],
              ['html' => '<a href="' . base_url('bahan') . '">' . esc($b['nama']) . '</a>'],
              ['teks' => $fmt($b['qty_rab']), 'angka' => true],
              ['teks' => $fmt($b['masuk_spk']), 'angka' => true],
              ['teks' => $fmt($sisa), 'angka' => true],
              $status,
              ['teks' => $fmt($stok) . ' ' . ($b['satuan'] ?? ''), 'angka' => true],
          ],
      ];
  }
  echo tabel_bantu([
      'cari' => 'Cari bahan…',
      'paginasi' => 10,
      'kolom' => [
          ['judul' => 'Kode'],
          ['judul' => 'Nama Bahan'],
          ['judul' => 'Qty RAB', 'angka' => true],
          ['judul' => 'Masuk SPK', 'angka' => true],
          ['judul' => 'Sisa vs RAB', 'angka' => true],
          ['judul' => 'Status'],
          ['judul' => 'Stok Gudang (BAST − Keluar)', 'angka' => true],
      ],
      'baris' => $barisSisa,
  ]);
  ?>
</div>

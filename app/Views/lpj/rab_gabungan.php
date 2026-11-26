<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); ?>
<div class="panel">
  <h2>RAB Gabungan Beberapa Gedung</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">RAB per gedung tetap tersedia di LPJ Gedung masing-masing. Halaman ini menggabungkan RAB dari gedung-gedung yang dipilih: pekerjaan yang sama (nama dan harga satuan sama) dijumlahkan volumenya lintas gedung. Harga satuan mengikuti analisa yang mengacu Master Bahan &amp; Master Upah.</p>
  <form method="get" action="<?= base_url('lpj/rab-gabungan') ?>">
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <?php foreach ($semua as $g): ?>
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="gedung[]" value="<?= (int) $g['id'] ?>" <?= in_array((int) $g['id'], $ids, true) ? 'checked' : '' ?>> <?= esc($g['nama']) ?></label>
      <?php endforeach; ?>
    </div>
    <div class="aksi" style="justify-content:flex-start">
      <button type="submit">Tampilkan Gabungan</button>
      <a class="tombol abu" href="<?= base_url('lpj/cetak/rab-gabungan?' . http_build_query(['gedung' => $ids])) ?>" target="_blank">Cetak RAB Gabungan</a>
    </div>
  </form>
</div>

<div class="panel">
  <h2>Ringkasan per Gedung Terpilih</h2>
  <?php
  $totBoq = 0;
  $barisRingkas = [];
  foreach ($perGedung as $g) {
      $totBoq += (float) $g['nilai_boq'];
      $barisRingkas[] = ['sel' => [
          (string) $g['nama'],
          ['teks' => rp($g['nilai_boq']), 'angka' => true],
          ['teks' => rp($g['nilai_realisasi']), 'angka' => true],
          ['teks' => number_format((float) $g['persen_fisik'], 1, ',', '.') . '%', 'angka' => true],
          ['html' => '<a href="' . base_url('lpj/gedung/' . $g['id']) . '">RAB per Gedung</a>'],
      ]];
  }
  $barisRingkas[] = ['total' => [
      ['teks' => 'Total BOQ terpilih', 'gaya' => 'text-align:right'],
      ['teks' => rp($totBoq), 'angka' => true],
      ['teks' => '', 'colspan' => 3],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari gedung…',
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Nilai BOQ (RAB)', 'angka' => true],
          ['judul' => 'Realisasi', 'angka' => true],
          ['judul' => 'Fisik', 'angka' => true],
          ['judul' => ''],
      ],
      'baris' => $barisRingkas,
  ]);
  ?>
</div>

<div class="panel">
  <h2>Pekerjaan Gabungan (<?= count($gabungan) ?> jenis pekerjaan)</h2>
  <?php
  $barisGabungan = [];
  foreach ($gabungan as $p) {
      $barisGabungan[] = ['sel' => [
          (string) $p['nama'],
          (string) ($p['satuan'] ?? ''),
          ['teks' => rp($p['harga_satuan']), 'angka' => true],
          ['teks' => $angka($p['volume']), 'angka' => true],
          ['teks' => rp($p['jumlah']), 'angka' => true],
          ['teks' => (string) (int) $p['jumlah_gedung'], 'angka' => true],
      ]];
  }
  if ($gabungan === []) {
      $barisGabungan[] = ['sel' => [['teks' => 'Belum ada pekerjaan pada gedung terpilih.', 'colspan' => 6]]];
  }
  $barisGabungan[] = ['total' => [
      ['teks' => 'TOTAL RAB GABUNGAN', 'colspan' => 4, 'gaya' => 'text-align:right'],
      ['teks' => rp($total), 'angka' => true],
      '',
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari pekerjaan…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Pekerjaan'],
          ['judul' => 'Satuan'],
          ['judul' => 'Harga Satuan', 'angka' => true],
          ['judul' => 'Volume Gabungan', 'angka' => true],
          ['judul' => 'Jumlah', 'angka' => true],
          ['judul' => 'Di Gedung', 'angka' => true],
      ],
      'baris' => $barisGabungan,
  ]);
  ?>
  <p class="kecil-teks">Total RAB gabungan dihitung dari pekerjaan yang digabung menurut nama + harga satuan; total BOQ per gedung pada ringkasan di atas adalah angka resmi per gedung dari dokumen perencana.</p>
</div>

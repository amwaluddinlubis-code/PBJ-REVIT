<?php /* Partial gedung/detail tab Bahan: panel Kebutuhan Bahan tertaut dari BOQ x koefisien AHSP. Tabel dirender helper tabel_bantu(). */ ?><div class="panel">
  <h2>Kebutuhan Bahan — Dihitung dari BOQ × Koefisien AHSP</h2>
  <div class="bungkus-tabel"><?php
  $barisBahan = [];
  $totalNilai = 0;
  foreach ($kebutuhan['tertaut'] as $b) {
      $nilai = (float) $b['qty_butuh'] * (float) $b['harga'];
      $totalNilai += $nilai;
      $sisa = (float) $b['qty_butuh'] - (float) $b['dipesan'];
      $barisBahan[] = ['sel' => [
          (string) $b['kode'],
          ['html' => esc($b['nama'])
              . (! empty($b['dikonversi']) ? ' <span class="lencana kuning" title="Dikonversi dari satuan AHSP ke satuan beli">dikonversi</span>' : '')
              . (! empty($b['satuan_beda']) ? ' <span class="lencana abu" title="Satuan komponen AHSP berbeda dari satuan bahan master — periksa kewajaran jumlahnya">satuan beda</span>' : ''),
              'class' => 'potong', 'title' => (string) $b['nama']],
          ['teks' => number_format((float) $b['qty_butuh'], 2, ',', '.'), 'angka' => true],
          (string) $b['satuan'],
          ['teks' => number_format((float) $b['harga'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format($nilai, 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $b['dipesan'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format($sisa, 2, ',', '.'), 'angka' => true],
      ]];
  }
  $barisBahan[] = ['total' => [
      ['teks' => 'Total Nilai Kebutuhan Bahan Tertaut', 'colspan' => 5],
      ['teks' => number_format($totalNilai, 0, ',', '.'), 'angka' => true],
      ['teks' => '', 'colspan' => 2],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari…',
      'paginasi' => 25,
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Kode'],
          ['judul' => 'Bahan'],
          ['judul' => 'Kebutuhan', 'angka' => true],
          ['judul' => 'Satuan'],
          ['judul' => 'Harga Acuan', 'angka' => true],
          ['judul' => 'Nilai Kebutuhan', 'angka' => true],
          ['judul' => 'Sudah Dipesan*', 'angka' => true],
          ['judul' => 'Sisa', 'angka' => true],
      ],
      'baris' => $barisBahan,
  ]);
  ?></div>
  <p class="kecil-teks">*Sudah dipesan = jumlah pada SPK yang tertaut ke gedung ini. Harga acuan mengikuti jenjang aplikasi (negosiasi → penawaran → RAB). Kebutuhan semen/agregat dikonversi dari kg AHSP: semen ÷50 per zak, pasir ÷1.400 per m³, kerikil/batu ÷1.600 per m³.</p>
</div>

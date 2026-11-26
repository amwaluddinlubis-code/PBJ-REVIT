<?php /* Partial gedung/detail tab Upah: panel Kebutuhan Upah dari BOQ x koefisien tenaga. Tabel dirender helper tabel_bantu(). */ ?><div class="panel">
  <h2>Kebutuhan Upah — Dihitung dari BOQ × Koefisien Tenaga</h2>
  <div class="bungkus-tabel"><?php
  $barisUpah = [];
  $totalU = 0;
  foreach ($kebutuhan_upah as $u) {
      $nilai = (float) $u['orang_hari'] * (float) $u['tarif'];
      $totalU += $nilai;
      $byr = $upah_dibayar[$u['jenis']] ?? null;
      $barisUpah[] = ['sel' => [
          (string) $u['jenis'],
          ['teks' => number_format((float) $u['orang_hari'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['tarif'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format($nilai, 0, ',', '.'), 'angka' => true],
          ['teks' => $byr ? number_format((float) $byr['oh'], 2, ',', '.') : '—', 'angka' => true],
          ['teks' => $byr ? number_format((float) $byr['jml'], 0, ',', '.') : '—', 'angka' => true],
      ]];
  }
  $barisUpah[] = ['total' => [
      ['teks' => 'Total Nilai Kebutuhan Upah', 'colspan' => 3],
      ['teks' => number_format($totalU, 0, ',', '.'), 'angka' => true],
      ['teks' => '', 'colspan' => 2],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari jenis tenaga…',
      'paginasi' => 25,
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Jenis Tenaga'],
          ['judul' => 'Kebutuhan (OH)', 'angka' => true],
          ['judul' => 'Tarif / OH', 'angka' => true],
          ['judul' => 'Nilai Kebutuhan', 'angka' => true],
          ['judul' => 'Sudah Dibayar (OH)', 'angka' => true],
          ['judul' => 'Sudah Dibayar (Rp)', 'angka' => true],
      ],
      'baris' => $barisUpah,
  ]);
  ?></div>
  <p class="kecil-teks">OH = orang-hari. Pembayaran upah dicatat di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a>. Tarif mengikuti daftar upah dokumen perencana (berbasis UMK) dan dapat diubah di sana.</p>
</div>

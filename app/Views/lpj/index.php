<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total Anggaran</div><div class="nilai"><?= $anggaran > 0 ? rp($anggaran) : 'Belum diatur' ?></div></div>
  <div class="kpi"><div class="label">Total BOQ Gedung</div><div class="nilai"><?= rp(array_sum(array_column($rekap, 'nilai_boq'))) ?></div></div>
  <div class="kpi"><div class="label">Belanja Terbayar</div><div class="nilai"><?= rp($terbayar) ?></div></div>
  <div class="kpi"><div class="label">Sisa Pagu</div><div class="nilai"><?= $sisa_pagu !== null ? rp($sisa_pagu) : '—' ?></div></div>
</div>
<?php if ($tanpa_gedung_nilai > 0): ?>
<div class="peringatan">
  <h2>Belanja tanpa tautan gedung</h2>
  <p style="margin:0"><?= (int) $tanpa_gedung_spk ?> SPK senilai <b><?= rp($tanpa_gedung_nilai) ?></b> belum tertaut ke gedung mana pun (termasuk SPK lama sebelum modul gedung ada). Tautkan lewat Ubah SPK agar LPJ per gedung lengkap.</p>
</div>
<?php endif; ?>
<div class="panel">
  <h2>Rekap LPJ per Gedung <a class="tombol kecil" style="float:right" href="<?= base_url('lpj/cetak') ?>" target="_blank">Cetak LPJ Sekolah</a></h2>
  <?php
  $fmtAngka = static fn ($n) => number_format((float) $n, 0, ',', '.');
  $barisRekap = [];
  foreach ($rekap as $g) {
      $barisRekap[] = ['sel' => [
          ['html' => '<b>' . esc($g['nama']) . '</b>'],
          ['teks' => $fmtAngka($g['nilai_boq']), 'angka' => true],
          ['html' => '<div class="bar' . ($g['persen_fisik'] >= 99.5 ? ' hijau' : '') . '"><div style="width:' . $g['persen_fisik'] . '%"></div></div><span class="kecil-teks">' . number_format($g['persen_fisik'], 1, ',', '.') . '%</span>'],
          ['teks' => $fmtAngka($g['nilai_kebutuhan_bahan']), 'angka' => true],
          ['teks' => $fmtAngka($g['nilai_spk']), 'angka' => true],
          ['teks' => $fmtAngka($g['invoice_terbayar']), 'angka' => true],
          ['teks' => $fmtAngka($g['upah_dibayar']), 'angka' => true],
          ['html' => '<a class="tombol kecil" href="' . base_url('lpj/gedung/' . $g['id']) . '">LPJ</a>'],
      ]];
  }
  $barisRekap[] = ['total' => [
      'Total',
      ['teks' => $fmtAngka(array_sum(array_column($rekap, 'nilai_boq'))), 'angka' => true],
      '',
      ['teks' => $fmtAngka(array_sum(array_column($rekap, 'nilai_kebutuhan_bahan'))), 'angka' => true],
      ['teks' => $fmtAngka(array_sum(array_column($rekap, 'nilai_spk'))), 'angka' => true],
      ['teks' => $fmtAngka(array_sum(array_column($rekap, 'invoice_terbayar'))), 'angka' => true],
      ['teks' => $fmtAngka(array_sum(array_column($rekap, 'upah_dibayar'))), 'angka' => true],
      '',
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari gedung…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Nilai BOQ', 'angka' => true],
          ['judul' => 'Fisik'],
          ['judul' => 'Kebutuhan Bahan', 'angka' => true],
          ['judul' => 'SPK (Dipesan)', 'angka' => true],
          ['judul' => 'Invoice Terbayar', 'angka' => true],
          ['judul' => 'Upah Dibayar', 'angka' => true],
          ['judul' => 'Aksi'],
      ],
      'baris' => $barisRekap,
  ]);
  ?>
  <p class="kecil-teks">Kebutuhan bahan dihitung dari BOQ × koefisien AHSP pada harga acuan. Kolom SPK/Invoice/Upah adalah realisasi belanja yang tertaut ke masing-masing gedung.</p>
</div>

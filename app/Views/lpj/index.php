<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total Anggaran</div><div class="nilai"><?= $anggaran > 0 ? $rp($anggaran) : 'Belum diatur' ?></div></div>
  <div class="kpi"><div class="label">Total BOQ Gedung</div><div class="nilai"><?= $rp(array_sum(array_column($rekap, 'nilai_boq'))) ?></div></div>
  <div class="kpi"><div class="label">Belanja Terbayar</div><div class="nilai"><?= $rp($terbayar) ?></div></div>
  <div class="kpi"><div class="label">Sisa Pagu</div><div class="nilai"><?= $sisa_pagu !== null ? $rp($sisa_pagu) : '—' ?></div></div>
</div>
<?php if ($tanpa_gedung_nilai > 0): ?>
<div class="peringatan">
  <h2>Belanja tanpa tautan gedung</h2>
  <p style="margin:0"><?= (int) $tanpa_gedung_spk ?> SPK senilai <b><?= $rp($tanpa_gedung_nilai) ?></b> belum tertaut ke gedung mana pun (termasuk SPK lama sebelum modul gedung ada). Tautkan lewat Ubah SPK agar LPJ per gedung lengkap.</p>
</div>
<?php endif; ?>
<div class="panel">
  <h2>Rekap LPJ per Gedung <a class="tombol kecil" style="float:right" href="<?= base_url('lpj/cetak') ?>" target="_blank">Cetak LPJ Sekolah</a></h2>
  <table data-paginasi="25" data-cari="Cari gedung…">
    <tr><th>Gedung</th><th class="angka">Nilai BOQ</th><th>Fisik</th><th class="angka">Kebutuhan Bahan</th><th class="angka">SPK (Dipesan)</th><th class="angka">Invoice Terbayar</th><th class="angka">Upah Dibayar</th><th>Aksi</th></tr>
    <?php foreach ($rekap as $g): ?>
    <tr>
      <td><b><?= esc($g['nama']) ?></b></td>
      <td class="angka"><?= number_format((float) $g['nilai_boq'], 0, ',', '.') ?></td>
      <td><div class="bar<?= $g['persen_fisik'] >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= $g['persen_fisik'] ?>%"></div></div><span class="kecil-teks"><?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</span></td>
      <td class="angka"><?= number_format((float) $g['nilai_kebutuhan_bahan'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $g['nilai_spk'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $g['invoice_terbayar'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $g['upah_dibayar'], 0, ',', '.') ?></td>
      <td><a class="tombol kecil" href="<?= base_url('lpj/gedung/' . $g['id']) ?>">LPJ</a></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total">
      <td>Total</td>
      <td class="angka"><?= number_format(array_sum(array_column($rekap, 'nilai_boq')), 0, ',', '.') ?></td>
      <td></td>
      <td class="angka"><?= number_format(array_sum(array_column($rekap, 'nilai_kebutuhan_bahan')), 0, ',', '.') ?></td>
      <td class="angka"><?= number_format(array_sum(array_column($rekap, 'nilai_spk')), 0, ',', '.') ?></td>
      <td class="angka"><?= number_format(array_sum(array_column($rekap, 'invoice_terbayar')), 0, ',', '.') ?></td>
      <td class="angka"><?= number_format(array_sum(array_column($rekap, 'upah_dibayar')), 0, ',', '.') ?></td>
      <td></td>
    </tr>
  </table>
  <p class="kecil-teks">Kebutuhan bahan dihitung dari BOQ × koefisien AHSP pada harga acuan. Kolom SPK/Invoice/Upah adalah realisasi belanja yang tertaut ke masing-masing gedung.</p>
</div>

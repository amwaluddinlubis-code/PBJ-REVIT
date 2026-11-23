<?php /* Partial gedung/detail tab Bahan: panel Kebutuhan Bahan tertaut dari BOQ x koefisien AHSP. */ ?><div class="panel">
  <h2>Kebutuhan Bahan — Dihitung dari BOQ × Koefisien AHSP</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari…">
    <tr><th>Kode</th><th>Bahan</th><th class="angka">Kebutuhan</th><th>Satuan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dipesan*</th><th class="angka">Sisa</th></tr>
    <?php $totalNilai = 0; foreach ($kebutuhan['tertaut'] as $b): $nilai = (float) $b['qty_butuh'] * (float) $b['harga']; $totalNilai += $nilai; $sisa = (float) $b['qty_butuh'] - (float) $b['dipesan']; ?>
    <tr>
      <td><?= esc($b['kode']) ?></td>
      <td class="potong" title="<?= esc($b['nama']) ?>"><?= esc($b['nama']) ?><?= ! empty($b['dikonversi']) ? ' <span class="lencana kuning" title="Dikonversi dari satuan AHSP ke satuan beli">dikonversi</span>' : '' ?><?= ! empty($b['satuan_beda']) ? ' <span class="lencana abu" title="Satuan komponen AHSP berbeda dari satuan bahan master — periksa kewajaran jumlahnya">satuan beda</span>' : '' ?></td>
      <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?></td>
      <td><?= esc($b['satuan']) ?></td>
      <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="5">Total Nilai Kebutuhan Bahan Tertaut</td><td class="angka"><?= number_format($totalNilai, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table></div>
  <p class="kecil-teks">*Sudah dipesan = jumlah pada SPK yang tertaut ke gedung ini. Harga acuan mengikuti jenjang aplikasi (negosiasi → penawaran → RAB). Kebutuhan semen/agregat dikonversi dari kg AHSP: semen ÷50 per zak, pasir ÷1.400 per m³, kerikil/batu ÷1.600 per m³.</p>
</div>

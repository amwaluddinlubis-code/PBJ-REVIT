<?php /* Partial gedung/detail tab Upah: panel Kebutuhan Upah dari BOQ x koefisien tenaga. */ ?><div class="panel">
  <h2>Kebutuhan Upah — Dihitung dari BOQ × Koefisien Tenaga</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari jenis tenaga…">
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Tarif / OH</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dibayar (OH)</th><th class="angka">Sudah Dibayar (Rp)</th></tr>
    <?php $totalU = 0; foreach ($kebutuhan_upah as $u): $nilai = (float) $u['orang_hari'] * (float) $u['tarif']; $totalU += $nilai; $byr = $upah_dibayar[$u['jenis']] ?? null; ?>
    <tr>
      <td><?= esc($u['jenis']) ?></td>
      <td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['oh'], 2, ',', '.') : '—' ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['jml'], 0, ',', '.') : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="3">Total Nilai Kebutuhan Upah</td><td class="angka"><?= number_format($totalU, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table></div>
  <p class="kecil-teks">OH = orang-hari. Pembayaran upah dicatat di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a>. Tarif mengikuti daftar upah dokumen perencana (berbasis UMK) dan dapat diubah di sana.</p>
</div>


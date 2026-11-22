<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>
<table data-cari="Cari pekerjaan…">
  <tr><th style="width:36px">Buang</th><th>Pekerjaan</th><th>Kode Analisa</th><th class="angka">Volume</th><th>Satuan</th><th class="angka">Jumlah</th><th></th></tr>
  <?php foreach ($rows as $p): $nol = (float) $p['volume'] <= 0; $adaReal = (int) $p['punya_realisasi'] > 0; ?>
  <tr>
    <td><input type="checkbox" name="pekerjaan[]" value="<?= (int) $p['id'] ?>" <?= $nol && ! $adaReal ? 'checked' : '' ?>></td>
    <td><?= esc($p['nama']) ?></td>
    <td><?= esc($p['kode_analisa'] ?? '') ?></td>
    <td class="angka"><?= $angka($p['volume']) ?></td>
    <td><?= esc($p['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($p['jumlah']) ?></td>
    <td><?php if ($adaReal): ?><span class="lencana kuning">sudah ada realisasi</span><?php elseif (! $nol): ?><span class="lencana abu">volume pecahan — periksa</span><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table>

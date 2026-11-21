<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div style="margin-bottom:12px">
  <span class="lencana biru">Nilai BOQ <b>&nbsp;<?= $rp($g['nilai_boq']) ?></b></span>
  <span class="lencana hijau">Fisik <b>&nbsp;<?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</b></span>
  <span class="lencana abu">Kebutuhan Bahan <b>&nbsp;<?= $rp($g['nilai_kebutuhan_bahan']) ?></b></span>
  <span class="lencana abu">SPK Dipesan <b>&nbsp;<?= $rp($g['nilai_spk']) ?></b></span>
  <a class="tombol kecil" style="float:right" href="<?= base_url('lpj/cetak/gedung/' . $g['id']) ?>" target="_blank">Cetak LPJ Gedung</a>
  <a class="tombol kecil abu" style="float:right;margin-right:6px" href="<?= base_url('gedung/' . $g['id']) ?>">Ke Menu Gedung</a>
</div>

<div class="panel">
  <h2>Kemajuan Pekerjaan</h2>
  <table data-paginasi="25">
    <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th>% </th><th class="angka">Nilai BOQ</th><th class="angka">Nilai Realisasi</th></tr>
    <?php $babLalu = null; foreach ($pekerjaan as $p): $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0; $nilaiReal = (float) $p['volume_terealisasi'] * (float) $p['harga_satuan']; ?>
      <?php if ($p['bab'] !== $babLalu): $babLalu = $p['bab']; ?>
      <tr><td colspan="6" style="font-weight:600;background:#fcfcfd"><?= esc($babLalu ?: '—') ?></td></tr>
      <?php endif; ?>
      <tr>
        <td><?= esc($p['nama']) ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume_terealisasi'], 3, ',', '.'), '0'), ',') ?></td>
        <td><div class="bar<?= $persen >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= $persen ?>%"></div></div></td>
        <td class="angka"><?= number_format((float) $p['jumlah'], 0, ',', '.') ?></td>
        <td class="angka"><?= number_format($nilaiReal, 0, ',', '.') ?></td>
      </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="4">Total</td><td class="angka"><?= number_format((float) $g['nilai_boq'], 0, ',', '.') ?></td><td class="angka"><?= number_format((float) $g['nilai_realisasi'], 0, ',', '.') ?></td></tr>
  </table>
</div>

<div class="panel">
  <h2>Bahan: Kebutuhan vs Dipesan</h2>
  <table data-paginasi="25">
    <tr><th>Bahan</th><th class="angka">Kebutuhan</th><th class="angka">Dipesan (SPK)</th><th class="angka">Sisa</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Dipesan</th></tr>
    <?php foreach ($kebutuhan['tertaut'] as $b): ?>
    <tr>
      <td><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?></td>
      <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?> <?= esc($b['satuan']) ?></td>
      <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['qty_butuh'] - (float) $b['dipesan'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['dipesan'] * (float) $b['harga'], 0, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="panel">
  <h2>Upah: Kebutuhan vs Dibayar</h2>
  <table>
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Tarif</th><th class="angka">Nilai Kebutuhan</th></tr>
    <?php $tot = 0; foreach ($kebutuhan_upah as $u): $n = (float) $u['orang_hari'] * (float) $u['tarif']; $tot += $n; ?>
    <tr><td><?= esc($u['jenis']) ?></td><td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td><td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td><td class="angka"><?= number_format($n, 0, ',', '.') ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="3">Total kebutuhan upah · sudah dibayar <?= $rp($g['upah_dibayar']) ?></td><td class="angka"><?= number_format($tot, 0, ',', '.') ?></td></tr>
  </table>
</div>

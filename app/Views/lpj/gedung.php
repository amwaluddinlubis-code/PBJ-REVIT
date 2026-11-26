<div style="margin-bottom:12px">
  <span class="lencana biru">Nilai BOQ <b>&nbsp;<?= rp($g['nilai_boq']) ?></b></span>
  <span class="lencana hijau">Fisik <b>&nbsp;<?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</b></span>
  <span class="lencana abu">Kebutuhan Bahan <b>&nbsp;<?= rp($g['nilai_kebutuhan_bahan']) ?></b></span>
  <span class="lencana abu">SPK Dipesan <b>&nbsp;<?= rp($g['nilai_spk']) ?></b></span>
  <a class="tombol kecil" style="float:right" href="<?= base_url('lpj/cetak/gedung/' . $g['id']) ?>" target="_blank">Cetak LPJ Gedung</a>
  <a class="tombol kecil abu" style="float:right;margin-right:6px" href="<?= base_url('dokumen/cetak-bast-aset/' . $g['id']) ?>" target="_blank">Cetak BAST Aset</a>
  <a class="tombol kecil abu" style="float:right;margin-right:6px" href="<?= base_url('gedung/' . $g['id']) ?>">Ke Menu Gedung</a>
</div>

<div class="panel">
  <h2>Kemajuan Pekerjaan</h2>
  <table data-paginasi="25" data-cari="Cari…">
    <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th>% </th><th class="angka">Nilai BOQ</th><th class="angka">Nilai Realisasi</th></tr>
    <?php $babLalu = null; foreach ($pekerjaan as $p): $volB = (float) ($p['volume_revisi'] ?? $p['volume']); $persen = $volB > 0 ? min(100, (float) $p['volume_terealisasi'] / $volB * 100) : 0; $nilaiReal = (float) $p['volume_terealisasi'] * (float) ($p['harga_revisi'] ?? $p['harga_satuan']); ?>
      <?php if ($p['bab'] !== $babLalu): $babLalu = $p['bab']; ?>
      <tr><td colspan="6" style="font-weight:600;background:#fcfcfd"><?= esc($babLalu ?: '—') ?></td></tr>
      <?php endif; ?>
      <tr>
        <td><?= esc($p['nama']) ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format($volB, 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
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
  <?php
  $barisBahanLpj = [];
  foreach ($kebutuhan['tertaut'] as $b) {
      $barisBahanLpj[] = ['sel' => [
          $b['kode'] . ' — ' . $b['nama'],
          ['teks' => number_format((float) $b['qty_butuh'], 2, ',', '.') . ' ' . ($b['satuan'] ?? ''), 'angka' => true],
          ['teks' => number_format((float) $b['dipesan'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $b['qty_butuh'] - (float) $b['dipesan'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $b['harga'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $b['dipesan'] * (float) $b['harga'], 0, ',', '.'), 'angka' => true],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Bahan'],
          ['judul' => 'Kebutuhan', 'angka' => true],
          ['judul' => 'Dipesan (SPK)', 'angka' => true],
          ['judul' => 'Sisa', 'angka' => true],
          ['judul' => 'Harga Acuan', 'angka' => true],
          ['judul' => 'Nilai Dipesan', 'angka' => true],
      ],
      'baris' => $barisBahanLpj,
  ]);
  ?>
</div>

<div class="panel">
  <h2>Upah: Kebutuhan vs Dibayar</h2>
  <?php
  $totUpah = 0;
  $barisUpahLpj = [];
  foreach ($kebutuhan_upah as $u) {
      $n = (float) $u['orang_hari'] * (float) $u['tarif'];
      $totUpah += $n;
      $barisUpahLpj[] = ['sel' => [
          (string) $u['jenis'],
          ['teks' => number_format((float) $u['orang_hari'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['tarif'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format($n, 0, ',', '.'), 'angka' => true],
      ]];
  }
  $barisUpahLpj[] = ['total' => [
      ['teks' => 'Total kebutuhan upah · sudah dibayar ' . rp($g['upah_dibayar']), 'colspan' => 3],
      ['teks' => number_format($totUpah, 0, ',', '.'), 'angka' => true],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari jenis tenaga…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Jenis Tenaga'],
          ['judul' => 'Kebutuhan (OH)', 'angka' => true],
          ['judul' => 'Tarif', 'angka' => true],
          ['judul' => 'Nilai Kebutuhan', 'angka' => true],
      ],
      'baris' => $barisUpahLpj,
  ]);
  ?>
</div>

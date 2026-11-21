<?php $rp = static fn ($n) => $n === null ? '—' : 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <p>Negosiasi dilakukan dengan penyedia pemilik <strong>Harga Terpilih</strong> (survei termurah). Harga Penawaran terisi awal dari harga terpilih; isi Harga Negosiasi bila ada kesepakatan lebih rendah. Total dan Hemat dihitung dari qty RAB.</p>
  <p class="kecil-teks">Penyedia default: <?= esc($identitas['penyedia_default_nama'] ?: '— (belum diisi di Pengaturan)') ?></p>
  <form method="post" action="<?= base_url('negosiasi/simpan') ?>">
    <?= csrf_field() ?>
    <div style="overflow-x:auto">
    <table data-paginasi="25">
      <tr><th>Kode</th><th>Nama Bahan</th><th class="angka">Qty RAB</th><th class="angka">Harga Terpilih</th><th class="angka">Harga Penawaran</th><th class="angka">Harga Negosiasi</th><th class="angka">Total Penawaran</th><th class="angka">Total Negosiasi</th><th class="angka">Hemat</th></tr>
      <?php $tPen = 0; $tNeg = 0; foreach ($bahan as $b):
        $qty = (float) $b['qty_total'];
        $pen = $b['penawaran']; $neg = $b['nego'] ?? $pen;
        $totPen = $pen !== null ? $qty * (float) $pen : null;
        $totNeg = $neg !== null ? $qty * (float) $neg : null;
        if ($totPen !== null) $tPen += $totPen;
        if ($totNeg !== null) $tNeg += $totNeg;
      ?>
      <tr>
        <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td>
        <td class="angka"><?= number_format($qty, 2, ',', '.') ?></td>
        <td class="angka"><?= $rp($b['terpilih']) ?></td>
        <td class="angka"><input type="number" name="penawaran[<?= (int) $b['id'] ?>]" min="0" step="1" value="<?= esc($pen ?? '') ?>" style="width:120px"></td>
        <td class="angka"><input type="number" name="nego[<?= (int) $b['id'] ?>]" min="0" step="1" value="<?= esc($b['nego'] ?? '') ?>" style="width:120px"></td>
        <td class="angka"><?= $rp($totPen) ?></td>
        <td class="angka"><?= $rp($totNeg) ?></td>
        <td class="angka"><?= ($totPen !== null && $totNeg !== null) ? $rp($totPen - $totNeg) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="baris-total"><th colspan="6" style="text-align:right">TOTAL</th><th class="angka"><?= $rp($tPen) ?></th><th class="angka"><?= $rp($tNeg) ?></th><th class="angka"><?= $rp($tPen - $tNeg) ?></th></tr>
    </table>
    </div>
    <div class="aksi md:col-span-2"><button type="submit">Simpan Negosiasi</button></div>
  </form>
</div>

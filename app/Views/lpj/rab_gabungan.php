<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); ?>
<div class="panel">
  <h2>RAB Gabungan Beberapa Gedung</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">RAB per gedung tetap tersedia di LPJ Gedung masing-masing. Halaman ini menggabungkan RAB dari gedung-gedung yang dipilih: pekerjaan yang sama (nama dan harga satuan sama) dijumlahkan volumenya lintas gedung. Harga satuan mengikuti analisa yang mengacu Master Bahan &amp; Master Upah.</p>
  <form method="get" action="<?= base_url('lpj/rab-gabungan') ?>">
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <?php foreach ($semua as $g): ?>
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="gedung[]" value="<?= (int) $g['id'] ?>" <?= in_array((int) $g['id'], $ids, true) ? 'checked' : '' ?>> <?= esc($g['nama']) ?></label>
      <?php endforeach; ?>
    </div>
    <div class="aksi" style="justify-content:flex-start">
      <button type="submit">Tampilkan Gabungan</button>
      <a class="tombol abu" href="<?= base_url('lpj/cetak/rab-gabungan?' . http_build_query(['gedung' => $ids])) ?>" target="_blank">Cetak RAB Gabungan</a>
    </div>
  </form>
</div>

<div class="panel">
  <h2>Ringkasan per Gedung Terpilih</h2>
  <table data-cari="Cari gedung…">
    <tr><th>Gedung</th><th class="angka">Nilai BOQ (RAB)</th><th class="angka">Realisasi</th><th class="angka">Fisik</th><th></th></tr>
    <?php $totBoq = 0; foreach ($perGedung as $g): $totBoq += (float) $g['nilai_boq']; ?>
    <tr>
      <td><?= esc($g['nama']) ?></td>
      <td class="angka"><?= rp($g['nilai_boq']) ?></td>
      <td class="angka"><?= rp($g['nilai_realisasi']) ?></td>
      <td class="angka"><?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</td>
      <td><a href="<?= base_url('lpj/gedung/' . $g['id']) ?>">RAB per Gedung</a></td>
    </tr>
    <?php endforeach; ?>
    <tr><th style="text-align:right">Total BOQ terpilih</th><th class="angka"><?= rp($totBoq) ?></th><th colspan="3"></th></tr>
  </table>
</div>

<div class="panel">
  <h2>Pekerjaan Gabungan (<?= count($gabungan) ?> jenis pekerjaan)</h2>
  <table data-paginasi="25" data-cari="Cari pekerjaan…">
    <tr><th>Pekerjaan</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Volume Gabungan</th><th class="angka">Jumlah</th><th class="angka">Di Gedung</th></tr>
    <?php foreach ($gabungan as $p): ?>
    <tr>
      <td><?= esc($p['nama']) ?></td>
      <td><?= esc($p['satuan'] ?? '') ?></td>
      <td class="angka"><?= rp($p['harga_satuan']) ?></td>
      <td class="angka"><?= $angka($p['volume']) ?></td>
      <td class="angka"><?= rp($p['jumlah']) ?></td>
      <td class="angka"><?= (int) $p['jumlah_gedung'] ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($gabungan === []): ?><tr><td colspan="6">Belum ada pekerjaan pada gedung terpilih.</td></tr><?php endif; ?>
    <tr><th colspan="4" style="text-align:right">TOTAL RAB GABUNGAN</th><th class="angka"><?= rp($total) ?></th><th></th></tr>
  </table>
  <p class="kecil-teks">Total RAB gabungan dihitung dari pekerjaan yang digabung menurut nama + harga satuan; total BOQ per gedung pada ringkasan di atas adalah angka resmi per gedung dari dokumen perencana.</p>
</div>

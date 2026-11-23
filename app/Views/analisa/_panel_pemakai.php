<?php /* Partial analisa/detail: panel Dipakai di Pekerjaan (daftar pekerjaan pemakai analisa ini). */ ?><div class="panel">
  <h2 style="margin-top:0">Dipakai di Pekerjaan (<?= count($pemakai ?? []) ?>)</h2>
  <?php if (empty($pemakai)): ?>
  <p class="kecil-teks" style="margin-bottom:0">Analisa ini belum dipakai pekerjaan mana pun di gedung aktif sekolah ini.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><table data-paginasi="10" data-cari="Cari gedung / pekerjaan…">
    <tr><th>Gedung</th><th>Pekerjaan</th><th class="angka">Volume Berjalan</th><th data-tanpa-urut>Aksi</th></tr>
    <?php foreach ($pemakai as $pm): $vb = (float) ($pm['volume_revisi'] ?? $pm['volume']); ?>
    <tr>
      <td><a href="<?= base_url('gedung/' . $pm['gedung_id']) ?>"><?= esc($pm['gedung']) ?></a></td>
      <td class="potong" title="<?= esc($pm['nama']) ?>"><?= esc($pm['nama']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format($vb, 3, ',', '.'), '0'), ',') ?> <?= esc($pm['satuan'] ?? '') ?></td>
      <td><a class="tombol kecil" href="<?= base_url('gedung/' . $pm['gedung_id'] . '/boq#pekerjaan-' . $pm['pekerjaan_id']) ?>">Buka Pekerjaan</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <p class="kecil-teks" style="margin-bottom:0">Tombol Buka Pekerjaan menuju baris pekerjaan itu di BOQ gedungnya (halaman digulir &amp; baris disorot).</p>
  <?php endif; ?>
</div>

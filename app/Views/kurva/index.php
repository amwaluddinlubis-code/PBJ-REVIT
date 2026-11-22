<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2>Kurva S Pelaksanaan Pekerjaan</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Jadwal pelaksanaan dalam bentuk kurva S (format dokumen Juknis): garis rencana kumulatif dibandingkan realisasi kumulatif mingguan dari data realisasi BOQ. Jendela waktu mengikuti tanggal mulai–selesai SPK yang tertaut ke gedung.</p>
  <form method="get" action="<?= base_url('kurva-s') ?>" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
    <label>Gedung
      <select data-select-cari name="gedung">
        <?php foreach ($semua as $row): ?><option value="<?= (int) $row['id'] ?>" <?= $g && (int) $g['id'] === (int) $row['id'] ? 'selected' : '' ?>><?= esc($row['nama']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="kecil">Tampilkan</button>
  </form>
</div>

<?php if ($g): ?>
<div class="panel">
  <h2><?= esc($g['nama']) ?> — Nilai BOQ <?= $rp($g['nilai_boq']) ?> · Realisasi saat ini <?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</h2>
  <?= $svg ?>
  <table data-paginasi="20">
    <tr><th>Minggu ke-</th><th>Sampai Tanggal</th><th class="angka">Rencana Kumulatif</th><th class="angka">Realisasi Kumulatif</th><th class="angka">Nilai Realisasi</th><th class="angka">Deviasi</th></tr>
    <?php foreach ($minggu as $m): ?>
    <tr>
      <td><?= (int) $m['ke'] ?></td>
      <td><?= esc($m['tanggal']) ?></td>
      <td class="angka"><?= number_format($m['rencana'], 1, ',', '.') ?>%</td>
      <td class="angka"><?= number_format($m['realisasi'], 1, ',', '.') ?>%</td>
      <td class="angka"><?= $rp($m['nilai']) ?></td>
      <td class="angka"><?= number_format($m['realisasi'] - $m['rencana'], 1, ',', '.') ?>%</td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="kecil-teks">Deviasi negatif berarti realisasi tertinggal dari rencana. Garis rencana digambar halus berbentuk S (lambat di awal dan akhir, cepat di tengah) sebagai pembanding; sesuaikan dengan jadwal resmi pada dokumen perencanaan.</p>
</div>
<?php else: ?>
<div class="panel"><p>Belum ada gedung dari dokumen perencana.</p></div>
<?php endif; ?>

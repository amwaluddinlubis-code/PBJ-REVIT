<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $num = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <h2 style="margin-top:0">Pratinjau SPK Gabungan</h2>
  <p class="kecil-teks">Dari <?= count($rencana) ?> rencana mingguan:
    <?= implode('; ', array_map(static fn ($r) => '<strong>' . esc($r['gedung_nama']) . '</strong> (' . esc(tgl($r['minggu_mulai'])) . ')', $rencana)) ?>.
    Jumlah beli boleh disesuaikan per baris. Baris dengan jumlah 0 tidak ikut dibuatkan SPK.</p>
  <form method="post" action="<?= base_url('rencana-mingguan/gabung-buat') ?>">
    <?= csrf_field() ?>
    <?php foreach ($rencana as $r): ?><input type="hidden" name="ids[]" value="<?= (int) $r['id'] ?>"><?php endforeach; ?>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari bahan…">
      <tr><th>Bahan</th><th>Gedung Tujuan</th><th class="angka">Kebutuhan Gedung Ini</th><th class="angka">Stok + Berjalan (se-sekolah)</th><th class="angka">Jumlah Beli</th><th class="angka">Harga Acuan</th><th class="angka">Nilai</th></tr>
      <?php foreach ($baris as $b): ?>
      <tr>
        <td><strong><?= esc($b['kode']) ?></strong> — <?= esc($b['nama']) ?> (<?= esc($b['satuan'] ?? '') ?>)</td>
        <td><?= esc($gedung_nama[$b['gedung_id']] ?? '') ?></td>
        <td class="angka"><?= $num($b['kotor']) ?></td>
        <td class="angka"><?= $num($b['stok'] + $b['berjalan']) ?></td>
        <td class="angka"><input type="text" name="qty[<?= (int) $b['bahan_id'] ?>][<?= (int) $b['gedung_id'] ?>]" value="<?= esc(number_format((float) $b['qty'], 2, '.', '')) ?>" style="width:100px;text-align:right" inputmode="decimal" aria-label="Jumlah beli <?= esc($b['nama']) ?> untuk <?= esc($gedung_nama[$b['gedung_id']] ?? '') ?>"></td>
        <td class="angka"><?= $rp($b['harga']) ?></td>
        <td class="angka"><?= $rp($b['nilai']) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="total"><td colspan="6"><strong>Total nilai SPK gabungan</strong></td><td class="angka"><strong><?= $rp($total) ?></strong></td></tr>
    </table></div>
    <p style="margin:10px 0 0"><button type="submit" class="tombol">Buat 1 SPK dari Gabungan Ini</button>
    <a class="tombol abu" href="<?= base_url('rencana-mingguan/gabung') ?>">Ubah Pilihan Rencana</a></p>
  </form>
  <p class="kecil-teks">SPK terbentuk tanpa gedung tunggal (gabungan) — rincian di dalamnya tetap terpisah per gedung tujuan, dan cetakan SPK mengelompokkan rincian menurut gedungnya. Penyedia dan tanggal SPK dilengkapi sesudahnya di Detail SPK, seperti SPK dari rencana mingguan biasa.</p>
</div>

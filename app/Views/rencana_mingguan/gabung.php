<div class="panel">
  <h2 style="margin-top:0">Gabungkan Rencana Mingguan Jadi 1 SPK</h2>
  <p class="kecil-teks">Centang dua rencana atau lebih — boleh beda gedung — lalu lanjut ke pratinjau. Kebutuhan bahan semua rencana dijumlahkan; stok tersedia &amp; pesanan berjalan dikurangkan <strong>satu kali</strong> dari gabungan, dan sisanya dibagi ke tiap gedung menurut porsi kebutuhannya. SPK yang terbentuk berisi rincian terpisah per gedung, jadi kebutuhan tiap gedung tetap terbaca di SPK, cetakan, dan Buku Material.</p>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks">Belum ada rencana mingguan yang punya target. Buat dan isi target dulu dari halaman <a href="<?= base_url('rencana-mingguan') ?>">Rencana Mingguan</a>.</p>
  <?php else: ?>
  <form method="post" action="<?= base_url('rencana-mingguan/gabung-pratinjau') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari rencana…">
      <tr><th style="width:34px" data-tanpa-urut>Pilih</th><th>Gedung</th><th>Minggu</th><th>Keterangan</th><th class="angka">Pekerjaan Ditargetkan</th></tr>
      <?php foreach ($daftar as $r): ?>
      <tr>
        <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" aria-label="Pilih rencana <?= esc($r['gedung_nama']) ?>"></td>
        <td><strong><?= esc($r['gedung_nama']) ?></strong></td>
        <td><?= esc(tgl($r['minggu_mulai'])) ?> – <?= esc(tgl(date('Y-m-d', strtotime($r['minggu_mulai'] . ' +6 days')))) ?></td>
        <td><?= esc($r['keterangan'] ?? '-') ?></td>
        <td class="angka"><?= (int) $r['jml_target'] ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p style="margin:10px 0 0"><button type="submit" class="tombol">Lihat Pratinjau Gabungan</button>
    <a class="tombol abu" href="<?= base_url('rencana-mingguan') ?>">Kembali</a></p>
  </form>
  <?php endif; ?>
</div>

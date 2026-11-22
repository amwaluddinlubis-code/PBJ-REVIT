<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2>Pembersihan Hasil Impor — Langkah 3 dari 3: Bahan Yatim &amp; Eksekusi</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Sesudah <?= (int) $jumlahPekerjaan ?> pekerjaan dan <?= (int) $jumlahAnalisa ?> analisa dibuang, bahan berikut kehilangan seluruh rujukannya. <strong>Kelompok A</strong> (tercentang bawaan) hanya dipakai analisa yang akan dibuang. <strong>Kelompok B</strong> tidak dirujuk apa pun sama sekali — tidak tercentang bawaan karena bisa saja daftar harga cadangan; periksa dulu. Bahan lain sejumlah <strong><?= (int) $terlindungi ?> bahan terlindungi</strong> (masih dirujuk analisa aktif, SPK, survei, negosiasi, atau buku material) dan tidak ditawarkan untuk dihapus.</p>
  <h3>Ringkasan dampak</h3>
  <table>
    <tr><th>Gedung</th><th class="angka">BOQ Sekarang</th><th class="angka">Pekerjaan Dibuang</th></tr>
    <?php foreach ($ringkasan['gedung'] as $g): ?>
    <tr><td><?= esc($g['nama']) ?></td><td class="angka"><?= $rp($g['nilai_boq']) ?></td><td class="angka"><?= (int) $g['c'] ?></td></tr>
    <?php endforeach; ?>
    <tr><th style="text-align:right">Nilai pekerjaan yang dibuang</th><th class="angka"><?= $rp($ringkasan['nilai_pekerjaan']) ?></th><th></th></tr>
  </table>
  <p class="kecil-teks">Nilai BOQ tiap gedung dihitung ulang dari pekerjaan yang tersisa sesudah eksekusi. Semua penghapusan bersifat lunak — data dapat dipulihkan dari basis data bila ternyata keliru.</p>
</div>

<form method="post" action="<?= base_url('bersih/eksekusi') ?>" data-konfirmasi="Jalankan pembersihan sekarang? Pekerjaan, analisa, dan bahan tercentang akan dihapus (lunak) dan BOQ gedung dihitung ulang.">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>Kelompok A — Hanya Dipakai Analisa yang Dibuang</h2>
    <table>
      <tr><th style="width:36px">Hapus</th><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="angka">Harga RAB</th></tr>
      <?php foreach ($grupA as $b): ?>
      <tr>
        <td><input type="checkbox" name="bahan[]" value="<?= (int) $b['id'] ?>" checked></td>
        <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td><td><?= esc($b['satuan'] ?? '') ?></td>
        <td class="angka"><?= $rp($b['harga_rab']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if ($grupA === []): ?><tr><td colspan="5">Tidak ada bahan kelompok A.</td></tr><?php endif; ?>
    </table>
  </div>
  <div class="panel">
    <h2>Kelompok B — Tidak Dirujuk Apa Pun</h2>
    <table>
      <tr><th style="width:36px">Hapus</th><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="angka">Harga RAB</th></tr>
      <?php foreach ($grupB as $b): ?>
      <tr>
        <td><input type="checkbox" name="bahan[]" value="<?= (int) $b['id'] ?>"></td>
        <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td><td><?= esc($b['satuan'] ?? '') ?></td>
        <td class="angka"><?= $rp($b['harga_rab']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if ($grupB === []): ?><tr><td colspan="5">Tidak ada bahan kelompok B.</td></tr><?php endif; ?>
    </table>
    <div class="aksi" style="justify-content:flex-start;margin-top:10px">
      <button type="submit">Jalankan Pembersihan</button>
    </div>
  </div>
</form>
<form method="post" action="<?= base_url('bersih/batal') ?>"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Batalkan Pembersihan</button></form>

<div class="panel">
  <h2>Pembersihan Hasil Impor — Langkah 2 dari 3: Analisa yang Menjadi Tak Terpakai</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)"><?= (int) $jumlahPekerjaan ?> pekerjaan ditandai tidak terpakai. Analisa di bawah ini hanya dirujuk oleh pekerjaan-pekerjaan itu — tidak ada pekerjaan aktif lain yang memakainya — sehingga analisanya ikut tidak terpakai. Centang analisa yang setuju dibuang; bahan yang hanya dipakai analisa terpilih akan ditawarkan untuk dihapus pada langkah berikutnya. Analisa yang tidak dicentang tetap tersimpan dan bahannya tetap terlindungi.</p>
</div>

<form method="post" action="<?= base_url('bersih/langkah3') ?>">
  <?= csrf_field() ?>
  <div class="panel">
    <?php if ($kandidat === []): ?>
    <p>Tidak ada analisa yang menjadi tak terpakai — pekerjaan terpilih tidak menaut analisa apa pun secara eksklusif. Lanjut saja ke langkah 3.</p>
    <?php else: ?>
    <table data-cari="Cari analisa…">
      <tr><th style="width:36px">Buang</th><th>Kode</th><th>Nama Analisa</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Komponen</th></tr>
      <?php foreach ($kandidat as $a): ?>
      <tr>
        <td><input type="checkbox" name="analisa[]" value="<?= (int) $a['id'] ?>" checked></td>
        <td><?= esc($a['kode']) ?></td>
        <td><?= esc($a['nama']) ?></td>
        <td><?= esc($a['satuan'] ?? '') ?></td>
        <td class="angka">Rp <?= number_format((float) $a['harga_satuan'], 0, ',', '.') ?></td>
        <td class="angka"><?= (int) $a['jumlah_komponen'] ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <div class="aksi" style="justify-content:flex-start;margin-top:10px">
      <button type="submit">Lanjut ke Langkah 3: Bahan Yatim</button>
    </div>
  </div>
</form>
<form method="post" action="<?= base_url('bersih/batal') ?>"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Batalkan Pembersihan</button></form>

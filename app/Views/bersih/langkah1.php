<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>
<div class="panel">
  <h2>Pembersihan Hasil Impor — Langkah 1 dari 3: Pekerjaan Volume &lt; 1</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Pekerjaan bervolume kurang dari 1 hampir selalu sisa baris template dari dokumen perencana — analisanya tidak akan terpakai. Tandai pekerjaan yang benar-benar tidak terpakai untuk dibuang. <strong>Volume 0 tercentang bawaan</strong>; volume pecahan (0 &lt; volume &lt; 1) tidak tercentang karena sebagian pekerjaan sah memang bervolume pecahan — periksa dulu sebelum mencentang. Pekerjaan yang sudah punya realisasi diberi tanda dan tidak tercentang.</p>
  <p>Dari <strong><?= number_format($totalAktif) ?> pekerjaan aktif</strong>, ada <strong><?= number_format($totalKecil) ?> pekerjaan bervolume &lt; 1</strong>.</p>
  <?php if ($totalKecil > 0): ?>
  <h3>Pembersihan Massal (disarankan untuk hasil impor)</h3>
  <p style="color:var(--ta-teks-2)">Pilih semua pekerjaan sekaligus tanpa mencentang satu per satu — pekerjaan yang sudah punya realisasi selalu dikecualikan. Sesudah memilih, kamu tetap meninjau analisa dan bahan pada langkah 2–3 sebelum apa pun dihapus.</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <form method="post" action="<?= base_url('bersih/langkah2') ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="volume_nol"><button type="submit">Pilih Semua Volume 0</button></form>
    <form method="post" action="<?= base_url('bersih/langkah2') ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="volume_kecil"><button type="submit" class="abu">Pilih Semua Volume &lt; 1 (termasuk pecahan)</button></form>
  </div>
  <p class="kecil-teks" style="margin-top:10px">Atau centang manual per gedung pada daftar di bawah (cocok untuk pilihan terbatas; formulir web membatasi jumlah centang yang terkirim).</p>
  <?php endif; ?>
</div>

<?php if ($totalKecil === 0): ?>
<div class="panel"><p>Tidak ada pekerjaan bervolume &lt; 1 — data pekerjaan sudah bersih.</p></div>
<?php else: ?>
<form method="post" action="<?= base_url('bersih/langkah2') ?>">
  <?= csrf_field() ?>
  <?php foreach ($perGedung as $namaGedung => $rows): ?>
  <div class="panel">
    <h2><?= esc($namaGedung) ?> <span class="kecil-teks">(<?= count($rows) ?> pekerjaan volume &lt; 1)</span></h2>
    <table data-cari="Cari pekerjaan…">
      <tr><th style="width:36px">Buang</th><th>Pekerjaan</th><th>Kode Analisa</th><th class="angka">Volume</th><th>Satuan</th><th class="angka">Jumlah</th><th></th></tr>
      <?php foreach ($rows as $p): $nol = (float) $p['volume'] <= 0; $adaReal = (int) $p['punya_realisasi'] > 0; ?>
      <tr>
        <td><input type="checkbox" name="pekerjaan[]" value="<?= (int) $p['id'] ?>" <?= $nol && ! $adaReal ? 'checked' : '' ?>></td>
        <td><?= esc($p['nama']) ?></td>
        <td><?= esc($p['kode_analisa'] ?? '') ?></td>
        <td class="angka"><?= $angka($p['volume']) ?></td>
        <td><?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= $rp($p['jumlah']) ?></td>
        <td><?php if ($adaReal): ?><span class="lencana kuning">sudah ada realisasi</span><?php elseif (! $nol): ?><span class="lencana abu">volume pecahan — periksa</span><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endforeach; ?>
  <div class="aksi" style="justify-content:flex-start;gap:8px">
    <button type="submit">Lanjut ke Langkah 2: Analisa</button>
    <a class="tombol abu" href="<?= base_url('impor') ?>">Kembali ke Impor</a>
  </div>
</form>
<form method="post" action="<?= base_url('bersih/batal') ?>" style="margin-top:8px"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Batalkan Pembersihan</button></form>
<?php endif; ?>

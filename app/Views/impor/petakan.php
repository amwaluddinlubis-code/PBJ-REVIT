<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>

<div class="panel">
  <h2>Tinjau &amp; Petakan Impor</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Berkas <b><?= esc($stage['nama']) ?></b> akan diimpor ke sekolah <b><?= esc($nama_sekolah) ?></b> (format: <?= $stage['format'] === 'template' ? 'Template Manual' : 'Workbook Perencana' ?>). <b>Belum ada data yang ditulis</b> — periksa daftar sheet di bawah ini: pilih tujuan impor tiap sheet, atau tandai <b>Lewati</b> untuk yang tidak akan diimpor. Nama gedung dapat diubah sebelum impor.</p>
  <p class="kecil-teks" style="margin-bottom:0">Sheet <b>Data Referensi</b> (daftar harga, analisa, harsat, rekap pagu, data sekolah) adalah bahan acuan bagi sheet lain — melewatkan Analisa membuat harga pekerjaan BOQ hanya mengandalkan harga yang tertulis di sheet BOQ itu sendiri.</p>
</div>

<form method="post" action="<?= base_url('impor/eksekusi') ?>">
  <?= csrf_field() ?>
  <div class="panel">
    <table>
      <tr><th style="width:22%">Sheet</th><th style="width:20%">Jenis Terdeteksi</th><th class="angka" style="width:13%">Isi</th><th style="width:19%">Tujuan Impor</th><th>Nama Gedung (bila tujuan Gedung)</th></tr>
      <?php foreach ($stage['lembar'] as $i => $l): ?>
      <?php
        $opsi = match ($l['kategori']) {
            'referensi' => ['referensi' => 'Data Referensi', 'lewati' => 'Lewati'],
            'gedung' => ['gedung' => 'Gedung', 'non_gedung' => 'Biaya Non-Gedung', 'lewati' => 'Lewati'],
            'non_gedung' => ['non_gedung' => 'Biaya Non-Gedung', 'gedung' => 'Gedung', 'lewati' => 'Lewati'],
            default => ['lewati' => 'Lewati', 'gedung' => 'Gedung', 'non_gedung' => 'Biaya Non-Gedung'],
        };
      ?>
      <tr>
        <td><b><?= esc($l['nama']) ?></b><input type="hidden" name="sheet[<?= $i ?>]" value="<?= esc($l['nama']) ?>"></td>
        <td><?= esc($l['jenis']) ?></td>
        <td class="angka"><?php if ($l['baris'] > 0): ?><?= number_format($l['baris'], 0, ',', '.') ?> baris<?php endif; ?><?php if ($l['total'] > 0): ?><br><?= $rp($l['total']) ?><?php endif; ?></td>
        <td>
          <select name="tujuan[<?= $i ?>]" data-baris="<?= $i ?>">
            <?php foreach ($opsi as $nilai => $label): ?>
            <option value="<?= $nilai ?>" <?= $l['saran'] === $nilai ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="text" name="nama_gedung[<?= $i ?>]" value="<?= esc($l['nama']) ?>" data-nama="<?= $i ?>" <?= $l['saran'] === 'gedung' ? '' : 'disabled' ?>></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div class="aksi" style="justify-content:flex-start;gap:10px;margin-top:14px">
      <button type="submit">Impor Sekarang</button>
      <button type="submit" class="tombol abu" formaction="<?= base_url('impor/batal') ?>">Batal — Jangan Impor</button>
    </div>
    <p class="kecil-teks" style="margin-bottom:0">Sheet bertujuan Gedung menggantikan pekerjaan gedung dengan nama/sumber yang sama dari impor sebelumnya; sheet bertujuan Biaya Non-Gedung menggantikan baris impor sebelumnya (baris manual Anda tidak terhapus).</p>
  </div>
</form>

<script>
document.querySelectorAll('select[data-baris]').forEach(function (sel) {
  sel.addEventListener('change', function () {
    var input = document.querySelector('input[data-nama="' + sel.dataset.baris + '"]');
    if (input) input.disabled = sel.value !== 'gedung';
  });
});
</script>

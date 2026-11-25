<?php /* Partial gedung/detail tab BOQ: panel Ubah Nama / Keterangan Gedung. */ ?><div class="panel">
  <h2>Ubah Nama / Keterangan Gedung</h2>
  <form method="post" action="<?= base_url('gedung/' . $g['id'] . '/perbarui') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" value="<?= esc($g['nama']) ?>" required></label>
      <label>Keterangan<input type="text" name="keterangan" value="<?= esc($g['keterangan'] ?? '') ?>"></label>
      <label>Nilai BOQ dokumen (Rp)<input type="text" name="nilai_boq" data-uang inputmode="numeric" value="<?= number_format((float) $g['nilai_boq'], 0, ',', '.') ?>"></label>
      <p class="kecil-teks md:col-span-2" style="margin:0">Nilai BOQ dokumen adalah angka resmi dari dokumen perencana (terisi saat impor). Nilai ini <strong>beku</strong>: tidak berubah oleh edit volume/pekerjaan — ubah hanya di sini bila angka dokumennya memang direvisi.</p>
      <div class="aksi md:col-span-2"><button type="submit">Simpan</button></div>
    </div>
  </form>
</div>

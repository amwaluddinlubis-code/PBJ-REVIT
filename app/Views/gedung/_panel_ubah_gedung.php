<?php /* Partial gedung/detail tab BOQ: panel Ubah Nama / Keterangan Gedung. */ ?><div class="panel">
  <h2>Ubah Nama / Keterangan Gedung</h2>
  <form method="post" action="<?= base_url('gedung/' . $g['id'] . '/perbarui') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" value="<?= esc($g['nama']) ?>" required></label>
      <label>Keterangan<input type="text" name="keterangan" value="<?= esc($g['keterangan'] ?? '') ?>"></label>
      <div class="aksi md:col-span-2"><button type="submit">Simpan</button></div>
    </div>
  </form>
</div>

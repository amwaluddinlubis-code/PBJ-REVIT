<?php /* Partial gedung/detail tab BOQ: panel Tambah Pekerjaan Manual. */ ?><div class="panel">
  <h2>Tambah Pekerjaan Manual</h2>
  <form method="post" action="<?= base_url('gedung/pekerjaan/simpan') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama pekerjaan<input type="text" name="nama" required></label>
      <label>Bab<input type="text" name="bab" placeholder="mis. II. PEKERJAAN TANAH"></label>
      <label>Kode analisa<input type="text" name="kode_analisa" placeholder="mis. B.01"></label>
      <label>Satuan<input type="text" name="satuan" placeholder="m3 / m2 / ls"></label>
      <label>Volume<input type="text" name="volume" value="0"></label>
      <label>Harga satuan (Rp)<input type="text" inputmode="numeric" data-uang name="harga_satuan" value="0"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Pekerjaan</button></div>
    </div>
  </form>
</div>

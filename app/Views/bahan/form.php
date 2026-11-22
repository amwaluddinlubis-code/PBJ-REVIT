<div class="panel">
<form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url($aksi) ?>">
  <?= csrf_field() ?>
  <label>Kode <input name="kode" value="<?= esc(old('kode', $b['kode'] ?? '')) ?>" required></label>
  <label>Nama Bahan <input name="nama" value="<?= esc(old('nama', $b['nama'] ?? '')) ?>" required></label>
  <label>Satuan <input name="satuan" value="<?= esc(old('satuan', $b['satuan'] ?? '')) ?>"></label>
  <label>Harga RAB (Rp) <input type="text" inputmode="numeric" data-uang name="harga_rab" value="<?= esc(old('harga_rab', $b['harga_rab'] ?? '')) ?>" required></label>
  <label>Qty Gedung RKB <input type="number" name="qty_rkb" step="0.001" value="<?= esc(old('qty_rkb', $b['qty_rkb'] ?? 0)) ?>"></label>
  <label>Qty Ruang ADM <input type="number" name="qty_adm" step="0.001" value="<?= esc(old('qty_adm', $b['qty_adm'] ?? 0)) ?>"></label>
  <label>Qty Toilet <input type="number" name="qty_toilet" step="0.001" value="<?= esc(old('qty_toilet', $b['qty_toilet'] ?? 0)) ?>"></label>
  <div class="aksi md:col-span-2">
    <button type="submit">Simpan</button>
    <a class="tombol abu" href="<?= base_url('bahan') ?>">Batal</a>
  </div>
</form>
<?php if (! empty($b['id'])): ?>
<form method="post" action="<?= base_url('bahan/hapus/' . $b['id']) ?>" data-konfirmasi="Hapus bahan ini?" style="margin-top:14px">
  <?= csrf_field() ?>
  <button type="submit" class="bahaya kecil">Hapus Bahan</button>
</form>
<?php endif; ?>
</div>

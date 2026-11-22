<div class="panel">
<form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url($aksi) ?>">
  <?= csrf_field() ?>
  <label>Nomor SPK <input type="number" name="nomor" min="1" value="<?= esc(old('nomor', $s['nomor'] ?? '')) ?>" required></label>
  <label>Tanggal <input type="date" name="tanggal" value="<?= esc(old('tanggal', $s['tanggal'] ?? '')) ?>"></label>
  <label>Penyedia
    <select name="penyedia_id" data-select-cari data-placeholder="— pilih / isi baru di kanan —">
      <option value="">— pilih / isi baru di kanan —</option>
      <?php foreach ($penyedia as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= (old('penyedia_id', $s['penyedia_id'] ?? '') == $p['id']) ? ' selected' : '' ?>><?= esc($p['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Penyedia Baru (bila belum ada) <input name="penyedia_baru" value=""></label>
  <label>Nama Penandatangan Penyedia <input name="ttd_penyedia" value="<?= esc(old('ttd_penyedia', $s['ttd_penyedia'] ?? '')) ?>"></label>
  <label>Gedung Tujuan
    <select name="gedung_id">
      <option value="">— umum / belum ditentukan —</option>
      <?php foreach ($semuaGedung ?? [] as $gd): ?>
        <option value="<?= (int) $gd['id'] ?>"<?= (old('gedung_id', $s['gedung_id'] ?? '') == $gd['id']) ? ' selected' : '' ?>><?= esc($gd['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Paket Pekerjaan <input name="paket" value="<?= esc(old('paket', $s['paket'] ?? '')) ?>"></label>
  <label>Tanggal Mulai <input type="date" name="tgl_mulai" value="<?= esc(old('tgl_mulai', $s['tgl_mulai'] ?? '')) ?>"></label>
  <label>Tanggal Selesai <input type="date" name="tgl_selesai" value="<?= esc(old('tgl_selesai', $s['tgl_selesai'] ?? '')) ?>"></label>
  <label>Status
    <select name="status">
      <?php foreach (['Aktif', 'Selesai', 'Batal'] as $st): ?>
        <option<?= (old('status', $s['status'] ?? 'Aktif') === $st) ? ' selected' : '' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="aksi md:col-span-2">
    <button type="submit">Simpan</button>
    <a class="tombol abu" href="<?= base_url('spk') ?>">Batal</a>
  </div>
</form>
<?php if (! empty($s['id'])): ?>
<form method="post" action="<?= base_url('spk/hapus/' . $s['id']) ?>" data-konfirmasi="Hapus SPK ini? Rincian barangnya menjadi belum bernomor." style="margin-top:14px">
  <?= csrf_field() ?>
  <button type="submit" class="bahaya kecil">Hapus SPK</button>
</form>
<?php endif; ?>
</div>

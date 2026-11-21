<div class="panel">
<form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url($aksi) ?>">
  <?= csrf_field() ?>
  <label>Nomor Urut <input type="number" name="nomor_urut" min="1" value="<?= esc(old('nomor_urut', $i['nomor_urut'] ?? '')) ?>" required></label>
  <label>No. Invoice Toko <input name="no_toko" value="<?= esc(old('no_toko', $i['no_toko'] ?? '')) ?>"></label>
  <label>Tanggal <input type="date" name="tanggal" value="<?= esc(old('tanggal', $i['tanggal'] ?? '')) ?>"></label>
  <label>SPK
    <select name="spk_id" id="pilihSpk" required>
      <?php foreach ($spk['list'] as $s): ?>
        <option value="<?= (int) $s['id'] ?>" data-total="<?= (int) $s['total'] ?>"<?= (old('spk_id', $i['spk_id'] ?? '') == $s['id']) ? ' selected' : '' ?>>SPK <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?> (total Rp <?= number_format((float) $s['total'], 0, ',', '.') ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>BAP
    <select name="bap_id">
      <option value="">— belum ada —</option>
      <?php foreach ($spk['bap'] as $sid => $daftar): foreach ($daftar as $b): ?>
        <option value="<?= (int) $b['id'] ?>" data-spk="<?= (int) $sid ?>"<?= (old('bap_id', $i['bap_id'] ?? '') == $b['id']) ? ' selected' : '' ?>>BAP urut <?= (int) $b['nomor_urut'] ?> (SPK id <?= (int) $sid ?>)</option>
      <?php endforeach; endforeach; ?>
    </select>
  </label>
  <label>BAST
    <select name="bast_id">
      <option value="">— belum ada —</option>
      <?php foreach ($spk['bast'] as $sid => $daftar): foreach ($daftar as $b): ?>
        <option value="<?= (int) $b['id'] ?>" data-spk="<?= (int) $sid ?>"<?= (old('bast_id', $i['bast_id'] ?? '') == $b['id']) ? ' selected' : '' ?>>BAST urut <?= (int) $b['nomor_urut'] ?> (SPK id <?= (int) $sid ?>)</option>
      <?php endforeach; endforeach; ?>
    </select>
  </label>
  <label>Nilai Invoice (kosongkan = total SPK) <input type="number" name="nilai" step="1" value="<?= esc(old('nilai', $i['nilai'] ?? '')) ?>"></label>
  <label>Status
    <select name="status">
      <?php foreach (['Belum Bayar', 'Sudah Bayar'] as $st): ?>
        <option<?= (old('status', $i['status'] ?? 'Belum Bayar') === $st) ? ' selected' : '' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="aksi md:col-span-2"><button type="submit">Simpan</button><a class="tombol abu" href="<?= base_url('invoice') ?>">Batal</a></div>
</form>
<?php if (! empty($i['id'])): ?>
<form method="post" action="<?= base_url('invoice/hapus/' . $i['id']) ?>" data-konfirmasi="Hapus invoice ini?" style="margin-top:14px">
  <?= csrf_field() ?><button type="submit" class="bahaya kecil">Hapus Invoice</button>
</form>
<?php endif; ?>
</div>

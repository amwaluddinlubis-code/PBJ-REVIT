<div class="panel">
<form method="post" action="<?= base_url('dokumen/bast-simpan/' . $bast['id']) ?>">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
    <label>Nomor Urut BAST <input type="number" name="nomor_urut" min="1" value="<?= esc($bast['nomor_urut']) ?>" required></label>
    <label>Tanggal Serah Terima <input type="date" name="tanggal" value="<?= esc($bast['tanggal'] ?? '') ?>"></label>
    <?php if (! empty($bap)): ?>
    <label>Tanggal BAP (urut <?= (int) $bap['nomor_urut'] ?>) <input type="date" value="<?= esc($bap['tanggal'] ?? '') ?>" readonly></label>
    <?php endif; ?>
  </div>
  <div class="aksi md:col-span-2"><button type="submit">Simpan BAST</button>
  <a class="tombol abu" href="<?= base_url('dokumen') ?>">Kembali</a>
  <a class="tombol" href="<?= base_url('dokumen/cetak-bast/' . $bast['id']) ?>" target="_blank">Cetak</a></div>
</form>
</div>

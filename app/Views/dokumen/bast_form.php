<div class="panel">
<form method="post" action="<?= base_url('dokumen/bast-simpan/' . $bast['id']) ?>">
  <?= csrf_field() ?>
  <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
    <label>Nomor Urut BAST <input type="number" name="nomor_urut" min="1" value="<?= esc($bast['nomor_urut']) ?>" required></label>
    <label>Tanggal Serah Terima <input type="date" name="tanggal" value="<?= esc($bast['tanggal'] ?? '') ?>"></label>
  </div>
  <div class="aksi"><button type="submit">Simpan BAST</button>
  <a class="tombol abu" href="<?= base_url('dokumen') ?>">Kembali</a>
  <a class="tombol" href="<?= base_url('dokumen/cetak-bast/' . $bast['id']) ?>" target="_blank">Cetak</a></div>
</form>
</div>

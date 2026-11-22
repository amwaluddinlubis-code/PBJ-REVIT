<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <h2>Catat Barang Masuk / Keluar</h2>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('material/simpan') ?>">
    <?= csrf_field() ?>
    <label>Tanggal <input type="date" name="tanggal"></label>
    <label>No. BPB <input name="no_bpb"></label>
    <label>Bahan
      <select name="bahan_id" required>
        <option value="">— pilih bahan —</option>
        <?php foreach ($bahan as $b): ?><option value="<?= (int) $b['id'] ?>"><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Masuk <input type="number" name="masuk" step="0.001" value="0"></label>
    <label>Keluar <input type="number" name="keluar" step="0.001" value="0"></label>
    <label>Keterangan <input name="keterangan"></label>
    <div class="aksi md:col-span-2"><button type="submit">Catat</button></div>
  </form>
</div>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-material','ids-material')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-material" method="post" action="<?= base_url('material/hapus-massal') ?>" data-konfirmasi-judul="Hapus Baris Buku Material Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-material"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} baris buku material terpilih? Sisa stok akan berubah.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('material/kosongkan') ?>" data-konfirmasi="Kosongkan buku material sekolah ini? Semua catatan masuk/keluar akan dihapus (hapus lunak) dan sisa stok kembali ke hitungan SPK saja." data-konfirmasi-judul="Kosongkan Buku Material" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Buku Material</button>
    </form>
  </div>
  <table data-paginasi="10" data-cari="Cari catatan material…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua baris" @change="pilihSemua($event)"></th><th>Tanggal</th><th>No. BPB</th><th>Bahan</th><th class="angka">Masuk</th><th class="angka">Keluar</th><th class="angka">Sisa Stok</th><th>Keterangan</th><th></th></tr>
    <?php foreach ($baris as $r): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"></td>
      <td><?= esc($r['tanggal'] ?? '—') ?></td><td><?= esc($r['no_bpb'] ?? '') ?></td>
      <td><?= esc($r['kode']) ?> — <?= esc($r['nama']) ?> (<?= esc($r['satuan'] ?? '') ?>)</td>
      <td class="angka"><?= $fmt($r['masuk']) ?></td><td class="angka"><?= $fmt($r['keluar']) ?></td>
      <td class="angka"><?= $fmt($r['sisa']) ?></td><td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('material/hapus/' . $r['id']) ?>" data-konfirmasi="Hapus baris ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if (! $baris): ?><tr><td colspan="9">Belum ada catatan — sama seperti di workbook, buku material baru berjalan bila mulai dicatat dari sini.</td></tr><?php endif; ?>
  </table>
</div>

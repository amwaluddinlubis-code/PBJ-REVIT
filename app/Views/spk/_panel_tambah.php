<?php /* Partial spk/detail: panel Tambah Baris Barang (form pilih bahan/upah/pekerjaan). */ ?><div class="panel">
  <h2>Tambah Baris Barang</h2>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('spk/detail-tambah/' . $s['id']) ?>">
    <?= csrf_field() ?>
    <label>Bahan
      <select name="bahan_id" id="pilihBahan" required data-select-cari data-placeholder="— pilih bahan —">
        <option value="">— pilih bahan —</option>
        <?php foreach ($bahan as $b): ?>
          <option value="<?= (int) $b['id'] ?>" data-harga="<?= (int) $b['harga_acuan'] ?>"><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?> (<?= esc($b['satuan'] ?? '') ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Qty <input type="number" name="qty" step="0.001" min="0" required></label>
    <label>Harga Satuan (kosongkan = harga acuan) <input type="text" inputmode="numeric" data-uang name="harga" step="1" id="hargaIsi" placeholder="otomatis"></label>
    <label>Kena PPN
      <select data-select-cari name="pajak"><option value="0">Tidak</option><option value="1">Ya</option></select>
    </label>
    <div class="aksi md:col-span-2"><button type="submit">Tambah Baris</button></div>
  </form>
  <p class="kecil-teks">Harga acuan diambil berjenjang: harga negosiasi → harga penawaran → harga RAB.</p>
</div>

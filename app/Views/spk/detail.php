<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2>SPK Nomor <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?></h2>
  <div class="kecil-teks">
    Tanggal <?= esc($s['tanggal'] ?? '—') ?> · Penyedia <?= esc($s['penyedia'] ?? '—') ?> (ttd: <?= esc($s['ttd_penyedia'] ?? '—') ?>) ·
    Periode <?= esc($s['tgl_mulai'] ?? '—') ?> s.d. <?= esc($s['tgl_selesai'] ?? '—') ?> · Status <?= esc($s['status']) ?> ·
    <a href="<?= base_url('spk/ubah/' . $s['id']) ?>">Ubah kepala SPK</a>
  </div>
</div>
<div class="panel">
<table data-paginasi="10">
  <tr><th>Kode</th><th>Nama Bahan</th><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga</th><th class="angka">Total</th><th class="angka">PPN</th><th>Ubah Qty/Harga</th><th></th></tr>
  <?php $grand = 0; $grandPpn = 0; foreach ($detail as $d): $grand += (float) $d['total']; $grandPpn += (float) $d['ppn']; ?>
  <tr>
    <td><?= esc($d['kode']) ?></td>
    <td><?= esc($d['nama']) ?></td>
    <td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td>
    <td><?= esc($d['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($d['harga']) ?></td>
    <td class="angka"><?= $rp($d['total']) ?></td>
    <td class="angka"><?= $d['ppn'] ? $rp($d['ppn']) : '—' ?></td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-ubah/' . $d['id']) ?>">
        <?= csrf_field() ?>
        <input type="number" name="qty" step="0.001" value="<?= esc($d['qty']) ?>" style="width:80px">
        <input type="number" name="harga" step="1" value="<?= esc($d['harga']) ?>" style="width:110px">
        <label class="kecil-teks"><input type="checkbox" name="pajak" value="1" style="width:auto"<?= $d['pajak'] ? ' checked' : '' ?>> kena PPN</label>
        <button type="submit" class="kecil abu">Simpan</button>
      </form>
    </td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-hapus/' . $d['id']) ?>" onsubmit="return confirm('Hapus baris ini?')">
        <?= csrf_field() ?>
        <button type="submit" class="kecil bahaya">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr class="baris-total"><th colspan="5" style="text-align:right">TOTAL</th><th class="angka"><?= $rp($grand) ?></th><th class="angka"><?= $rp($grandPpn) ?></th><th colspan="2"></th></tr>
</table>
</div>
<div class="panel">
  <h2>Tambah Baris Barang</h2>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('spk/detail-tambah/' . $s['id']) ?>">
    <?= csrf_field() ?>
    <label>Bahan
      <select name="bahan_id" id="pilihBahan" required>
        <option value="">— pilih bahan —</option>
        <?php foreach ($bahan as $b): ?>
          <option value="<?= (int) $b['id'] ?>" data-harga="<?= (int) $b['harga_acuan'] ?>"><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?> (<?= esc($b['satuan'] ?? '') ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Qty <input type="number" name="qty" step="0.001" min="0" required></label>
    <label>Harga Satuan (kosongkan = harga acuan) <input type="number" name="harga" step="1" id="hargaIsi" placeholder="otomatis"></label>
    <label>Kena PPN
      <select name="pajak"><option value="0">Tidak</option><option value="1">Ya</option></select>
    </label>
    <div class="aksi md:col-span-2"><button type="submit">Tambah Baris</button></div>
  </form>
  <p class="kecil-teks">Harga acuan diambil berjenjang: harga negosiasi → harga penawaran → harga RAB.</p>
</div>
<script>
document.getElementById('pilihBahan').addEventListener('change', function () {
  var h = this.options[this.selectedIndex].getAttribute('data-harga');
  document.getElementById('hargaIsi').placeholder = h ? ('acuan: ' + Number(h).toLocaleString('id-ID')) : 'otomatis';
});
</script>

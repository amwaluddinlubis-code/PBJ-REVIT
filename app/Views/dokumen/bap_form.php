<div class="panel">
<form method="post" action="<?= base_url('dokumen/bap-simpan/' . $bap['id']) ?>">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
    <label>Nomor Urut BAP <input type="number" name="nomor_urut" min="1" value="<?= esc($bap['nomor_urut']) ?>" required></label>
    <label>Tanggal Pemeriksaan <input type="date" name="tanggal" value="<?= esc($bap['tanggal'] ?? '') ?>"></label>
  </div>
  <table style="margin-top:14px">
    <tr><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th><th>Kondisi</th><th>Keterangan</th></tr>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><?= esc($it['nama']) ?></td>
      <td class="angka"><?= number_format((float) $it['qty'], 2, ',', '.') ?></td>
      <td><?= esc($it['satuan'] ?? '') ?></td>
      <td>
        <select name="kondisi[<?= (int) $it['bd_id'] ?>]" style="width:140px">
          <?php foreach (['Baik', 'Cukup', 'Kurang', 'Tidak Sesuai'] as $k): ?>
            <option<?= $it['kondisi'] === $k ? ' selected' : '' ?>><?= $k ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><input name="keterangan[<?= (int) $it['bd_id'] ?>]" value="<?= esc($it['keterangan'] ?? '') ?>"></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <div class="aksi md:col-span-2"><button type="submit">Simpan BAP</button>
  <a class="tombol abu" href="<?= base_url('dokumen') ?>">Kembali</a>
  <a class="tombol" href="<?= base_url('dokumen/cetak-bap/' . $bap['id']) ?>" target="_blank">Cetak</a></div>
</form>
</div>

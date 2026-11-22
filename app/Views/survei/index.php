<div class="panel">
  <h2>Jumlah Calon Penyedia Menurut Juknis Revitalisasi</h2>
  <p style="margin-bottom:0">Perbandingan harga wajib memenuhi jumlah minimum calon penyedia menurut nilai paket pengadaan: sampai dengan <strong>Rp50 juta</strong> boleh pemilihan langsung atau negosiasi harga (1 penyedia); <strong>Rp50–200 juta</strong> perbandingan dengan paling sedikit <strong>2 calon penyedia</strong>; di atas <strong>Rp200 juta</strong> paling sedikit <strong>3 calon penyedia</strong>. Isi harga dari toko-toko pembanding di bawah ini sebagai bukti perbandingan — panel pada halaman detail SPK akan memperingatkan bila jumlah toko pembanding untuk bahan paket itu masih kurang dari minimum.</p>
</div>
<div data-bulk-akar x-data="komponenBulk('form-bulk-survei','ids-survei')">
<div class="panel">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <form id="form-bulk-survei" method="post" action="<?= base_url('survei/hapus-massal') ?>" data-konfirmasi-judul="Hapus Harga Survei Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-survei"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus harga survei {n} bahan terpilih (semua toko)?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('survei/kosongkan') ?>" data-konfirmasi="Kosongkan seluruh harga survei sekolah ini? Semua harga toko untuk semua bahan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Harga Survei" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Harga Survei</button>
    </form>
    <span class="kecil-teks">Centang bahan pada tabel di bawah untuk menghapus harga surveinya.</span>
  </div>
</div>
<div class="panel">
  <p>Isi harga dari tiap toko hasil survei. Kolom <strong>Termurah</strong> dan <strong>Toko Termurah</strong> terhitung otomatis — harga termurah itulah yang menjadi <em>Harga Terpilih</em> untuk negosiasi dan acuan SPK.</p>
  <form method="post" action="<?= base_url('survei/simpan') ?>">
    <?= csrf_field() ?>
    <label>Tanggal Survei <input type="date" name="tanggal_survei" value="<?= esc($identitas['tanggal_survei'] ?? '') ?>" style="max-width:220px"></label>
    <div style="overflow-x:auto; margin-top:12px">
    <table data-paginasi="25" data-cari="Cari bahan…">
      <tr><th style="width:34px"><input type="checkbox" title="Pilih semua bahan" @change="pilihSemua($event)"></th><th>Kode</th><th>Nama Bahan</th><th>Satuan</th>
        <?php for ($t = 1; $t <= 4; $t++): ?><th class="angka"><?= esc($identitas['toko' . $t] ?? ('Toko ' . $t)) ?></th><?php endfor; ?>
        <th class="angka">Termurah</th><th>Toko Termurah</th></tr>
      <?php foreach ($bahan as $b): 
        $vals = [];
        for ($t = 1; $t <= 4; $t++) { $v = $harga[$b['id']][$t] ?? null; if ($v !== null && $v !== '') $vals[$t] = (int) $v; }
        $min = $vals ? min($vals) : null; $tokoMin = $min !== null ? array_search($min, $vals, true) : null;
      ?>
      <tr>
        <td><input type="checkbox" data-pilih value="<?= (int) $b['id'] ?>" x-model="terpilih"></td>
        <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td><td><?= esc($b['satuan'] ?? '') ?></td>
        <?php for ($t = 1; $t <= 4; $t++): ?>
          <td class="angka"><input type="text" inputmode="numeric" data-uang name="harga[<?= (int) $b['id'] ?>][<?= $t ?>]" min="0" step="1" value="<?= esc($harga[$b['id']][$t] ?? '') ?>" style="width:120px"></td>
        <?php endfor; ?>
        <td class="angka"><?= $min !== null ? 'Rp ' . number_format($min, 0, ',', '.') : '—' ?></td>
        <td><?= $tokoMin !== null ? esc($identitas['toko' . $tokoMin] ?? ('Toko ' . $tokoMin)) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <div class="aksi md:col-span-2"><button type="submit">Simpan Harga Survei</button></div>
  </form>
</div>
</div>

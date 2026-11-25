<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <h2>Catat Barang Keluar</h2>
  <p class="kecil-teks">Barang <strong>masuk tercatat otomatis dari BAST</strong> — tanggalnya mengikuti tanggal BAST — jadi di sini Bapak/Ibu hanya mencatat barang <strong>keluar</strong>. Pilih gedung tujuan dan bahan; petunjuk di bawah form menunjukkan kebutuhan gedung itu dan stok tersedia bahan ini, agar tidak salah catat. Pilihan bahan hanya menampilkan bahan yang masih berstok (penerimaan BAST dikurangi yang sudah keluar).</p>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('material/simpan') ?>">
    <?= csrf_field() ?>
    <label>Tanggal <input type="date" name="tanggal"></label>
    <label>No. BPB <input name="no_bpb"></label>
    <label>Gedung Tujuan
      <?= select_cari('gedung_id', array_map(static fn ($g) => ['value' => (int) $g['id'], 'label' => $g['nama']], $gedung ?? []), null, ['placeholder' => '— gudang bersama / belum ditentukan —']) ?>
    </label>
    <label>Bahan
      <?php if (! empty($bahan_pilih)): ?>
      <?= select_cari('bahan_id', array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama'] . ' — sisa ' . number_format((float) $b['stok'], 2, ',', '.') . ' ' . ($b['satuan'] ?? ''), 'data' => ['stok' => (float) $b['stok'], 'satuan' => (string) ($b['satuan'] ?? '')]], $bahan_pilih), null, ['required' => true, 'placeholder' => '— pilih bahan —']) ?>
      <?php else: ?>
      <span class="kecil-teks">Belum ada bahan yang berstok. Pilihan bahan tampil sesudah ada SPK yang di-BAST — periksa di <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</span>
      <?php endif; ?>
    </label>
    <label>Jumlah Keluar <input type="number" name="keluar" step="0.001" value="0"></label>
    <label>Keterangan <input name="keterangan"></label>
    <p class="kecil-teks md:col-span-2" id="info-catatan" hidden style="margin:0"></p>
    <div class="aksi md:col-span-2"><button type="submit">Catat</button></div>
  </form>
</div>
<div class="panel">
  <h2>Kebutuhan Bahan per Gedung</h2>
  <p class="kecil-teks">Kebutuhan dihitung dari BOQ tiap gedung (volume × koefisien analisa). Bandingkan dengan yang sudah keluar ke gedung itu sebelum mencatat. Baris merah = keluar ke gedung itu sudah melebihi kebutuhannya (atau bahan itu bukan kebutuhan gedung tersebut). Gedung diurutkan dari persentase sisa kebutuhan terbesar — yang paling belum terpenuhi tampil lebih dulu.</p>
  <?php if (! empty($semua_kebutuhan_gedung)): ?>
  <form method="get" action="<?= base_url('material') ?>" style="margin:0 0 10px">
    <label>Saring gedung
      <select name="f_gedung" onchange="this.form.submit()">
        <option value="0"<?= ($f_gedung ?? 0) === 0 ? ' selected' : '' ?>>— semua gedung —</option>
        <?php foreach ($semua_kebutuhan_gedung as $kg): ?>
        <option value="<?= (int) $kg['gedung']['id'] ?>"<?= ($f_gedung ?? 0) === (int) $kg['gedung']['id'] ? ' selected' : '' ?>><?= esc($kg['gedung']['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
  <?php endif; ?>
  <?php if (! empty($kebutuhan_gedung)): ?>
  <table data-cari="Cari gedung / bahan…">
    <tr><th>Bahan</th><th class="angka">Kebutuhan</th><th class="angka">Sudah Keluar</th><th class="angka">Sisa Kebutuhan</th></tr>
    <?php foreach ($kebutuhan_gedung as $kg): ?>
    <tr class="baris-grup"><td colspan="4"><strong><?= esc($kg['gedung']['nama']) ?></strong><?php if (($kg['persen_sisa'] ?? -1) >= 0): ?> <span class="kecil-teks">· sisa kebutuhan <?= number_format((float) $kg['persen_sisa'], 1, ',', '.') ?>%</span><?php endif; ?></td></tr>
    <?php foreach ($kg['baris'] as $r): ?>
    <tr<?= $r['sisa'] < -0.0005 ? ' class="negatif"' : '' ?>>
      <td><?= esc($r['kode']) ?> — <?= esc($r['nama']) ?> (<?= esc($r['satuan'] ?? '') ?>)</td>
      <td class="angka"><?= $fmt($r['butuh']) ?></td>
      <td class="angka"><?= $fmt($r['keluar']) ?></td>
      <td class="angka"><?= $fmt($r['sisa']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
  <p class="kecil-teks" style="margin:0">Belum ada kebutuhan bahan terhitung — kebutuhan berasal dari BOQ pada halaman <a href="<?= base_url('gedung') ?>">Gedung</a>.</p>
  <?php endif; ?>
</div>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-material','ids-material')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-material" method="post" action="<?= base_url('material/hapus-massal') ?>" data-konfirmasi-judul="Hapus Baris Buku Material Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-material"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} baris buku material terpilih? Sisa stok akan berubah.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('material/kosongkan') ?>" data-konfirmasi="Kosongkan buku material sekolah ini? Semua catatan KELUAR akan dihapus (hapus lunak); catatan masuk dari BAST tidak terpengaruh dan stok kembali sebesar penerimaan BAST." data-konfirmasi-judul="Kosongkan Buku Material" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Buku Material</button>
    </form>
  </div>
  <p class="kecil-teks" style="margin:0 0 10px">Baris bertanda <strong>Otomatis</strong> berasal dari BAST (tanggal = tanggal BAST) dan tidak dapat dihapus dari sini. Catatan masuk manual lama tidak lagi ditampilkan dan tidak dihitung — stok murni penerimaan BAST dikurangi catatan keluar.</p>
  <table data-paginasi="10" data-cari="Cari catatan material…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua baris" @change="pilihSemua($event)"></th><th>Tanggal</th><th>No. BPB</th><th>Bahan</th><th>Gedung</th><th class="angka">Masuk</th><th class="angka">Keluar</th><th class="angka">Sisa Stok</th><th>Keterangan</th><th></th></tr>
    <?php foreach ($baris as $r): ?>
    <tr>
      <td><?php if (! empty($r['otomatis'])): ?><span class="kecil-teks">Otomatis</span><?php else: ?><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"><?php endif; ?></td>
      <td><?= esc(tgl($r['tanggal'] ?? null)) ?></td><td><?= esc($r['no_bpb'] ?? '') ?></td>
      <td><?= esc($r['kode']) ?> — <?= esc($r['nama']) ?> (<?= esc($r['satuan'] ?? '') ?>)</td>
      <td><?= esc($r['gedung_nama'] ?? '—') ?></td>
      <td class="angka"><?= $fmt($r['masuk']) ?></td><td class="angka"><?= $fmt($r['keluar']) ?></td>
      <td class="angka"><?= $fmt($r['sisa']) ?></td><td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><?php if (empty($r['otomatis'])): ?><form class="inline" method="post" action="<?= base_url('material/hapus/' . $r['id']) ?>" data-konfirmasi="Hapus baris ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (! $baris): ?><tr><td colspan="10">Belum ada catatan — catatan masuk muncul otomatis sesudah ada BAST, catatan keluar dicatat dari form di atas.</td></tr><?php endif; ?>
  </table>
</div>
<?php if (! empty($rekap_gedung)): ?>
<div class="panel">
  <h2>Rekap Keluar per Gedung</h2>
  <p class="kecil-teks">Hanya dari catatan keluar yang gedung tujuannya diisi — menunjukkan pemakaian tiap gedung, mis. bahan dari satu SPK gabungan yang dipakai masing-masing gedung. Stok utama tetap gabungan se-sekolah (penerimaan BAST dikurangi total keluar).</p>
  <table data-cari="Cari gedung / bahan…">
    <tr><th>Gedung</th><th>Bahan</th><th class="angka">Sudah Keluar</th></tr>
    <?php foreach ($rekap_gedung as $r): ?>
    <tr>
      <td><strong><?= esc($r['gedung_nama']) ?></strong></td>
      <td><?= esc($r['kode']) ?> — <?= esc($r['nama']) ?> (<?= esc($r['satuan'] ?? '') ?>)</td>
      <td class="angka"><?= $fmt($r['keluar']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<script>
(function () {
  var peta = <?= json_encode($peta_info ?? [], JSON_NUMERIC_CHECK) ?>;
  var form = document.querySelector('form[action$="material/simpan"]');
  if (!form) return;
  var selG = form.querySelector('select[name="gedung_id"]');
  var selB = form.querySelector('select[name="bahan_id"]');
  var info = document.getElementById('info-catatan');
  if (!selB || !info) return;
  var fmt = function (n) { return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 }); };
  function segarkan() {
    if (!selB.value) { info.hidden = true; return; }
    var op = selB.options[selB.selectedIndex];
    var teks = 'Stok tersedia bahan ini: ' + fmt(parseFloat(op.dataset.stok || '0')) + ' ' + (op.dataset.satuan || '') + ' (penerimaan BAST dikurangi yang sudah keluar).';
    if (selG && selG.value) {
      var namaG = selG.options[selG.selectedIndex].textContent;
      var e = (peta[selG.value] || {})[selB.value];
      if (e) {
        teks += ' Kebutuhan ' + namaG + ': ' + fmt(e.butuh) + ' ' + e.satuan + ' · sudah keluar ke gedung ini: ' + fmt(e.keluar) + ' · sisa kebutuhan: ' + fmt(e.sisa) + '.';
      } else {
        teks += ' Bahan ini TIDAK termasuk kebutuhan ' + namaG + ' — periksa lagi gedung tujuannya.';
      }
    }
    info.textContent = teks;
    info.hidden = false;
  }
  selB.addEventListener('change', segarkan);
  if (selG) selG.addEventListener('change', segarkan);
})();
</script>

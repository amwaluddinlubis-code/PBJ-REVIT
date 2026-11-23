<?php /* Partial invoice/index: modal form Invoice (baru & ubah) — bidang sama persis dengan invoice/form.php; halaman form mandiri tetap ada sebagai cadangan. POST biasa ke invoice/simpan atau invoice/perbarui/{id}. */ ?>
<div class="mk-lapik" id="modal-invoice" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="minv-judul" style="max-width:720px;width:94vw">
    <h3 id="minv-judul" style="margin-top:0">Invoice Baru</h3>
    <form id="form-invoice-modal" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('invoice/simpan') ?>">
      <?= csrf_field() ?>
      <label>Nomor Urut <input type="number" name="nomor_urut" min="1" required></label>
      <label>No. Invoice Toko <input name="no_toko"></label>
      <label>Tanggal <input type="date" name="tanggal"></label>
      <label>SPK
        <select name="spk_id" required data-select-cari data-placeholder="— pilih SPK —">
          <option value="">— pilih SPK —</option>
          <?php foreach ($spk['list'] ?? [] as $s): ?>
            <option value="<?= (int) $s['id'] ?>" data-total="<?= (int) $s['total'] ?>">SPK <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?> (total Rp <?= number_format((float) $s['total'], 0, ',', '.') ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>BAP
        <select data-select-cari name="bap_id">
          <option value="">— belum ada —</option>
          <?php foreach ($spk['bap'] ?? [] as $sid => $daftar): foreach ($daftar as $b): ?>
            <option value="<?= (int) $b['id'] ?>" data-spk="<?= (int) $sid ?>">BAP urut <?= (int) $b['nomor_urut'] ?> (SPK id <?= (int) $sid ?>)</option>
          <?php endforeach; endforeach; ?>
        </select>
      </label>
      <label>BAST
        <select data-select-cari name="bast_id">
          <option value="">— belum ada —</option>
          <?php foreach ($spk['bast'] ?? [] as $sid => $daftar): foreach ($daftar as $b): ?>
            <option value="<?= (int) $b['id'] ?>" data-spk="<?= (int) $sid ?>">BAST urut <?= (int) $b['nomor_urut'] ?> (SPK id <?= (int) $sid ?>)</option>
          <?php endforeach; endforeach; ?>
        </select>
      </label>
      <label>Nilai Invoice (kosongkan = total SPK) <input type="text" inputmode="numeric" data-uang name="nilai"></label>
      <label>Status
        <select data-select-cari name="status">
          <?php foreach (['Belum Bayar', 'Sudah Bayar'] as $st): ?>
            <option><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="aksi md:col-span-2">
        <button type="submit">Simpan</button>
        <button type="button" class="abu" onclick="tutupModalInvoice()">Batal</button>
      </div>
    </form>
    <form id="form-invoice-hapus" method="post" action="" data-konfirmasi="Hapus invoice ini?" style="margin-top:14px" hidden>
      <?= csrf_field() ?><button type="submit" class="bahaya kecil">Hapus Invoice</button>
    </form>
  </div>
</div>
<script type="application/json" id="data-invoice"><?= json_encode(array_column($inv ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
(function () {
  var lapik = document.getElementById('modal-invoice');
  var form = document.getElementById('form-invoice-modal');
  var judul = document.getElementById('minv-judul');
  var formHapus = document.getElementById('form-invoice-hapus');
  var dataInv = {};
  try { dataInv = JSON.parse(document.getElementById('data-invoice').textContent || '{}'); } catch (e) {}
  var isoKeDmy = function (v) {
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(v || '');
    return m ? m[3] + '/' + m[2] + '/' + m[1] : '';
  };
  var isi = function (nama, nilai) {
    var el = form.elements[nama];
    if (!el) return;
    el.value = nilai == null ? '' : nilai;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  };
  window.bukaInvoiceBaru = function () {
    form.reset();
    form.action = '<?= base_url('invoice/simpan') ?>';
    judul.textContent = 'Invoice Baru';
    isi('nomor_urut', '<?= (int) ($nomor_baru ?? 1) ?>');
    isi('status', 'Belum Bayar');
    formHapus.hidden = true;
    lapik.hidden = false;
  };
  window.bukaInvoiceUbah = function (id) {
    var i = dataInv[id];
    if (!i) return;
    form.reset();
    form.action = '<?= base_url('invoice/perbarui') ?>/' + id;
    judul.textContent = 'Ubah Invoice ' + (i.nomor_urut || '');
    isi('nomor_urut', i.nomor_urut);
    isi('no_toko', i.no_toko);
    var tgl = form.elements['tanggal'];
    if (tgl) { tgl.value = isoKeDmy(i.tanggal); tgl.dispatchEvent(new Event('change', { bubbles: true })); }
    isi('spk_id', i.spk_id || '');
    isi('bap_id', i.bap_id || '');
    isi('bast_id', i.bast_id || '');
    var nilai = form.elements['nilai'];
    if (nilai) nilai.value = window.formatUang ? window.formatUang(i.nilai || '') : (i.nilai || '');
    isi('status', i.status || 'Belum Bayar');
    formHapus.action = '<?= base_url('invoice/hapus') ?>/' + id;
    formHapus.hidden = false;
    lapik.hidden = false;
  };
  window.tutupModalInvoice = function () { lapik.hidden = true; };
  lapik.addEventListener('click', function (e) { if (e.target === lapik) tutupModalInvoice(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lapik.hidden) tutupModalInvoice(); });
})();
</script>

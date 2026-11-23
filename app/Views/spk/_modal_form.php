<?php /* Partial spk/index: modal form SPK (baru & ubah) — bidang sama persis dengan spk/form.php; halaman form mandiri tetap ada sebagai cadangan. Dikirim sebagai POST biasa ke spk/simpan atau spk/perbarui/{id}. */ ?>
<div class="mk-lapik" id="modal-spk" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="mspk-judul" style="max-width:720px;width:94vw">
    <h3 id="mspk-judul" style="margin-top:0">SPK Baru</h3>
    <form id="form-spk-modal" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('spk/simpan') ?>">
      <?= csrf_field() ?>
      <label>Nomor SPK <input type="number" name="nomor" min="1" required></label>
      <label>Tanggal <input type="date" name="tanggal"></label>
      <label>Penyedia
        <select name="penyedia_id" data-select-cari data-placeholder="— pilih / isi baru di kanan —">
          <option value="">— pilih / isi baru di kanan —</option>
          <?php foreach ($penyedia ?? [] as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Penyedia Baru (bila belum ada) <input name="penyedia_baru" value=""></label>
      <label>Nama Penerima (khusus penyedia "Masyarakat") <input name="penerima_manual" placeholder="mis. nama warga penjual pasir/batu/kayu">
        <span class="kecil-teks">Bila penyedia dipilih "Masyarakat", isi nama orangnya di sini — nama ini yang tampil menggantikan nama penyedia di SPK, BAP, BAST, invoice, dan cetakan turunannya.</span>
      </label>
      <label>Nama Penandatangan Penyedia <input name="ttd_penyedia" placeholder="kosongkan = memakai Nama Penerima"></label>
      <label>Gedung Tujuan
        <select data-select-cari name="gedung_id">
          <option value="">— umum / belum ditentukan —</option>
          <?php foreach ($semuaGedung ?? [] as $gd): ?>
            <option value="<?= (int) $gd['id'] ?>"><?= esc($gd['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Paket Pekerjaan <input name="paket"></label>
      <label>Tanggal Mulai <input type="date" name="tgl_mulai"></label>
      <label>Tanggal Selesai <input type="date" name="tgl_selesai"></label>
      <label>Status
        <select data-select-cari name="status">
          <?php foreach (['Aktif', 'Selesai', 'Batal'] as $st): ?>
            <option><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="aksi md:col-span-2">
        <button type="submit">Simpan</button>
        <button type="button" class="abu" onclick="tutupModalSpk()">Batal</button>
      </div>
    </form>
    <form id="form-spk-hapus" method="post" action="" data-konfirmasi="Hapus SPK ini? Rincian barangnya menjadi belum bernomor." style="margin-top:14px" hidden>
      <?= csrf_field() ?>
      <button type="submit" class="bahaya kecil">Hapus SPK</button>
    </form>
  </div>
</div>
<script type="application/json" id="data-spk"><?= json_encode(array_column($spk ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
(function () {
  var lapik = document.getElementById('modal-spk');
  var form = document.getElementById('form-spk-modal');
  var judul = document.getElementById('mspk-judul');
  var formHapus = document.getElementById('form-spk-hapus');
  var dataSpk = {};
  try { dataSpk = JSON.parse(document.getElementById('data-spk').textContent || '{}'); } catch (e) {}
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
  var isiTanggal = function (nama, nilai) {
    var el = form.elements[nama];
    if (!el) return;
    el.value = isoKeDmy(nilai);
    el.dispatchEvent(new Event('change', { bubbles: true }));
  };
  window.bukaSpkBaru = function () {
    form.reset();
    form.action = '<?= base_url('spk/simpan') ?>';
    judul.textContent = 'SPK Baru';
    isi('nomor', '<?= (int) ($nomor_baru ?? 1) ?>');
    isi('status', 'Aktif');
    formHapus.hidden = true;
    lapik.hidden = false;
  };
  window.bukaSpkUbah = function (id) {
    var s = dataSpk[id];
    if (!s) return;
    form.reset();
    form.action = '<?= base_url('spk/perbarui') ?>/' + id;
    judul.textContent = 'Ubah SPK ' + (s.nomor || '');
    isi('nomor', s.nomor);
    isiTanggal('tanggal', s.tanggal);
    isi('penyedia_id', s.penyedia_id || '');
    isi('penerima_manual', s.penerima_manual);
    isi('ttd_penyedia', s.ttd_penyedia);
    isi('gedung_id', s.gedung_id || '');
    isi('paket', s.paket);
    isiTanggal('tgl_mulai', s.tgl_mulai);
    isiTanggal('tgl_selesai', s.tgl_selesai);
    isi('status', s.status || 'Aktif');
    formHapus.action = '<?= base_url('spk/hapus') ?>/' + id;
    formHapus.hidden = false;
    lapik.hidden = false;
  };
  window.tutupModalSpk = function () { lapik.hidden = true; };
  lapik.addEventListener('click', function (e) { if (e.target === lapik) tutupModalSpk(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lapik.hidden) tutupModalSpk(); });
})();
</script>

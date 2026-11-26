<div class="panel">
<form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url($aksi) ?>">
  <?= csrf_field() ?>
  <label>Nomor Urut <input type="number" name="nomor_urut" min="1" value="<?= esc(old('nomor_urut', $i['nomor_urut'] ?? '')) ?>" required></label>
  <label>No. Invoice Toko <input name="no_toko" value="<?= esc(old('no_toko', $i['no_toko'] ?? '')) ?>"></label>
  <label>Tanggal <input type="date" name="tanggal" value="<?= esc(old('tanggal', $i['tanggal'] ?? '')) ?>" min="<?= esc($buku_min ?? '') ?>" max="<?= esc($buku_max ?? '') ?>"><span class="kecil-teks" style="display:block">Buku terbuka: <?= esc($buku_label ?? '') ?></span></label>
  <label>SPK
    <select name="spk_id" id="pilihSpk" required data-select-cari data-placeholder="— pilih SPK —">
      <?php foreach ($spk['list'] as $s): $w = $spk['warisan'][(int) $s['id']] ?? []; ?>
        <option value="<?= (int) $s['id'] ?>" data-total="<?= (int) $s['total'] ?>" data-sisa="<?= (int) ($w['sisa'] ?? 0) ?>" data-bast-tanggal="<?= esc($w['bast']['tanggal'] ?? '') ?>"<?= (old('spk_id', $i['spk_id'] ?? '') == $s['id']) ? ' selected' : '' ?>>SPK <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?> (total Rp <?= number_format((float) $s['total'], 0, ',', '.') ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="md:col-span-2"><span class="kecil-teks" id="info-warisan-invoice">Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.</span></div>
  <label>Nilai Invoice (otomatis = sisa nilai BAST, boleh diubah) <input type="text" inputmode="numeric" data-uang name="nilai" step="1" value="<?= esc(old('nilai', $i['nilai'] ?? '')) ?>"></label>
  <label>Status
    <select data-select-cari name="status">
      <?php foreach (['Belum Bayar', 'Sudah Bayar'] as $st): ?>
        <option<?= (old('status', $i['status'] ?? 'Belum Bayar') === $st) ? ' selected' : '' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="aksi md:col-span-2"><button type="submit">Simpan</button><a class="tombol abu" href="<?= base_url('invoice') ?>">Batal</a></div>
</form>
<?php if (! empty($i['id'])): ?>
<form method="post" action="<?= base_url('invoice/hapus/' . $i['id']) ?>" data-konfirmasi="Hapus invoice ini?" style="margin-top:14px">
  <?= csrf_field() ?><button type="submit" class="bahaya kecil">Hapus Invoice</button>
</form>
<?php endif; ?>
</div>
<script type="application/json" id="data-warisan-spk"><?= json_encode($spk['warisan'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
// Warisan SPK: tanggal SPK + BAP/BAST terakhir ditampilkan dari SPK
// terpilih; server mewarisi bap_id/bast_id dari SPK saat menyimpan.
(function () {
  var warisan = {};
  try { warisan = JSON.parse(document.getElementById('data-warisan-spk').textContent || '{}'); } catch (e) {}
  function tglIndo(iso) {
    if (!iso) return '—';
    var p = String(iso).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  var nilaiSendiri = <?= (int) ($i['nilai'] ?? 0) ?>;
  var spkSendiri = <?= json_encode((string) ($i['spk_id'] ?? '')) ?>;
  function rpIndo(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
  function sisaEfektif(spkId) {
    var w = warisan[spkId];
    if (!w) return 0;
    var sisa = Number(w.sisa || 0);
    if (spkSendiri !== '' && String(spkSendiri) === String(spkId)) sisa += nilaiSendiri;
    return sisa;
  }
  function saring() {
    var sel = document.getElementById('pilihSpk');
    var tglInv = document.querySelector('input[name="tanggal"]');
    if (!sel) return;
    var tgl = tglInv ? tglInv.value : '';
    Array.prototype.forEach.call(sel.options, function (opt) {
      if (!opt.value) return;
      var bastTgl = opt.getAttribute('data-bast-tanggal') || '';
      var layak = bastTgl !== '' && sisaEfektif(opt.value) > 0 && (!tgl || bastTgl <= tgl);
      opt.disabled = !layak && opt.value !== sel.value;
    });
    if (window.perbaruiSelectCari) window.perbaruiSelectCari(sel);
  }
  function tampil(isiNilai) {
    var sel = document.getElementById('pilihSpk');
    var kotak = document.getElementById('info-warisan-invoice');
    if (!sel || !kotak) return;
    var w = warisan[sel.value];
    if (!w) { kotak.textContent = 'Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.'; return; }
    var bap = w.bap ? 'BAP urut ' + w.bap.nomor_urut + ' tgl ' + tglIndo(w.bap.tanggal) + ' (' + rpIndo(w.nilai_bap) + ')' : 'BAP belum ada';
    var bast = w.bast ? 'BAST urut ' + w.bast.nomor_urut + ' tgl ' + tglIndo(w.bast.tanggal) + ' (' + rpIndo(w.nilai_bast) + ')' : 'BAST belum ada';
    kotak.textContent = 'Diwarisi dari SPK: tgl SPK ' + tglIndo(w.tanggal) + ' · ' + bap + ' · ' + bast + ' · sisa belum ter-invoice ' + rpIndo(sisaEfektif(sel.value)) + '.';
    if (isiNilai) {
      var nilai = document.querySelector('input[name="nilai"]');
      if (nilai) { nilai.value = sisaEfektif(sel.value); nilai.dispatchEvent(new Event('input', { bubbles: true })); }
    }
  }
  var sel = document.getElementById('pilihSpk');
  if (sel) sel.addEventListener('change', function () { tampil(true); });
  var tglInv = document.querySelector('input[name="tanggal"]');
  if (tglInv) tglInv.addEventListener('change', saring);
  saring();
  tampil(false);
})();
</script>

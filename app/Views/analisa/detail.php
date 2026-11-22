<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb'];
$total = 0.0;
foreach ($komponen as $k) {
    $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0);
}
?>
<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('analisa') ?>">Analisa Pekerjaan</a> <span aria-hidden="true">/</span> <span><?= esc($a['kode']) ?></span></nav>
<div class="panel">
  <h2 style="margin-top:0"><?= esc($a['kode']) ?> — <?= esc($a['nama'] ?? '') ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:99px;font-size:12px;vertical-align:middle">harga satuan belum terverifikasi</span><?php endif; ?></h2>
  <?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px">Harga satuan analisa ini tidak terbaca dari sheet Harsat dokumen sumber (tertulis #REF! di dokumennya). Menyimpan perubahan komponen di bawah akan mengisinya dari jumlah komponen.</p>
  <?php endif; ?>
  <p class="kecil-teks" id="ringkas-analisa" style="margin-top:0">Satuan analisa: <strong><?= esc($a['satuan'] ?? '-') ?></strong> · Harga satuan tersimpan: <strong><?= $rp($a['harga_satuan'] ?? 0) ?></strong> · Jumlah komponen: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong> per satuan. Klik Ubah pada komponen untuk mengubah koefisien/tarif lewat modal — kebutuhan bahan &amp; upah di semua gedung yang memakai analisa ini langsung berubah.</p>
  <p style="margin:0 0 10px"><button type="button" class="tombol" id="btn-tambah-komp">+ Tambah Komponen</button></p>

  <div id="wadah-komponen">
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari komponen…">
      <tr><th>Jenis</th><th>Komponen</th><th class="angka">Koefisien</th><th>Satuan</th><th class="angka">Tarif (Rp)</th><th class="angka">Nilai / Satuan</th><th data-tanpa-urut>Aksi</th></tr>
      <?php foreach ($komponen as $k): $nol = (float) ($k['tarif'] ?? 0) <= 0; ?>
      <tr<?= $nol ? ' style="background:#fffbeb"' : '' ?>>
        <td><span style="background:<?= $lencana[$k['jenis']] ?? '#e5e7eb' ?>;padding:1px 7px;border-radius:99px;font-size:11px"><?= esc(ucfirst($k['jenis'])) ?></span></td>
        <td class="potong" title="<?= esc($k['nama']) ?>"><?= esc($k['nama_master'] ?? $k['nama']) ?><?php if (! empty($k['kode_master'])): ?> <span class="kecil-teks">(<?= esc($k['kode_master']) ?>)</span><?php endif; ?></td>
        <td class="angka"><?= esc(rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ',')) ?></td>
        <td><?= esc($k['satuan'] ?? '') ?></td>
        <td class="angka"><?= number_format((float) ($k['tarif'] ?? 0), 0, ',', '.') ?><?php if ($nol): ?> <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">tarif 0</span><?php endif; ?></td>
        <td class="angka"><?= $rp((float) $k['koefisien'] * (float) ($k['tarif'] ?? 0)) ?></td>
        <td style="white-space:nowrap">
          <button type="button" class="tombol kecil abu" data-ubah-komp data-id="<?= (int) $k['id'] ?>" data-nama="<?= esc($k['nama_master'] ?? $k['nama'], 'attr') ?>" data-jenis="<?= esc(ucfirst($k['jenis']), 'attr') ?>" data-koefisien="<?= esc((string) $k['koefisien'], 'attr') ?>" data-satuan="<?= esc($k['satuan'] ?? '', 'attr') ?>" data-tarif="<?= (int) ($k['tarif'] ?? 0) ?>">Ubah</button>
          <button type="submit" class="tombol kecil abu" form="hapus-komp-<?= (int) $k['id'] ?>">Hapus</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="baris-total"><td colspan="5"><strong>Total komponen per satuan</strong></td><td class="angka"><strong><?= $rp($total) ?></strong></td><td></td></tr>
    </table></div>
    <?php foreach ($komponen as $k): ?>
    <form id="hapus-komp-<?= (int) $k['id'] ?>" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-hapus/' . $k['id']) ?>" data-konfirmasi="Hapus komponen <?= esc($k['nama']) ?> dari analisa ini?" style="display:none"><?= csrf_field() ?></form>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel">
  <h2 style="margin-top:0">Dipakai di Pekerjaan (<?= count($pemakai ?? []) ?>)</h2>
  <?php if (empty($pemakai)): ?>
  <p class="kecil-teks" style="margin-bottom:0">Analisa ini belum dipakai pekerjaan mana pun di gedung aktif sekolah ini.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><table data-paginasi="10" data-cari="Cari gedung / pekerjaan…">
    <tr><th>Gedung</th><th>Pekerjaan</th><th class="angka">Volume Berjalan</th><th data-tanpa-urut>Aksi</th></tr>
    <?php foreach ($pemakai as $pm): $vb = (float) ($pm['volume_revisi'] ?? $pm['volume']); ?>
    <tr>
      <td><a href="<?= base_url('gedung/' . $pm['gedung_id']) ?>"><?= esc($pm['gedung']) ?></a></td>
      <td class="potong" title="<?= esc($pm['nama']) ?>"><?= esc($pm['nama']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format($vb, 3, ',', '.'), '0'), ',') ?> <?= esc($pm['satuan'] ?? '') ?></td>
      <td><a class="tombol kecil" href="<?= base_url('gedung/' . $pm['gedung_id'] . '/boq#pekerjaan-' . $pm['pekerjaan_id']) ?>">Buka Pekerjaan</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <p class="kecil-teks" style="margin-bottom:0">Tombol Buka Pekerjaan menuju baris pekerjaan itu di BOQ gedungnya (halaman digulir &amp; baris disorot).</p>
  <?php endif; ?>
</div>

<div class="mk-lapik" id="modal-ubah-komp" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="judul-ubah-komp" style="max-width:440px;text-align:left">
    <h3 id="judul-ubah-komp" style="text-align:left">Ubah Komponen</h3>
    <p class="kecil-teks" style="text-align:left"><span id="fu-jenis"></span> · <strong id="fu-nama"></strong></p>
    <form id="form-ubah-komp" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-ubah') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="komponen_id" id="fu-id">
      <label style="display:block;text-align:left;margin-bottom:8px">Koefisien
        <input type="text" name="koefisien" id="fu-koef" style="width:100%">
      </label>
      <label style="display:block;text-align:left;margin-bottom:8px">Satuan
        <input type="text" name="satuan" id="fu-satuan" style="width:100%">
      </label>
      <label style="display:block;text-align:left;margin-bottom:8px">Tarif (Rp)
        <input type="text" inputmode="numeric" data-uang name="tarif" id="fu-tarif" style="width:100%">
      </label>
      <p class="kecil-teks" style="text-align:left">Nilai per satuan analisa: <strong id="nilai-ubah">Rp 0</strong> <span style="opacity:.75">(terhitung langsung saat mengetik)</span></p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="tombol abu" data-tutup-modal>Batal</button>
        <button type="submit" class="tombol">Simpan Komponen</button>
      </div>
    </form>
  </div>
</div>

<div class="mk-lapik" id="modal-tambah-komp" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="judul-tambah-komp" style="max-width:520px;text-align:left">
    <h3 id="judul-tambah-komp" style="text-align:left">Tambah Komponen</h3>
    <form id="form-tambah-komp" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-tambah') ?>">
      <?= csrf_field() ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:left">
        <label>Jenis
          <select name="jenis" data-select-cari>
            <option value="bahan">Bahan</option>
            <option value="upah">Upah</option>
            <option value="alat">Alat</option>
          </select>
        </label>
        <label>Bahan dari master (jenis Bahan)
          <?= select_cari('bahan_id', array_merge([['value' => '', 'label' => '— ketik nama manual —']], array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama']], $bahan))) ?>
        </label>
        <label>Nama komponen <input type="text" name="nama" placeholder="nama komponen"></label>
        <label>Koefisien <input type="text" name="koefisien" id="ft-koef" placeholder="0,0000"></label>
        <label>Satuan <input type="text" name="satuan" placeholder="m3 / OH / zak"></label>
        <label>Tarif (Rp) <input type="text" inputmode="numeric" data-uang name="tarif" id="ft-tarif" placeholder="otomatis bila dari master"></label>
      </div>
      <p class="kecil-teks" style="text-align:left">Nilai per satuan dari isian ini: <strong id="nilai-tambah">Rp 0</strong>. Untuk jenis Upah, isi Nama persis seperti jenis di Master Upah agar tarif master berlaku; untuk Bahan dari master, tarif kosong memakai harga acuan bahan.</p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="tombol abu" data-tutup-modal>Batal</button>
        <button type="submit" class="tombol">+ Tambah Komponen</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
  var angka = function (s) {
    s = String(s || '').trim();
    if (!s) return 0;
    if (s.indexOf(',') >= 0) s = s.replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
  };
  var uang = function (s) { return parseFloat(String(s || '').replace(/[^0-9]/g, '')) || 0; };
  var pemicu = null;
  function buka(id, btn) {
    pemicu = btn || null;
    var m = document.getElementById(id);
    m.hidden = false;
    var f = m.querySelector('input:not([type=hidden]), select');
    if (f) f.focus();
  }
  function tutup(m) { m.hidden = true; if (pemicu) pemicu.focus(); }
  document.querySelectorAll('.mk-lapik').forEach(function (m) {
    m.addEventListener('click', function (e) { if (e.target === m) tutup(m); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelectorAll('.mk-lapik:not([hidden])').forEach(tutup);
  });
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-tutup-modal]');
    if (t) { tutup(t.closest('.mk-lapik')); return; }
    var u = e.target.closest('[data-ubah-komp]');
    if (u) {
      document.getElementById('fu-id').value = u.dataset.id;
      document.getElementById('fu-nama').textContent = u.dataset.nama;
      document.getElementById('fu-jenis').textContent = u.dataset.jenis;
      document.getElementById('fu-koef').value = u.dataset.koefisien;
      document.getElementById('fu-satuan').value = u.dataset.satuan;
      var tarif = document.getElementById('fu-tarif');
      tarif.value = u.dataset.tarif;
      if (window.inisiasiUang) window.inisiasiUang(document.getElementById('modal-ubah-komp'));
      nilaiUbah();
      buka('modal-ubah-komp', u);
      return;
    }
    if (e.target.closest('#btn-tambah-komp')) buka('modal-tambah-komp', e.target.closest('#btn-tambah-komp'));
  });
  function nilaiUbah() {
    document.getElementById('nilai-ubah').textContent = rp(angka(document.getElementById('fu-koef').value) * uang(document.getElementById('fu-tarif').value));
  }
  ['fu-koef', 'fu-tarif'].forEach(function (i) { document.getElementById(i).addEventListener('input', nilaiUbah); });
  function nilaiTambah() {
    document.getElementById('nilai-tambah').textContent = rp(angka(document.getElementById('ft-koef').value) * uang(document.getElementById('ft-tarif').value));
  }
  ['ft-koef', 'ft-tarif'].forEach(function (i) { document.getElementById(i).addEventListener('input', nilaiTambah); });

  async function segarkan() {
    var html = await (await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
    var doc = new DOMParser().parseFromString(html, 'text/html');
    ['wadah-komponen', 'ringkas-analisa'].forEach(function (id) {
      var baru = doc.getElementById(id), lama = document.getElementById(id);
      if (baru && lama) lama.innerHTML = baru.innerHTML;
    });
    var w = document.getElementById('wadah-komponen');
    if (window.inisiasiPaginasi) window.inisiasiPaginasi(w);
    if (window.inisiasiUang) window.inisiasiUang(w);
  }
  async function kirim(form, modalId) {
    try {
      var r = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      var data = await r.json();
      if (data.ok) {
        tutup(document.getElementById(modalId));
        if (window.tampilToast) window.tampilToast(data.pesan, 'sukses');
        await segarkan();
      } else if (window.tampilToast) {
        window.tampilToast(data.pesan || 'Gagal menyimpan.', 'galat');
      }
    } catch (err) {
      if (window.tampilToast) window.tampilToast('Gagal terhubung ke server.', 'galat');
    }
  }
  document.getElementById('form-ubah-komp').addEventListener('submit', function (e) { e.preventDefault(); kirim(e.target, 'modal-ubah-komp'); });
  document.getElementById('form-tambah-komp').addEventListener('submit', function (e) { e.preventDefault(); kirim(e.target, 'modal-tambah-komp'); });

  var cocok = location.hash.match(/^#komponen-(\d+)$/);
  if (cocok) {
    var b = document.querySelector('[data-ubah-komp][data-id="' + cocok[1] + '"]');
    if (b) b.click();
  }
})();
</script>

<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2 style="margin-top:0">Analisa Pekerjaan (AHSP)</h2>
  <p class="kecil-teks" style="margin-top:0">Analisa menentukan kebutuhan bahan &amp; upah dari volume BOQ. Klik <strong>Buka</strong> untuk mengubah koefisien atau tarif komponen tanpa impor ulang — harga satuan analisa dihitung ulang dari komponennya dan kebutuhan di semua gedung langsung mengikuti.</p>
  <?php if (($belum ?? 0) > 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px"><strong><?= (int) $belum ?> analisa</strong> harga satuannya <strong>belum terverifikasi</strong> — tidak terbaca dari sheet Harsat dokumen sumber (di dokumennya sendiri tertulis #REF!). Harga BOQ tetap yang dipakai untuk nilai pekerjaan; penanda ini hanya agar Bapak tahu analisa mana yang harga satuannya belum bisa dicek silang.</p>
  <?php endif; ?>
  <?php if (! empty($komponen_nol)): ?>
  <details style="margin-bottom:12px">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Komponen Bertarif 0 — <?= count($komponen_nol) ?> komponen belum punya tarif pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk rincian)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><table class="satu-baris" data-cari="Cari komponen…" data-paginasi="25">
      <tr><th>Analisa</th><th>Jenis</th><th>Komponen</th><th class="angka">Koefisien</th><th>Satuan</th><th>Aksi</th></tr>
      <?php foreach ($komponen_nol as $k): ?>
      <tr>
        <td><strong><?= esc($k['kode']) ?></strong> <span class="kecil-teks"><?= esc($k['analisa_nama']) ?></span></td>
        <td><?= esc(ucfirst($k['jenis'])) ?></td>
        <td class="potong" title="<?= esc($k['komponen']) ?>"><?= esc($k['komponen']) ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ',') ?></td>
        <td><?= esc($k['satuan'] ?? '') ?></td>
        <td><a class="tombol kecil" href="<?= base_url('analisa/' . $k['analisa_id']) ?>#komponen-<?= (int) $k['komponen_id'] ?>">Isi Tarif</a></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p class="kecil-teks">Komponen bertarif 0 membuat nilai kebutuhan analisa itu kurang dari seharusnya. Isi tarifnya lewat tombol Isi Tarif, atau dari Master Bahan/Master Upah bila komponennya tertaut master.</p>
  </details>
  <?php endif; ?>
  <?php if (! empty($beda_satuan)): ?>
  <details style="margin-bottom:12px" id="panel-satuan">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Satuan Bahan Berbeda — <?= count($beda_satuan) ?> pasangan satuan komponen ≠ satuan master pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk memperbaiki)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><table class="satu-baris" data-cari="Cari bahan…" data-paginasi="25">
      <tr><th>Bahan</th><th>Satuan Master</th><th>Satuan Komponen</th><th class="angka">Komponen</th><th class="angka">Faktor Berlaku</th><th style="width:260px">Faktor Manual (perbaikan)</th></tr>
      <?php foreach ($beda_satuan as $r): ?>
      <tr>
        <td class="potong" title="<?= esc($r['nama']) ?>"><strong><?= esc($r['kode']) ?></strong> <?= esc($r['nama']) ?></td>
        <td><a href="<?= base_url('bahan/ubah/' . (int) $r['bahan_id']) ?>" title="Klik untuk memperbaiki satuan bahan ini di Master Bahan"><?= esc($r['satuan_master']) ?></a></td>
        <td><?php if (! empty($r['contoh_aid'])): ?><a href="<?= base_url('analisa/' . (int) $r['contoh_aid']) ?>#komponen-<?= (int) $r['contoh_kid'] ?>" title="Klik untuk memperbaiki satuan komponen ini di editor analisa"><?= esc($r['satuan_komponen']) ?></a><?php else: ?><?= esc($r['satuan_komponen']) ?><?php endif; ?></td>
        <td class="angka"><?= (int) $r['jml'] ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $r['faktor_berlaku'], 8, ',', '.'), '0'), ',') ?><?= $r['faktor_manual'] !== null ? ' <span style="background:#dcfce7;color:#166534;padding:1px 7px;border-radius:99px;font-size:11px">manual</span>' : '' ?></td>
        <td>
          <button type="button" class="tombol kecil" data-atur-konversi data-bahan="<?= (int) $r['bahan_id'] ?>" data-nama="<?= esc($r['kode'] . ' — ' . $r['nama'], 'attr') ?>" data-satuan-komponen="<?= esc($r['satuan_komponen'], 'attr') ?>" data-satuan-master="<?= esc($r['satuan_master'], 'attr') ?>" data-faktor="<?= $r['faktor_manual'] !== null ? esc(rtrim(rtrim(number_format((float) $r['faktor_manual'], 8, ',', '.'), '0'), ',')) : esc(rtrim(rtrim(number_format((float) $r['faktor_berlaku'], 8, ',', '.'), '0'), ',')) ?>">Atur Faktor</button>
          <?php if ($r['faktor_manual'] !== null): ?>
          <form class="inline" method="post" action="<?= base_url('analisa/hapus-konversi') ?>" data-konfirmasi="Hapus konversi manual untuk <?= esc($r['nama']) ?> (<?= esc($r['satuan_komponen']) ?>)? Pasangan ini kembali memakai aturan bawaan." style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="bahan_id" value="<?= (int) $r['bahan_id'] ?>">
            <input type="hidden" name="satuan_komponen" value="<?= esc($r['satuan_komponen']) ?>">
            <button type="submit" class="tombol kecil abu">Hapus Manual</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p class="kecil-teks">Faktor = pengali dari satuan komponen ke satuan master (contoh: semen komponen kg, master zak → faktor 0,02 karena 1 kg = 1/50 zak). Faktor manual mengalahkan aturan bawaan dan langsung mengubah hitungan kebutuhan bahan di Gedung, Rencana Belanja, dan LPJ.</p>
  </details>
  <?php endif; ?>
  <div class="aksi" style="justify-content:flex-start;margin-bottom:12px">
    <a class="tombol<?= ($tab ?? 'didanai') === 'didanai' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=didanai') ?>">Dipakai Gedung Didanai (<?= (int) ($jml_didanai ?? 0) ?>)</a>
    <a class="tombol<?= ($tab ?? 'didanai') === 'lainnya' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=lainnya') ?>">Lainnya — Tidak Didanai / Belum Dipakai (<?= (int) ($jml_lainnya ?? 0) ?>)</a>
  </div>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks"><?= ($tab ?? 'didanai') === 'didanai' ? 'Tidak ada analisa yang dipakai oleh gedung yang didanai.' : 'Semua analisa dipakai oleh gedung yang didanai.' ?></p>
  <?php endif; ?>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari data-paginasi="25">
    <tr><th>Kode</th><th>Nama Analisa</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Komponen</th><th class="angka">Dipakai di Pekerjaan</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $a): ?>
    <tr<?= ! empty($a['cari_teks']) ? ' data-cari-teks="' . esc($a['cari_teks'], 'attr') . '"' : '' ?>>
      <td><strong><?= esc($a['kode']) ?></strong></td>
      <td class="potong" title="<?= esc($a['nama'] ?? '') ?>"><?= esc($a['nama'] ?? '') ?></td>
      <td><?= esc($a['satuan'] ?? '') ?></td>
      <td class="angka"><?= $rp($a['harga_satuan'] ?? 0) ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">belum terverifikasi</span><?php endif; ?></td>
      <td class="angka"><?= (int) $a['jml_komponen'] ?></td>
      <td class="angka"><?= (int) $a['dipakai'] ?> pekerjaan<br><span class="kecil-teks"><?= (int) ($a['dipakai_didanai'] ?? 0) ?> di gedung didanai<?= (int) ($a['dipakai_lain'] ?? 0) > 0 ? ' · ' . (int) $a['dipakai_lain'] . ' di gedung tidak didanai' : '' ?></span></td>
      <td><a class="tombol kecil" href="<?= base_url('analisa/' . $a['id']) ?>">Buka</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>

<div class="mk-lapik" id="modal-konversi" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="judul-konversi" style="max-width:440px;text-align:left">
    <h3 id="judul-konversi" style="text-align:left">Atur Faktor Konversi</h3>
    <p class="kecil-teks" style="text-align:left"><strong id="fk-nama"></strong><br>Satuan komponen: <strong id="fk-satuan-komponen"></strong> · Satuan master: <strong id="fk-satuan-master"></strong></p>
    <form id="form-konversi" method="post" action="<?= base_url('analisa/simpan-konversi') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="bahan_id" id="fk-bahan">
      <input type="hidden" name="satuan_komponen" id="fk-satuan">
      <label style="display:block;text-align:left;margin-bottom:8px">Faktor (pengali ke satuan master)
        <input type="text" name="faktor" id="fk-faktor" placeholder="mis. 0,02" style="width:100%">
      </label>
      <p class="kecil-teks" style="text-align:left" id="fk-pratinjau">—</p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="tombol abu" data-tutup-modal>Batal</button>
        <button type="submit" class="tombol">Simpan Faktor</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var pemicu = null;
  var modal = document.getElementById('modal-konversi');
  if (!modal) return;
  function angka(s) {
    s = String(s || '').trim();
    if (!s) return 0;
    if (s.indexOf(',') >= 0) s = s.replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
  }
  function fmt(n) { return String(Math.round(n * 100000000) / 100000000).replace('.', ','); }
  function pratinjau() {
    var f = angka(document.getElementById('fk-faktor').value);
    var sk = document.getElementById('fk-satuan-komponen').textContent;
    var sm = document.getElementById('fk-satuan-master').textContent;
    document.getElementById('fk-pratinjau').textContent = f > 0
      ? 'Berarti: 1 ' + sk + ' = ' + fmt(f) + ' ' + sm + ' · contoh 100 ' + sk + ' terhitung ' + fmt(100 * f) + ' ' + sm + '.'
      : 'Isi faktor untuk melihat artinya. Contoh semen: komponen kg, master zak → faktor 0,02.';
  }
  document.getElementById('fk-faktor').addEventListener('input', pratinjau);
  function tutup() { modal.hidden = true; if (pemicu) pemicu.focus(); }
  modal.addEventListener('click', function (e) { if (e.target === modal) tutup(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) tutup(); });
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-tutup-modal]') && e.target.closest('#modal-konversi')) { tutup(); return; }
    var b = e.target.closest('[data-atur-konversi]');
    if (!b) return;
    pemicu = b;
    document.getElementById('fk-bahan').value = b.dataset.bahan;
    document.getElementById('fk-satuan').value = b.dataset.satuanKomponen;
    document.getElementById('fk-nama').textContent = b.dataset.nama;
    document.getElementById('fk-satuan-komponen').textContent = b.dataset.satuanKomponen;
    document.getElementById('fk-satuan-master').textContent = b.dataset.satuanMaster;
    document.getElementById('fk-faktor').value = b.dataset.faktor;
    pratinjau();
    modal.hidden = false;
    document.getElementById('fk-faktor').focus();
    document.getElementById('fk-faktor').select();
  });
  document.getElementById('form-konversi').addEventListener('submit', async function (e) {
    e.preventDefault();
    try {
      var r = await fetch(e.target.action, { method: 'POST', body: new FormData(e.target), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      var data = await r.json();
      if (data.ok) {
        tutup();
        if (window.tampilToast) window.tampilToast(data.pesan, 'sukses');
        var html = await (await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var baru = doc.getElementById('panel-satuan'), lama = document.getElementById('panel-satuan');
        if (baru && lama) {
          var terbuka = lama.open;
          lama.innerHTML = baru.innerHTML;
          lama.open = terbuka;
          if (window.inisiasiPaginasi) window.inisiasiPaginasi(lama);
        }
      } else if (window.tampilToast) {
        window.tampilToast(data.pesan || 'Gagal menyimpan.', 'galat');
      }
    } catch (err) {
      if (window.tampilToast) window.tampilToast('Gagal terhubung ke server.', 'galat');
    }
  });
})();
</script>

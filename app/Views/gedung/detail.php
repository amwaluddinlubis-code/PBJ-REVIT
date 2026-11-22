<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$tabAktif = $tab ?? 'boq';
?>
<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('gedung') ?>">Kelola Gedung</a> <span aria-hidden="true">/</span> <span><?= esc($g['nama']) ?></span></nav>
<div style="margin-bottom:12px">
  <span class="lencana biru">Nilai BOQ <b>&nbsp;<?= $rp($g['nilai_boq']) ?></b></span>
  <span class="lencana hijau">Realisasi <b>&nbsp;<?= $rp($nilai_realisasi) ?> (<?= number_format($persen_fisik, 1, ',', '.') ?>%)</b></span>
  <?php if (! empty($g['sumber_sheet'])): ?><span class="lencana abu">Sumber sheet: <?= esc($g['sumber_sheet']) ?></span><?php endif; ?>
  <a class="tombol kecil" style="float:right" href="<?= base_url('rencana-belanja?gedung=' . $g['id']) ?>">Buat Rencana Belanja Gedung Ini</a>
</div>
<div class="tab">
  <a href="<?= base_url('gedung/' . $g['id'] . '/boq') ?>"<?= $tabAktif === 'boq' ? ' class="aktif"' : '' ?>>BOQ Pekerjaan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/bahan') ?>"<?= $tabAktif === 'bahan' ? ' class="aktif"' : '' ?>>Kebutuhan Bahan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/upah') ?>"<?= $tabAktif === 'upah' ? ' class="aktif"' : '' ?>>Kebutuhan Upah</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/realisasi') ?>"<?= $tabAktif === 'realisasi' ? ' class="aktif"' : '' ?>>Realisasi</a>
  <a href="<?= base_url('lpj/gedung/' . $g['id']) ?>">LPJ Gedung</a>
</div>

<?php if ($tabAktif === 'boq'): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-pekerjaan','ids-pekerjaan')">
  <h2>BOQ — Daftar Pekerjaan</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-pekerjaan" method="post" action="<?= base_url('gedung/pekerjaan/hapus-massal') ?>" data-konfirmasi-judul="Hapus Pekerjaan Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
      <span id="ids-pekerjaan"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} pekerjaan terpilih dari BOQ gedung ini? Realisasi pekerjaan itu ikut terhapus dan nilai BOQ dihitung ulang.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('gedung/' . $g['id'] . '/kosongkan-boq') ?>" data-konfirmasi="Kosongkan BOQ gedung ini? Semua <?= count($pekerjaan) ?> pekerjaan beserta realisasinya akan dihapus (hapus lunak) dan nilai BOQ menjadi nol." data-konfirmasi-judul="Kosongkan BOQ Gedung" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan BOQ</button>
    </form>
  </div>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari pekerjaan…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua pekerjaan" @change="pilihSemua($event)"></th><th>Pekerjaan</th><th>Kode</th><th class="angka">Volume Awal</th><th class="angka">Vol. Berjalan</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Jumlah Berjalan</th><th style="width:210px">Ubah Volume / Hapus</th></tr>
    <?php $subtotalBab = []; foreach ($pekerjaan as $px) { $kb = (string) ($px['bab'] ?? ''); $subtotalBab[$kb] = ($subtotalBab[$kb] ?? 0) + (float) ($px['volume_revisi'] ?? $px['volume']) * (float) ($px['harga_berjalan'] ?? $px['harga_satuan']); } ?>
    <?php $babLalu = null; $total = 0; foreach ($pekerjaan as $p): $total += (float) ($p['volume_revisi'] ?? $p['volume']) * (float) ($p['harga_berjalan'] ?? $p['harga_satuan']); ?>
      <?php if ($p['bab'] !== $babLalu): $babLalu = $p['bab']; ?>
      <tr><td colspan="7" style="font-weight:600;background:#fcfcfd"><?= esc($babLalu ?: '—') ?></td><td class="angka" style="font-weight:700;background:#fcfcfd"><?= number_format($subtotalBab[(string) ($babLalu ?? '')] ?? 0, 0, ',', '.') ?></td><td style="background:#fcfcfd"></td></tr>
      <?php endif; ?>
      <tr id="pekerjaan-<?= (int) $p['id'] ?>">
        <td><input type="checkbox" data-pilih value="<?= (int) $p['id'] ?>" x-model="terpilih"></td>
        <td class="potong" title="<?= esc($p['nama']) ?>"><?php if (! empty($p['kode_analisa']) && isset($petaAnalisa[$p['kode_analisa']])): ?><button type="button" class="tautan-pekerjaan" data-buka-detail="<?= (int) $p['id'] ?>" title="Klik untuk melihat rincian bahan &amp; upah"><span class="panah">&#9656;</span> <?= esc($p['nama']) ?></button><?php else: ?><?= esc($p['nama']) ?><?php endif; ?></td>
        <td><?php if ($p['kode_analisa']): $idA = $petaAnalisa[$p['kode_analisa']]['id'] ?? ($p['analisa_id'] ?? null); ?><?php if ($idA): ?><a class="lencana abu" style="text-decoration:none" href="<?= base_url('analisa/' . $idA) ?>" title="Buka halaman analisa <?= esc($p['kode_analisa'], 'attr') ?>"><?= esc($p['kode_analisa']) ?></a><?php else: ?><span class="lencana abu"><?= esc($p['kode_analisa']) ?></span><?php endif; ?><?php else: ?>—<?php endif; ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?></td>
        <td class="angka"><?php $vb = (float) ($p['volume_revisi'] ?? $p['volume']); ?><?= rtrim(rtrim(number_format($vb, 3, ',', '.'), '0'), ',') ?><?php if (isset($p['volume_revisi']) && $p['volume_revisi'] !== null): ?> <span class="lencana abu" title="Diubah oleh BA Revisi yang disahkan">Revisi</span><?php endif; ?><?php if (! empty($p['dari_revisi_id'])): ?> <span class="lencana abu" title="Pekerjaan dari BA Revisi">Pengganti/Baru</span><?php endif; ?></td>
        <td><?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= number_format((float) ($p['harga_berjalan'] ?? $p['harga_satuan']), 0, ',', '.') ?></td>
        <td class="angka"><?= number_format($vb * (float) ($p['harga_berjalan'] ?? $p['harga_satuan']), 0, ',', '.') ?></td>
        <td>
          <form class="inline" method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/volume') ?>">
            <?= csrf_field() ?>
            <input type="text" name="volume" value="<?= esc((string) $p['volume']) ?>" style="width:76px;display:inline-block;margin:0;padding:4px 7px">
            <button class="kecil" type="submit">Simpan</button>
          </form>
          <form class="inline" method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/hapus') ?>" data-konfirmasi="Hapus pekerjaan ini?">
            <?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
      <?php if (! empty($p['kode_analisa']) && isset($petaAnalisa[$p['kode_analisa']])): $a = $petaAnalisa[$p['kode_analisa']]; $vol = (float) ($p['volume_revisi'] ?? $p['volume']); ?>
      <tr class="baris-detail" data-detail="<?= (int) $p['id'] ?>">
        <td colspan="9">
          <div class="detail-pekerjaan">
            <div class="detail-kepala">Analisa <strong><?= esc($a['kode']) ?></strong> &mdash; <?= esc($a['nama']) ?> &middot; satuan pekerjaan: <?= esc($a['satuan'] ?? '') ?> &middot; volume: <?= rtrim(rtrim(number_format($vol, 3, ',', '.'), '0'), ',') ?> &middot; harga satuan BOQ: Rp <?= number_format((float) $p['harga_satuan'], 0, ',', '.') ?></div>
            <table class="mini">
              <tr><th>Jenis</th><th>Komponen</th><th class="angka">Koefisien</th><th>Satuan</th><th class="angka">Harga</th><th class="angka">Nilai / Satuan</th><th class="angka">Kebutuhan Total</th></tr>
              <?php $jenisLalu = null; $subTotal = 0.0; $grand = 0.0; foreach ($a['komponen'] as $k): $nilai = (float) $k['koefisien'] * (float) $k['tarif']; ?>
              <?php if ($jenisLalu !== null && $k['jenis'] !== $jenisLalu): ?>
              <tr class="subtotal"><td colspan="5">Subtotal <?= esc(ucfirst($jenisLalu)) ?></td><td class="angka"><?= number_format($subTotal, 0, ',', '.') ?></td><td></td></tr>
              <?php $subTotal = 0.0; endif; $jenisLalu = $k['jenis']; $subTotal += $nilai; $grand += $nilai; $butuh = (float) $k['koefisien'] * $vol; $butuhKonv = $butuh * (float) ($k['faktor'] ?? 1); ?>
              <tr>
                <td><span class="lencana <?= $k['jenis'] === 'bahan' ? 'biru' : ($k['jenis'] === 'upah' ? 'hijau' : 'abu') ?>"><?= esc(ucfirst($k['jenis'])) ?></span></td>
                <td><?= esc($k['nama_master'] ?? $k['nama']) ?><?php if (! empty($k['kode_master'])): ?> <span class="kecil-teks">(<?= esc($k['kode_master']) ?>)</span><?php endif; ?></td>
                <td class="angka"><?= rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ',') ?></td>
                <td><?= esc($k['satuan'] ?? '') ?></td>
                <td class="angka"><?= number_format((float) $k['tarif'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($butuh, 2, ',', '.') ?> <?= esc($k['satuan'] ?? '') ?><?php if (($k['faktor'] ?? 1) != 1 && ! empty($k['satuan_master'])): ?> &asymp; <?= number_format($butuhKonv, 2, ',', '.') ?> <?= esc($k['satuan_master']) ?><?php endif; ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if ($jenisLalu !== null): ?>
              <tr class="subtotal"><td colspan="5">Subtotal <?= esc(ucfirst($jenisLalu)) ?></td><td class="angka"><?= number_format($subTotal, 0, ',', '.') ?></td><td></td></tr>
              <?php endif; ?>
              <tr class="total"><td colspan="5">Total komponen per satuan pekerjaan</td><td class="angka"><?= number_format($grand, 0, ',', '.') ?></td><td class="angka">&times; volume = <?= number_format($grand * $vol, 0, ',', '.') ?></td></tr>
            </table>
            <?php if ((float) ($a['analisa']['harga_satuan'] ?? 0) <= 0): ?>
            <div class="detail-kaki">Harga satuan analisa ini <strong>belum terverifikasi</strong> dari Harsat dokumen sumber (#REF!).</div>
            <?php endif; ?>
            <?php if (! empty($a['tersembunyi'])): ?>
            <div class="detail-kaki"><?= (int) $a['tersembunyi'] ?> komponen disembunyikan karena bahan/upah masternya sudah dihapus dari master.</div>
            <?php endif; ?>
            <?php if (abs($grand - (float) $p['harga_satuan']) > 1): ?>
            <div class="detail-kaki">Catatan: total komponen per satuan di rincian ini (Rp <?= number_format($grand, 0, ',', '.') ?>) dapat berbeda tipis dari harga berjalan (Rp <?= number_format((float) ($p['harga_berjalan'] ?? $p['harga_satuan']), 0, ',', '.') ?>) karena harga berjalan memakai tarif terbesar antara komponen dan Master Upah. Harga satuan dokumen BOQ: Rp <?= number_format((float) $p['harga_satuan'], 0, ',', '.') ?>.</div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endif; ?>
    <?php endforeach; ?>
    <tr class="total"><td colspan="7">Grand Total BOQ Berjalan</td><td class="angka"><?= number_format($total, 0, ',', '.') ?></td><td></td></tr>
  </table></div>
  <p class="kecil-teks">Mengubah volume pekerjaan menghitung ulang kebutuhan bahan &amp; upah gedung ini secara otomatis.</p>
</div>
<script>
// Tautan dalam #pekerjaan-<id> (mis. dari panel "Dipakai di Pekerjaan" di
// halaman Analisa): bersihkan saringan tabel, pindah ke halaman paginasi
// yang memuat barisnya, gulir ke sana, sorot, dan buka rinciannya.
(function () {
  var m = location.hash.match(/^#pekerjaan-(\d+)$/);
  if (!m) return;
  var jalan = function () {
    var baris = document.getElementById('pekerjaan-' + m[1]);
    if (!baris) return;
    var tabel = baris.closest('table');
    if (tabel) {
      var wadah = tabel.closest('.bungkus-tabel') || tabel.parentElement;
      var akar = wadah ? wadah.parentElement : document;
      var cari = akar.querySelector('input[type="search"], .kotak-cari input');
      if (cari && cari.value) { cari.value = ''; cari.dispatchEvent(new Event('input', { bubbles: true })); }
      var berikut = function () { return akar.querySelector('.tombol-halaman button[data-h="next"]:not([disabled])'); };
      var jaga = 0;
      while (baris.style.display === 'none' && berikut() && jaga++ < 60) { berikut().click(); }
    }
    baris.scrollIntoView({ block: 'center' });
    baris.style.background = '#fef3c7';
    var tombol = baris.querySelector('[data-buka-detail]');
    if (tombol) tombol.click();
    setTimeout(function () { baris.style.background = ''; }, 4000);
  };
  // Tunggu paginasi.js selesai menginisialisasi tabel (ia menyembunyikan
  // baris di luar halaman aktif) sebelum memeriksa keterlihatan baris.
  if (document.readyState === 'complete') { setTimeout(jalan, 200); }
  else { window.addEventListener('load', function () { setTimeout(jalan, 200); }); }
})();
</script>
<div class="panel">
  <h2>Tambah Pekerjaan Manual</h2>
  <form method="post" action="<?= base_url('gedung/pekerjaan/simpan') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama pekerjaan<input type="text" name="nama" required></label>
      <label>Bab<input type="text" name="bab" placeholder="mis. II. PEKERJAAN TANAH"></label>
      <label>Kode analisa<input type="text" name="kode_analisa" placeholder="mis. B.01"></label>
      <label>Satuan<input type="text" name="satuan" placeholder="m3 / m2 / ls"></label>
      <label>Volume<input type="text" name="volume" value="0"></label>
      <label>Harga satuan (Rp)<input type="text" inputmode="numeric" data-uang name="harga_satuan" value="0"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Pekerjaan</button></div>
    </div>
  </form>
</div>
<div class="panel">
  <h2>Ubah Nama / Keterangan Gedung</h2>
  <form method="post" action="<?= base_url('gedung/' . $g['id'] . '/perbarui') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" value="<?= esc($g['nama']) ?>" required></label>
      <label>Keterangan<input type="text" name="keterangan" value="<?= esc($g['keterangan'] ?? '') ?>"></label>
      <div class="aksi md:col-span-2"><button type="submit">Simpan</button></div>
    </div>
  </form>
</div>

<?php elseif ($tabAktif === 'bahan'): ?>
<div class="panel">
  <h2>Kebutuhan Bahan — Dihitung dari BOQ × Koefisien AHSP</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari…">
    <tr><th>Kode</th><th>Bahan</th><th class="angka">Kebutuhan</th><th>Satuan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dipesan*</th><th class="angka">Sisa</th></tr>
    <?php $totalNilai = 0; foreach ($kebutuhan['tertaut'] as $b): $nilai = (float) $b['qty_butuh'] * (float) $b['harga']; $totalNilai += $nilai; $sisa = (float) $b['qty_butuh'] - (float) $b['dipesan']; ?>
    <tr>
      <td><?= esc($b['kode']) ?></td>
      <td class="potong" title="<?= esc($b['nama']) ?>"><?= esc($b['nama']) ?><?= ! empty($b['dikonversi']) ? ' <span class="lencana kuning" title="Dikonversi dari satuan AHSP ke satuan beli">dikonversi</span>' : '' ?><?= ! empty($b['satuan_beda']) ? ' <span class="lencana abu" title="Satuan komponen AHSP berbeda dari satuan bahan master — periksa kewajaran jumlahnya">satuan beda</span>' : '' ?></td>
      <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?></td>
      <td><?= esc($b['satuan']) ?></td>
      <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="5">Total Nilai Kebutuhan Bahan Tertaut</td><td class="angka"><?= number_format($totalNilai, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table></div>
  <p class="kecil-teks">*Sudah dipesan = jumlah pada SPK yang tertaut ke gedung ini. Harga acuan mengikuti jenjang aplikasi (negosiasi → penawaran → RAB). Kebutuhan semen/agregat dikonversi dari kg AHSP: semen ÷50 per zak, pasir ÷1.400 per m³, kerikil/batu ÷1.600 per m³.</p>
</div>
<?php if (! empty($kebutuhan['tak_tertaut'])): ?>
<div class="panel">
  <h2>Komponen Belum Tertaut ke Master Bahan (<?= count($kebutuhan['tak_tertaut']) ?>)</h2>
  <p style="margin-top:0" class="kecil-teks">Komponen AHSP berikut belum punya padanan di master bahan sehingga belum terhitung. Tautkan ke bahan yang sesuai — penautan berlaku untuk semua gedung.</p>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari komponen…">
    <tr><th>Komponen AHSP</th><th class="angka">Kebutuhan</th><th style="width:46%">Tautkan ke Bahan Master</th></tr>
    <?php foreach ($kebutuhan['tak_tertaut'] as $t): ?>
    <tr>
      <td class="potong" title="<?= esc($t['nama']) ?>"><?= esc($t['nama']) ?></td>
      <td class="angka"><?= number_format((float) $t['qty_butuh'], 2, ',', '.') ?></td>
      <td>
        <form method="post" action="<?= base_url('gedung/tautkan') ?>" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="nama_komponen" value="<?= esc($t['nama']) ?>">
          <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
          <select name="bahan_id" required style="margin:0" data-select-cari data-placeholder="— pilih bahan —">
            <option value="">— pilih bahan —</option>
            <?php foreach ($semuaBahan as $sb): ?><option value="<?= (int) $sb['id'] ?>"><?= esc($sb['kode']) ?> — <?= esc($sb['nama']) ?></option><?php endforeach; ?>
          </select>
          <button class="kecil" type="submit">Tautkan</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>

<?php elseif ($tabAktif === 'upah'): ?>
<div class="panel">
  <h2>Kebutuhan Upah — Dihitung dari BOQ × Koefisien Tenaga</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari jenis tenaga…">
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Tarif / OH</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dibayar (OH)</th><th class="angka">Sudah Dibayar (Rp)</th></tr>
    <?php $totalU = 0; foreach ($kebutuhan_upah as $u): $nilai = (float) $u['orang_hari'] * (float) $u['tarif']; $totalU += $nilai; $byr = $upah_dibayar[$u['jenis']] ?? null; ?>
    <tr>
      <td><?= esc($u['jenis']) ?></td>
      <td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['oh'], 2, ',', '.') : '—' ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['jml'], 0, ',', '.') : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="3">Total Nilai Kebutuhan Upah</td><td class="angka"><?= number_format($totalU, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table></div>
  <p class="kecil-teks">OH = orang-hari. Pembayaran upah dicatat di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a>. Tarif mengikuti daftar upah dokumen perencana (berbasis UMK) dan dapat diubah di sana.</p>
</div>

<?php elseif ($tabAktif === 'realisasi'): ?>
<div class="panel">
  <h2>Kemajuan per Pekerjaan</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari…">
    <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th>% Fisik</th><th style="width:300px">Catat Kemajuan</th></tr>
    <?php foreach ($pekerjaan as $p): $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0; ?>
    <tr>
      <td class="potong" title="<?= esc($p['nama']) ?>"><?= esc($p['nama']) ?><?php if (! empty($p['bab'])): ?> <span class="kecil-teks">(<?= esc($p['bab']) ?>)</span><?php endif; ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume_terealisasi'], 3, ',', '.'), '0'), ',') ?></td>
      <td><div class="sel-fisik"><div class="bar<?= $persen >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= $persen ?>%"></div></div><span class="kecil-teks"><?= number_format($persen, 1, ',', '.') ?>%</span></div></td>
      <td>
        <form method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/realisasi') ?>" style="display:flex;gap:5px">
          <?= csrf_field() ?>
          <input type="text" name="volume" placeholder="Volume" required style="margin:0;width:84px">
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" style="margin:0;width:132px">
          <button class="kecil" type="submit">Catat</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<div class="panel">
  <h2>Riwayat Pencatatan Terakhir</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari riwayat…">
    <tr><th>Tanggal</th><th>Pekerjaan</th><th class="angka">Volume</th><th>Keterangan</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r): ?>
    <tr>
      <td><?= esc($r['tanggal']) ?></td>
      <td><?= esc($r['nama_pekerjaan']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $r['volume'], 3, ',', '.'), '0'), ',') ?></td>
      <td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('gedung/realisasi/' . $r['id'] . '/hapus') ?>" data-konfirmasi="Hapus catatan ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($riwayat === []): ?><tr><td colspan="5">Belum ada pencatatan kemajuan.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-buka-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var baris = document.querySelector('[data-detail="' + btn.getAttribute('data-buka-detail') + '"]');
    if (!baris) return;
    var buka = baris.classList.toggle('terbuka');
    baris.style.display = buka ? '' : 'none';
    var panah = btn.querySelector('.panah');
    if (panah) panah.innerHTML = buka ? '&#9662;' : '&#9656;';
  });
});
</script>

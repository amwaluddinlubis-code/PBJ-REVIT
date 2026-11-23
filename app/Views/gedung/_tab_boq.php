<?php /* Partial gedung/detail tab BOQ: panel daftar pekerjaan (bulk hapus, kosongkan, rincian analisa per baris) + skrip jangkar #pekerjaan-<id>. */ ?><div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-pekerjaan','ids-pekerjaan')">
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

<?php /* Partial gedung/detail tab BOQ: panel daftar pekerjaan (bulk hapus, kosongkan, rincian analisa per baris) + skrip jangkar #pekerjaan-<id>. Tabel dirender helper tabel_bantu(); baris judul bab (baris-bab), baris rincian (baris-detail), dan grand total (total) dipertahankan kelasnya untuk skrip lipat & paginasi.js. */ ?><div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-pekerjaan','ids-pekerjaan')">
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
  <div class="bungkus-tabel"><?php
  $fmtVol = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ',');
  $subtotalBab = [];
  foreach ($pekerjaan as $px) {
      $kb = (string) ($px['bab'] ?? '');
      $subtotalBab[$kb] = ($subtotalBab[$kb] ?? 0) + (float) ($px['volume_revisi'] ?? $px['volume']) * (float) ($px['harga_berjalan'] ?? $px['harga_satuan']);
  }
  $barisBoq = [];
  $babLalu = null;
  $total = 0;
  foreach ($pekerjaan as $p) {
      $total += (float) ($p['volume_revisi'] ?? $p['volume']) * (float) ($p['harga_berjalan'] ?? $p['harga_satuan']);
      if ($p['bab'] !== $babLalu) {
          $babLalu = $p['bab'];
          $barisBoq[] = [
              'class' => 'baris-bab',
              'atribut' => 'data-bab="' . esc($babLalu ?: '—', 'attr') . '"',
              'sel' => [
                  ['html' => '<button type="button" class="kecil abu tombol-lipat-bab" aria-expanded="true" title="Lipat / buka pekerjaan bab ini" style="margin-right:6px">&#9662;</button>' . esc($babLalu ?: '—'), 'colspan' => 7, 'gaya' => 'font-weight:600;background:#fcfcfd'],
                  ['teks' => number_format($subtotalBab[(string) ($babLalu ?? '')] ?? 0, 0, ',', '.'), 'angka' => true, 'gaya' => 'font-weight:700;background:#fcfcfd'],
                  ['teks' => '', 'gaya' => 'background:#fcfcfd'],
              ],
          ];
      }
      $vb = (float) ($p['volume_revisi'] ?? $p['volume']);
      $namaSel = (! empty($p['kode_analisa']) && isset($petaAnalisa[$p['kode_analisa']]))
          ? '<button type="button" class="tautan-pekerjaan" data-buka-detail="' . (int) $p['id'] . '" title="Klik untuk melihat rincian bahan &amp; upah"><span class="panah">&#9656;</span> ' . esc($p['nama']) . '</button>'
          : esc($p['nama']);
      if ($p['kode_analisa']) {
          $idA = $petaAnalisa[$p['kode_analisa']]['id'] ?? ($p['analisa_id'] ?? null);
          $kodeSel = $idA
              ? '<a class="lencana abu" style="text-decoration:none" href="' . base_url('analisa/' . $idA) . '" title="Buka halaman analisa ' . esc($p['kode_analisa'], 'attr') . '">' . esc($p['kode_analisa']) . '</a>'
              : '<span class="lencana abu">' . esc($p['kode_analisa']) . '</span>';
      } else {
          $kodeSel = '—';
      }
      $barisBoq[] = [
          'atribut' => 'id="pekerjaan-' . (int) $p['id'] . '"',
          'sel' => [
              ['html' => '<input type="checkbox" data-pilih value="' . (int) $p['id'] . '" x-model="terpilih">'],
              ['html' => $namaSel, 'class' => 'potong', 'title' => (string) $p['nama']],
              ['html' => $kodeSel],
              ['teks' => $fmtVol($p['volume']), 'angka' => true],
              ['html' => $fmtVol($vb)
                  . ((isset($p['volume_revisi']) && $p['volume_revisi'] !== null) ? ' <span class="lencana abu" title="Diubah oleh BA Revisi yang disahkan">Revisi</span>' : '')
                  . (! empty($p['dari_revisi_id']) ? ' <span class="lencana abu" title="Pekerjaan dari BA Revisi">Pengganti/Baru</span>' : ''), 'angka' => true],
              (string) ($p['satuan'] ?? ''),
              ['teks' => number_format((float) ($p['harga_berjalan'] ?? $p['harga_satuan']), 0, ',', '.'), 'angka' => true],
              ['teks' => number_format($vb * (float) ($p['harga_berjalan'] ?? $p['harga_satuan']), 0, ',', '.'), 'angka' => true],
              ['html' => '<form class="inline" method="post" action="' . base_url('gedung/pekerjaan/' . $p['id'] . '/volume') . '">'
                  . csrf_field()
                  . '<input type="text" name="volume" value="' . esc((string) $p['volume'], 'attr') . '" style="width:76px;display:inline-block;margin:0;padding:4px 7px"> '
                  . '<button class="kecil" type="submit">Simpan</button></form> '
                  . '<form class="inline" method="post" action="' . base_url('gedung/pekerjaan/' . $p['id'] . '/hapus') . '" data-konfirmasi="Hapus pekerjaan ini?">'
                  . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
          ],
      ];
      if (! empty($p['kode_analisa']) && isset($petaAnalisa[$p['kode_analisa']])) {
          $a = $petaAnalisa[$p['kode_analisa']];
          $vol = (float) ($p['volume_revisi'] ?? $p['volume']);
          ob_start();
          ?>
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
          <?php
          $detailHtml = ob_get_clean();
          $barisBoq[] = [
              'class' => 'baris-detail',
              'atribut' => 'data-detail="' . (int) $p['id'] . '"',
              'sel' => [['html' => $detailHtml, 'colspan' => 9]],
          ];
      }
  }
  $barisBoq[] = ['total' => [
      ['teks' => 'Grand Total BOQ Berjalan', 'colspan' => 7],
      ['teks' => number_format($total, 0, ',', '.'), 'angka' => true],
      '',
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari pekerjaan…',
      'paginasi' => 25,
      'class' => 'satu-baris',
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua pekerjaan" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Pekerjaan'],
          ['judul' => 'Kode'],
          ['judul' => 'Volume Awal', 'angka' => true],
          ['judul' => 'Vol. Berjalan', 'angka' => true],
          ['judul' => 'Satuan'],
          ['judul' => 'Harga Satuan', 'angka' => true],
          ['judul' => 'Jumlah Berjalan', 'angka' => true],
          ['judul' => 'Ubah Volume / Hapus', 'lebar' => '210px'],
      ],
      'baris' => $barisBoq,
  ]);
  ?></div>
  <p class="kecil-teks">Mengubah volume pekerjaan menghitung ulang kebutuhan bahan &amp; upah gedung ini secara otomatis. Tombol panah di awal judul bab melipat/membuka baris pekerjaan bab itu (subtotal bab tetap terlihat); keadaan lipatan diingat untuk gedung ini.</p>
  <style>
    tr.unit-terlipat { display: none !important; }
    @media print { tr.unit-terlipat { display: table-row !important; } }
  </style>
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
<script>
// Lipat bab BOQ: tombol pada baris judul bab menyembunyikan baris
// pekerjaan + baris rinciannya sampai judul bab berikutnya. Penyembunyian
// memakai kelas (bukan style sebaris) agar tidak ditimpa mesin Tabel
// Standar (paginasi.js) yang mengatur display baris untuk paginasi &
// pencarian; subtotal bab tetap terlihat karena baris judul tidak pernah
// disembunyikan oleh skrip ini. Keadaan terlipat per bab disimpan di
// localStorage per gedung.
(function () {
  var kunci = 'pbj-boq-lipat-<?= (int) $g['id'] ?>';
  var terlipat = {};
  try {
    JSON.parse(localStorage.getItem(kunci) || '[]').forEach(function (b) { terlipat[b] = true; });
  } catch (e) { /* abaikan */ }
  function simpan() {
    try { localStorage.setItem(kunci, JSON.stringify(Object.keys(terlipat))); } catch (e) { /* abaikan */ }
  }
  function terapkan(kepala, lipat) {
    var tombol = kepala.querySelector('.tombol-lipat-bab');
    if (tombol) {
      tombol.setAttribute('aria-expanded', lipat ? 'false' : 'true');
      tombol.innerHTML = lipat ? '&#9656;' : '&#9662;';
    }
    var tr = kepala.nextElementSibling;
    while (tr && ! tr.classList.contains('baris-bab') && ! tr.classList.contains('total')) {
      if ((tr.id && tr.id.indexOf('pekerjaan-') === 0) || tr.classList.contains('baris-detail')) {
        tr.classList.toggle('unit-terlipat', lipat);
      }
      tr = tr.nextElementSibling;
    }
  }
  document.querySelectorAll('tr.baris-bab').forEach(function (kepala) {
    var nama = kepala.getAttribute('data-bab') || '';
    if (terlipat[nama]) terapkan(kepala, true);
    var tombol = kepala.querySelector('.tombol-lipat-bab');
    if (tombol) {
      tombol.addEventListener('click', function () {
        var lipat = ! terlipat[nama];
        if (lipat) { terlipat[nama] = true; } else { delete terlipat[nama]; }
        terapkan(kepala, lipat);
        simpan();
      });
    }
  });
})();
</script>

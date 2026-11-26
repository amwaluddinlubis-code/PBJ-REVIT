<?php $namaBulan = static fn ($ym) => bulanTahun($ym); ?>
<details class="panel panel-lipat" data-lipat-id="bukukas-panel" open>
  <summary><?= esc($nama_buku) ?> — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Pembukuan menurut Juknis Revitalisasi SMP 2026. Isi buku tergabung otomatis dari pembayaran invoice (bank), pembayaran upah (tunai), bukti setor ke kas negara, dan transaksi manual di bawah. Buku ditutup setiap akhir bulan.</p>
  <div class="aksi" style="justify-content:flex-start;gap:8px;flex-wrap:wrap">
    <?php foreach (['bku' => 'Buku Kas Umum', 'bank' => 'Buku Bank', 'tunai' => 'Buku Pembantu Kas Tunai'] as $k => $label): ?>
      <a class="tombol <?= $buku === $k ? '' : 'abu' ?>" href="<?= base_url('buku-kas?buku=' . $k . '&bulan=' . $bulan) ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <form method="get" action="<?= base_url('buku-kas') ?>" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="buku" value="<?= esc($buku) ?>">
      <input type="month" name="bulan" value="<?= esc($bulan) ?>">
      <button type="submit" class="kecil">Tampilkan</button>
    </form>
    <a class="tombol abu" href="<?= base_url('buku-kas/cetak?buku=' . $buku . '&bulan=' . $bulan) ?>" target="_blank">Cetak Bulan Ini</a>
  </div>
</details>

<details class="panel panel-lipat" data-lipat-id="bukukas-bulan">
  <summary><?= esc($nama_buku) ?> — Bulan <?= esc($namaBulan($bulan)) ?></summary>

  <?php
  // Tabel buku bulan ini: saldo awal, mutasi dengan saldo berjalan, saldo akhir.
  $labelSumber = ['manual' => 'manual', 'invoice' => 'jurnal pembayaran', 'upah' => 'upah'];
  $barisBuku = [
      ['sel' => [
          ['html' => '<b>Saldo awal bulan</b>', 'colspan' => 3],
          '',
          '',
          ['html' => '<b>' . rp($saldo_awal) . '</b>', 'angka' => true],
      ]],
  ];
  foreach ($baris as $e) {
      $barisBuku[] = ['sel' => [
          (string) ($e['nomor'] ?? ''),
          (string) tgl($e['tanggal'] ?? null),
          ['html' => esc($e['uraian']) . ' <span class="kecil-teks">(' . ($labelSumber[$e['sumber']] ?? 'bukti setor') . ')</span>'],
          ['teks' => $e['masuk'] > 0 ? rp($e['masuk']) : '—', 'angka' => true],
          ['teks' => $e['keluar'] > 0 ? rp($e['keluar']) : '—', 'angka' => true],
          ['teks' => rp($e['saldo']), 'angka' => true],
      ]];
  }
  if ($baris === []) {
      $barisBuku[] = ['sel' => [['teks' => 'Tidak ada transaksi pada bulan ini.', 'colspan' => 6]]];
  }
  $barisBuku[] = ['sel' => [
      ['html' => '<b>Saldo akhir bulan (penutupan buku)</b>', 'colspan' => 3],
      '',
      '',
      ['html' => '<b>' . rp($saldo_akhir) . '</b>', 'angka' => true],
  ]];
  echo tabel_bantu([
      'cari' => 'Cari transaksi…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'No. Bukti'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Uraian'],
          ['judul' => 'Penerimaan', 'angka' => true],
          ['judul' => 'Pengeluaran', 'angka' => true],
          ['judul' => 'Saldo', 'angka' => true],
      ],
      'baris' => $barisBuku,
  ]);
  ?>
</details>

<details class="panel panel-lipat" data-lipat-id="bukukas-catat-transaksi-manual" data-bulk-akar x-data="komponenBulk('form-bulk-kas','ids-kas')">
  <summary>Catat Transaksi Kas Manual</summary>

  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <button type="button" class="tombol" onclick="bukaKasBaru()">+ Catat Transaksi</button>
    <?php if ($manual !== []): ?>
    <form id="form-bulk-kas" method="post" action="<?= base_url('buku-kas/hapus-massal') ?>" data-konfirmasi-judul="Hapus Transaksi Kas Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-kas"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} transaksi kas terpilih dari buku?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('buku-kas/kosongkan') ?>" data-konfirmasi="Kosongkan transaksi kas manual sekolah ini? Semua <?= count($manual) ?> transaksi kas yang dicatat manual (termasuk penerimaan dana) akan dihapus (hapus lunak). Baris buku dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus." data-konfirmasi-judul="Kosongkan Transaksi Kas Manual" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Transaksi Manual</button>
    </form>
    <?php endif; ?>
  </div>
  <p class="kecil-teks">Pembayaran invoice, pembayaran upah, penerimaan dana, dan penyetoran pajak/sisa dana tidak dicatat di sini — sudah masuk buku secara otomatis dari modulnya masing-masing: <a href="<?= base_url('penerimaan-dana') ?>">Penerimaan Dana</a> · <a href="<?= base_url('bukti-setor') ?>">Bukti Setor &amp; Pajak</a> · <a href="<?= base_url('invoice/jurnal') ?>">Jurnal Pembayaran</a> · <a href="<?= base_url('upah') ?>">Pembayaran Upah</a>. Panel ini hanya untuk transaksi kas lain-lain di luar modul tersebut.</p>

  <?php if ($manual !== []): ?>
  <?php
  $barisManual = [];
  foreach ($manual as $m) {
      $barisManual[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $m['id'] . '" x-model="terpilih">'],
          (string) tgl($m['tanggal']),
          (string) $m['uraian'],
          (string) $m['kategori'] . ($m['tahap'] ? ' · tahap ' . (int) $m['tahap'] : ''),
          (string) $m['kanal'] . ' · ' . (string) $m['jenis'],
          ['teks' => rp($m['jumlah']), 'angka' => true],
          ['html' => '<form class="inline" method="post" action="' . base_url('buku-kas/hapus/' . $m['id']) . '" data-konfirmasi="Hapus transaksi kas ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari transaksi…',
      'paginasi' => 10,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua transaksi" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Uraian'],
          ['judul' => 'Kategori'],
          ['judul' => 'Kanal'],
          ['judul' => 'Jumlah', 'angka' => true],
          ['judul' => ''],
      ],
      'baris' => $barisManual,
  ]);
  ?>
  <?php endif; ?>
</details>

<details class="panel panel-lipat" data-lipat-id="bukukas-tutup-buku" open>
  <summary>Tutup Buku Bulanan</summary>

  <?php if ($buku_terbuka !== null): ?>
  <p style="margin-top:0">Buku terbuka: <strong><?= esc($buku_terbuka['label']) ?></strong> — semua pencatatan uang masuk bulan ini. Sesudah ditutup, pencatatan berikutnya langsung masuk bulan berikutnya dan bulan ini tidak bisa diubah lagi.</p>
  <div class="kartu-kpi ringkas">
    <div class="kpi"><div class="label">Saldo Awal</div><div class="nilai"><?= rp($ringkas_terbuka['saldo_awal']) ?></div></div>
    <div class="kpi"><div class="label">Total Masuk</div><div class="nilai"><?= rp($ringkas_terbuka['total_masuk']) ?></div></div>
    <div class="kpi"><div class="label">Total Keluar</div><div class="nilai"><?= rp($ringkas_terbuka['total_keluar']) ?></div></div>
    <div class="kpi"><div class="label">Saldo Akhir</div><div class="nilai"><?= rp($ringkas_terbuka['saldo_akhir']) ?></div></div>
    <div class="kpi"><div class="label">Baris BKU</div><div class="nilai"><?= (int) $ringkas_terbuka['jumlah_baris'] ?></div></div>
  </div>
  <?php if ($belum_bayar_bulan > 0): ?>
  <p class="kecil-teks">Perhatian: masih ada <?= (int) $belum_bayar_bulan ?> invoice belum dibayar bertanggal bulan ini. Periksa juga kewajiban pajak masa ini di menu Bukti Setor &amp; Pajak sebelum menutup buku.</p>
  <?php else: ?>
  <p class="kecil-teks">Periksa kewajiban pajak masa ini di menu Bukti Setor &amp; Pajak sebelum menutup buku.</p>
  <?php endif; ?>
  <?php if (! empty($spj_belum_lengkap)): ?>
  <p class="kecil-teks">Perhatian: SPJ bulan <?= esc(implode(', ', array_map(static fn ($ym) => bulanTahun($ym), $spj_belum_lengkap))) ?> belum lengkap (belum ada snapshot bukti) — lihat menu <a href="<?= base_url('spj-bulanan') ?>">SPJ Bulanan</a>.</p>
  <?php endif; ?>
  <form method="post" action="<?= base_url('buku-kas/tutup') ?>" data-konfirmasi="Tutup buku <?= esc($buku_terbuka['label']) ?> dengan saldo akhir <?= rp($ringkas_terbuka['saldo_akhir']) ?>? Sesudah ditutup, transaksi bulan ini tidak bisa dicatat/diubah/dihapus lagi dan pencatatan berikutnya masuk bulan berikutnya." data-konfirmasi-judul="Tutup Buku <?= esc($buku_terbuka['label']) ?>" data-konfirmasi-tombol="Ya, Tutup Buku">
    <?= csrf_field() ?>
    <p class="kecil-teks" style="margin:8px 0 6px"><strong>Daftar periksa rekonsiliasi (wajib diisi, 0 bila tidak ada):</strong> bunga &amp; biaya bank yang dinyatakan dicatat otomatis sebagai transaksi kas bila belum tercatat.</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
      <label class="kecil-teks">Saldo rekening koran bank (Rp)<br><input type="text" name="saldo_koran" inputmode="numeric" required placeholder="0" style="max-width:190px"></label>
      <label class="kecil-teks">Kas fisik hasil opname (Rp)<br><input type="text" name="kas_fisik" inputmode="numeric" required placeholder="0" style="max-width:190px"></label>
      <label class="kecil-teks">Bunga bank bulan ini (Rp)<br><input type="text" name="bunga_bank" inputmode="numeric" required placeholder="0" style="max-width:170px"></label>
      <label class="kecil-teks">Biaya bank bulan ini (Rp)<br><input type="text" name="biaya_bank" inputmode="numeric" required placeholder="0" style="max-width:170px"></label>
      <label class="kecil-teks">Penjelasan selisih (bila ada)<br><input type="text" name="penjelasan_rekon" placeholder="opsional" style="max-width:240px"></label>
      <button type="submit">Tutup Buku <?= esc($buku_terbuka['label']) ?></button>
    </div>
  </form>
  <?php else: ?>
  <p style="margin-top:0">Seluruh bulan tahun anggaran ini sudah ditutup. Pencatatan uang tidak diterima lagi; buka kembali buku (admin) bila memang perlu koreksi.</p>
  <?php endif; ?>

  <?php if ($daftar_tutup !== []): ?>
  <h3 style="margin:16px 0 8px">Bulan yang Sudah Ditutup</h3>
  <?php
  $barisTutup = [];
  foreach ($daftar_tutup as $b => $t) {
      $aksi = '<span class="kecil-teks">—</span>';
      if ($saya_admin) {
          $aksi = '<form class="inline" method="post" action="' . base_url('buku-kas/buka-kembali/' . $t['id']) . '" data-konfirmasi="Buka kembali buku bulan ini? Bulan ini dan bulan-bulan sesudahnya kembali terbuka dan harus ditutup ulang berurutan." data-konfirmasi-judul="Buka Kembali Buku" data-konfirmasi-tombol="Ya, Buka Kembali">'
              . csrf_field()
              . '<input type="text" name="alasan" placeholder="Alasan (wajib)" required style="max-width:190px"> '
              . '<button class="kecil" type="submit">Buka Kembali</button></form>';
      }
      $rekon = '—';
      if (($t['saldo_koran'] ?? null) !== null) {
          $rekon = 'koran ' . rp($t['saldo_koran']) . ' · selisih bank ' . rp($t['selisih_bank'] ?? 0)
              . ' · kas fisik ' . rp($t['kas_fisik'] ?? 0) . ' · selisih tunai ' . rp($t['selisih_tunai'] ?? 0)
              . ((float) ($t['bunga_bank'] ?? 0) > 0 ? ' · bunga ' . rp($t['bunga_bank']) : '')
              . ((float) ($t['biaya_bank'] ?? 0) > 0 ? ' · biaya ' . rp($t['biaya_bank']) : '')
              . (! empty($t['penjelasan_rekon']) ? ' — ' . $t['penjelasan_rekon'] : '');
      }
      $barisTutup[] = ['sel' => [
          $namaBulan(sprintf('%04d-%02d', (int) $t['tahun'], (int) $b)),
          ['teks' => rp($t['saldo_awal']), 'angka' => true],
          ['teks' => rp($t['total_masuk']), 'angka' => true],
          ['teks' => rp($t['total_keluar']), 'angka' => true],
          ['teks' => rp($t['saldo_akhir']), 'angka' => true],
          $rekon,
          (string) ($t['ditutup_oleh'] ?? '—') . ' · ' . (string) tglWaktu($t['ditutup_pada'] ?? null),
          ['html' => $aksi],
      ]];
  }
  echo tabel_bantu([
      'cari' => false,
      'paginasi' => false,
      'kolom' => [
          ['judul' => 'Bulan'],
          ['judul' => 'Saldo Awal', 'angka' => true],
          ['judul' => 'Masuk', 'angka' => true],
          ['judul' => 'Keluar', 'angka' => true],
          ['judul' => 'Saldo Akhir', 'angka' => true],
          ['judul' => 'Rekonsiliasi'],
          ['judul' => 'Ditutup Oleh'],
          ['judul' => ''],
      ],
      'baris' => $barisTutup,
  ]);
  ?>
  <?php endif; ?>
</details>

<?= form_modal([
    'id' => 'modal-kas',
    'judul' => 'Catat Transaksi Kas Manual',
    'aksi' => base_url('buku-kas/simpan'),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal', 'wajib' => true, 'min' => $buku_min ?? '', 'max' => $buku_max ?? '', 'petunjuk' => 'Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'uraian', 'label' => 'Uraian', 'jenis' => 'teks', 'wajib' => true, 'penuh' => true, 'placeholder' => 'cth. Penerimaan dana bantuan tahap 1'],
        ['nama' => 'kategori', 'label' => 'Kategori', 'jenis' => 'pilihan', 'opsi' => [
            'lainnya' => 'Lainnya',
            'koreksi' => 'Koreksi bulan tertutup (jurnal balik)',
        ], 'petunjuk' => 'Penerimaan dana dicatat di modul Penerimaan Dana; pajak & sisa dana di Bukti Setor & Pajak. Koreksi hanya untuk membalik transaksi bulan yang sudah ditutup.'],
        ['nama' => 'koreksi_ref', 'label' => 'Transaksi Asal (bulan tertutup)', 'jenis' => 'pilihan', 'opsi' => $pilihan_koreksi ?? [], 'penuh' => true, 'petunjuk' => 'Wajib untuk kategori Koreksi — jurnal balik tercatat sebagai lawan transaksi asal di bulan terbuka.'],
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan', 'opsi' => ['masuk' => 'Uang masuk', 'keluar' => 'Uang keluar']],
        ['nama' => 'kanal', 'label' => 'Kanal', 'jenis' => 'pilihan', 'opsi' => ['bank' => 'Bank', 'tunai' => 'Tunai']],
        ['nama' => 'jumlah', 'label' => 'Jumlah (Rp)', 'jenis' => 'uang', 'wajib' => true, 'placeholder' => '750000000'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks', 'placeholder' => 'opsional'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Catat Transaksi'],
]) ?>
<script>
// Jembatan modal kas: tanggal bawaan mengikuti Bulan Terbuka, lalu
// sinkronkan widget select-cari sesudah modal dibuka.
function bukaKasBaru() {
  window.bukaFormModal('modal-kas', { tanggal: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
  var m = document.getElementById('modal-kas');
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
  segarkanKoreksiKas();
}
// Kategori Koreksi: tampilkan pemilih transaksi asal; memilih asal
// mengisi jumlah & arah lawan (server tetap menentukan arah akhir).
function segarkanKoreksiKas() {
  var m = document.getElementById('modal-kas');
  if (!m) return;
  var kat = m.querySelector('select[name="kategori"]');
  var ref = m.querySelector('select[name="koreksi_ref"]');
  if (!kat || !ref) return;
  var bungkus = ref.closest('.fm-field');
  if (bungkus) bungkus.style.display = kat.value === 'koreksi' ? '' : 'none';
  ref.disabled = kat.value !== 'koreksi';
}
document.addEventListener('change', function (ev) {
  var m = document.getElementById('modal-kas');
  if (!m || !m.contains(ev.target)) return;
  if (ev.target.name === 'kategori') { segarkanKoreksiKas(); return; }
  if (ev.target.name === 'koreksi_ref') {
    var opt = ev.target.options[ev.target.selectedIndex];
    if (!opt) return;
    var jml = m.querySelector('input[name="jumlah"]');
    if (jml && opt.dataset.jumlah) { jml.value = opt.dataset.jumlah; jml.dispatchEvent(new Event('input', { bubbles: true })); }
    var jenis = m.querySelector('select[name="jenis"]');
    if (jenis && opt.dataset.jenisAsal) { jenis.value = opt.dataset.jenisAsal === 'masuk' ? 'keluar' : 'masuk'; jenis.dispatchEvent(new Event('change', { bubbles: true })); }
  }
});
</script>

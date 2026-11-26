<?php $namaBulan = static fn ($ym) => date('F Y', strtotime($ym . '-01')); ?>
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
          ['html' => '<b>Saldo awal bulan</b>', 'colspan' => 2],
          '',
          '',
          ['html' => '<b>' . rp($saldo_awal) . '</b>', 'angka' => true],
      ]],
  ];
  foreach ($baris as $e) {
      $barisBuku[] = ['sel' => [
          (string) tgl($e['tanggal'] ?? null),
          ['html' => esc($e['uraian']) . ' <span class="kecil-teks">(' . ($labelSumber[$e['sumber']] ?? 'bukti setor') . ')</span>'],
          ['teks' => $e['masuk'] > 0 ? rp($e['masuk']) : '—', 'angka' => true],
          ['teks' => $e['keluar'] > 0 ? rp($e['keluar']) : '—', 'angka' => true],
          ['teks' => rp($e['saldo']), 'angka' => true],
      ]];
  }
  if ($baris === []) {
      $barisBuku[] = ['sel' => [['teks' => 'Tidak ada transaksi pada bulan ini.', 'colspan' => 5]]];
  }
  $barisBuku[] = ['sel' => [
      ['html' => '<b>Saldo akhir bulan (penutupan buku)</b>', 'colspan' => 2],
      '',
      '',
      ['html' => '<b>' . rp($saldo_akhir) . '</b>', 'angka' => true],
  ]];
  echo tabel_bantu([
      'cari' => 'Cari transaksi…',
      'paginasi' => 25,
      'kolom' => [
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

<details class="panel panel-lipat" data-lipat-id="bukukas-catat-transaksi-kas-manual">
  <summary>Catat Transaksi Kas Manual</summary>

  <button type="button" class="tombol" onclick="bukaKasBaru()">+ Catat Transaksi</button>
  <p class="kecil-teks">Pembayaran invoice, pembayaran upah, dan penyetoran pajak/sisa dana tidak perlu dicatat di sini — sudah masuk buku secara otomatis dari modulnya masing-masing.</p>
</details>

<?php if ($manual !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-kas','ids-kas')">
  <h2>Transaksi Manual Tercatat</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-kas" method="post" action="<?= base_url('buku-kas/hapus-massal') ?>" data-konfirmasi-judul="Hapus Transaksi Kas Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-kas"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} transaksi kas terpilih dari buku?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('buku-kas/kosongkan') ?>" data-konfirmasi="Kosongkan transaksi kas manual sekolah ini? Semua <?= count($manual) ?> transaksi kas yang dicatat manual (termasuk penerimaan dana) akan dihapus (hapus lunak). Baris buku dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus." data-konfirmasi-judul="Kosongkan Transaksi Kas Manual" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Transaksi Manual</button>
    </form>
  </div>
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
</div>
<?php endif; ?>

<?= form_modal([
    'id' => 'modal-kas',
    'judul' => 'Catat Transaksi Kas Manual',
    'aksi' => base_url('buku-kas/simpan'),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal', 'wajib' => true],
        ['nama' => 'uraian', 'label' => 'Uraian', 'jenis' => 'teks', 'wajib' => true, 'penuh' => true, 'placeholder' => 'cth. Penerimaan dana bantuan tahap 1'],
        ['nama' => 'kategori', 'label' => 'Kategori', 'jenis' => 'pilihan', 'opsi' => [
            'penerimaan' => 'Penerimaan dana bantuan',
            'lainnya' => 'Lainnya',
            'pajak' => 'Pajak (di luar bukti setor)',
            'sisa_dana' => 'Sisa dana',
        ]],
        ['nama' => 'tahap', 'label' => 'Tahap (untuk penerimaan)', 'jenis' => 'pilihan', 'opsi' => ['' => '—', '1' => 'Tahap 1 (70%)', '2' => 'Tahap 2 (30%)']],
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan', 'opsi' => ['masuk' => 'Uang masuk', 'keluar' => 'Uang keluar']],
        ['nama' => 'kanal', 'label' => 'Kanal', 'jenis' => 'pilihan', 'opsi' => ['bank' => 'Bank', 'tunai' => 'Tunai']],
        ['nama' => 'jumlah', 'label' => 'Jumlah (Rp)', 'jenis' => 'uang', 'wajib' => true, 'placeholder' => '750000000'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks', 'placeholder' => 'opsional'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Catat Transaksi'],
]) ?>
<script>
// Jembatan modal kas: tanggal bawaan mengikuti Bulan Bekerja, lalu
// sinkronkan widget select-cari sesudah modal dibuka.
function bukaKasBaru() {
  window.bukaFormModal('modal-kas', { tanggal: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
  var m = document.getElementById('modal-kas');
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
</script>

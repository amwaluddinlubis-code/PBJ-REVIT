<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-gedung','ids-gedung')">
  <h2>Daftar Gedung — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-gedung" method="post" action="<?= base_url('gedung/hapus-massal') ?>" data-konfirmasi-judul="Hapus Gedung Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-gedung"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} gedung terpilih beserta seluruh BOQ dan realisasinya?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('gedung/kosongkan') ?>" data-konfirmasi="Kosongkan daftar gedung sekolah ini? Semua <?= count($gedung) ?> gedung aktif beserta BOQ dan realisasinya akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Daftar Gedung" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Daftar Gedung</button>
    </form>
  </div>
  <?php
  $barisGedung = [];
  foreach ($gedung as $g) {
      $perubahan = abs($g['perubahan']) < 0.5
          ? '<span class="kecil-teks">—</span>'
          : '<span style="color:' . ($g['perubahan'] > 0 ? 'var(--ina-red-700,#a61e1e)' : 'var(--ina-green-700,#1e6b2a)') . '">'
              . ($g['perubahan'] > 0 ? '+' : '−') . 'Rp ' . number_format(abs((float) $g['perubahan']), 0, ',', '.') . '</span>'
              . ($g['boq_asli'] > 0 ? ' <span class="kecil-teks">(' . number_format(abs($g['perubahan']) / $g['boq_asli'] * 100, 1, ',', '.') . '%)</span>' : '');
      $barisGedung[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $g['id'] . '" x-model="terpilih">'],
          ['html' => '<b>' . esc($g['nama']) . '</b>'
              . ((int) ($g['didanai'] ?? 1) !== 1 ? ' <span style="background:var(--ina-red-100);color:var(--ina-red-700);padding:1px 7px;border-radius:99px;font-size:11px">Tidak didanai</span>' : '')
              . ($g['keterangan'] ? '<br><span class="kecil-teks">' . esc($g['keterangan']) . '</span>' : '')],
          ['teks' => (string) (int) $g['jml_pekerjaan'], 'angka' => true],
          ['teks' => rp($g['boq_asli']), 'angka' => true],
          ['teks' => rp($g['nilai_analisa']), 'angka' => true],
          ['html' => $perubahan, 'angka' => true],
          ['teks' => rp($g['nilai_realisasi']), 'angka' => true],
          ['html' => '<div class="bar' . ($g['persen_fisik'] >= 99.5 ? ' hijau' : '') . '"><div style="width:' . min(100, $g['persen_fisik']) . '%"></div></div><span class="kecil-teks">' . number_format($g['persen_fisik'], 1, ',', '.') . '%</span>'],
          (string) ($g['sumber_sheet'] ?? '—'),
          ['html' => '<a class="tombol kecil" href="' . base_url('gedung/' . $g['id']) . '">Buka</a> '
              . '<form class="inline" method="post" action="' . base_url('gedung/' . $g['id'] . '/didanai') . '">'
              . csrf_field() . '<input type="hidden" name="didanai" value="' . ((int) ($g['didanai'] ?? 1) === 1 ? 0 : 1) . '"><button class="kecil abu" type="submit">' . ((int) ($g['didanai'] ?? 1) === 1 ? 'Tandai Tidak Didanai' : 'Tandai Didanai') . '</button></form> '
              . '<form class="inline" method="post" action="' . base_url('gedung/' . $g['id'] . '/hapus') . '" data-konfirmasi="Hapus gedung beserta seluruh BOQ dan realisasinya?">'
              . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  if ($gedung === []) {
      // colspan 8 (< 10 kolom) disengaja: paginasi.js mengenali sel tunggal
      // sepenuh lebar sebagai baris grup dan menyembunyikannya bila kosong.
      $barisGedung[] = ['sel' => [['teks' => 'Belum ada gedung. Impor dokumen perencana atau tambah manual di bawah.', 'colspan' => 8]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari gedung…',
      'paginasi' => 25,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua gedung" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Gedung'],
          ['judul' => 'Pekerjaan', 'angka' => true],
          ['judul' => 'Nilai BOQ Asli', 'angka' => true],
          ['judul' => 'Nilai Analisa', 'angka' => true],
          ['judul' => 'Perubahan', 'angka' => true],
          ['judul' => 'Realisasi', 'angka' => true],
          ['judul' => 'Fisik'],
          ['judul' => 'Sumber Sheet'],
          ['judul' => 'Aksi'],
      ],
      'baris' => $barisGedung,
  ]);
  ?>
</div>
<div class="panel">
  <h2>Tambah Gedung Manual</h2>
  <button type="button" class="tombol" onclick="bukaFormModal('modal-gedung')">+ Tambah Gedung</button>
</div>
<?= form_modal([
    'id' => 'modal-gedung',
    'judul' => 'Tambah Gedung Manual',
    'aksi' => base_url('gedung/simpan'),
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama gedung', 'wajib' => true, 'placeholder' => 'mis. RKB 2 Kopel'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'placeholder' => 'mis. Ruang Kelas 2 Kopel (18 m × 7 m)'],
        ['nama' => 'nilai_boq', 'label' => 'Nilai BOQ dokumen (Rp)', 'jenis' => 'uang', 'placeholder' => '0'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Tambah Gedung'],
]) ?>

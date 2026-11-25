<div class="panel">
  <h2 style="margin-top:0">Analisa Pekerjaan (AHSP)</h2>
  <p class="kecil-teks" style="margin-top:0">Analisa menentukan kebutuhan bahan &amp; upah dari volume BOQ. Klik <strong>Buka</strong> untuk mengubah koefisien atau tarif komponen tanpa impor ulang — harga satuan analisa dihitung ulang dari komponennya dan kebutuhan di semua gedung langsung mengikuti.</p>
  <?php if (($belum ?? 0) > 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px"><strong><?= (int) $belum ?> analisa</strong> harga satuannya <strong>belum terverifikasi</strong> — tidak terbaca dari sheet Harsat dokumen sumber (di dokumennya sendiri tertulis #REF!). Harga BOQ tetap yang dipakai untuk nilai pekerjaan; penanda ini hanya agar Bapak tahu analisa mana yang harga satuannya belum bisa dicek silang.</p>
  <?php endif; ?>
  <?php if (! empty($komponen_nol)): ?>
  <details style="margin-bottom:12px">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Komponen Bertarif 0 — <?= count($komponen_nol) ?> komponen belum punya tarif pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk rincian)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><?php
    $barisNol = [];
    foreach ($komponen_nol as $k) {
        $barisNol[] = ['sel' => [
            ['html' => '<strong>' . esc($k['kode']) . '</strong> <span class="kecil-teks">' . esc($k['analisa_nama']) . '</span>'],
            ['teks' => ucfirst($k['jenis'])],
            ['teks' => $k['komponen'], 'class' => 'potong', 'title' => $k['komponen']],
            ['teks' => rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ','), 'angka' => true],
            ['teks' => $k['satuan'] ?? ''],
            ['html' => '<a class="tombol kecil" href="' . base_url('analisa/' . $k['analisa_id']) . '#komponen-' . (int) $k['komponen_id'] . '">Isi Tarif</a>'],
        ]];
    }
    echo tabel_bantu([
        'kolom' => [['judul' => 'Analisa'], ['judul' => 'Jenis'], ['judul' => 'Komponen'], ['judul' => 'Koefisien', 'angka' => true], ['judul' => 'Satuan'], ['judul' => 'Aksi']],
        'baris' => $barisNol, 'cari' => 'Cari komponen…', 'paginasi' => 25, 'class' => 'satu-baris',
    ]);
    ?></div>
    <p class="kecil-teks">Komponen bertarif 0 membuat nilai kebutuhan analisa itu kurang dari seharusnya. Isi tarifnya lewat tombol Isi Tarif, atau dari Master Bahan/Master Upah bila komponennya tertaut master.</p>
  </details>
  <?php endif; ?>
  <?php if (! empty($beda_satuan)): ?>
  <details style="margin-bottom:12px" id="panel-satuan">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Satuan Bahan Berbeda — <?= count($beda_satuan) ?> pasangan satuan komponen ≠ satuan master pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk memperbaiki)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><?php
    $barisBeda = [];
    foreach ($beda_satuan as $r) {
        $fmtFaktor = static fn ($n) => rtrim(rtrim(number_format((float) $n, 8, ',', '.'), '0'), ',');
        $aksi = '<button type="button" class="tombol kecil" data-atur-konversi data-bahan="' . (int) $r['bahan_id'] . '" data-nama="' . esc($r['kode'] . ' — ' . $r['nama'], 'attr') . '" data-satuan-komponen="' . esc($r['satuan_komponen'], 'attr') . '" data-satuan-master="' . esc($r['satuan_master'], 'attr') . '" data-faktor="' . esc($fmtFaktor($r['faktor_manual'] ?? $r['faktor_berlaku'])) . '">Atur Faktor</button>';
        if ($r['faktor_manual'] !== null) {
            $aksi .= ' <form class="inline" method="post" action="' . base_url('analisa/hapus-konversi') . '" data-konfirmasi="Hapus konversi manual untuk ' . esc($r['nama'], 'attr') . ' (' . esc($r['satuan_komponen'], 'attr') . ')? Pasangan ini kembali memakai aturan bawaan." style="display:inline">'
                . csrf_field()
                . '<input type="hidden" name="bahan_id" value="' . (int) $r['bahan_id'] . '">'
                . '<input type="hidden" name="satuan_komponen" value="' . esc($r['satuan_komponen'], 'attr') . '">'
                . '<button type="submit" class="tombol kecil bahaya">Hapus Manual</button></form>';
        }
        $barisBeda[] = ['sel' => [
            ['html' => '<strong>' . esc($r['kode']) . '</strong> ' . esc($r['nama']), 'class' => 'potong', 'title' => $r['nama']],
            ['html' => '<a href="' . base_url('bahan/ubah/' . (int) $r['bahan_id']) . '" title="Klik untuk memperbaiki satuan bahan ini di Master Bahan">' . esc($r['satuan_master']) . '</a>'],
            ['html' => ! empty($r['contoh_aid']) ? '<a href="' . base_url('analisa/' . (int) $r['contoh_aid']) . '#komponen-' . (int) $r['contoh_kid'] . '" title="Klik untuk memperbaiki satuan komponen ini di editor analisa">' . esc($r['satuan_komponen']) . '</a>' : esc($r['satuan_komponen'])],
            ['teks' => (string) (int) $r['jml'], 'angka' => true],
            ['html' => $fmtFaktor($r['faktor_berlaku']) . ($r['faktor_manual'] !== null ? ' <span style="background:#dcfce7;color:#166534;padding:1px 7px;border-radius:99px;font-size:11px">manual</span>' : ''), 'angka' => true],
            ['html' => $aksi],
        ]];
    }
    echo tabel_bantu([
        'kolom' => [['judul' => 'Bahan'], ['judul' => 'Satuan Master'], ['judul' => 'Satuan Komponen'], ['judul' => 'Komponen', 'angka' => true], ['judul' => 'Faktor Berlaku', 'angka' => true], ['judul' => 'Faktor Manual (perbaikan)', 'lebar' => '260px']],
        'baris' => $barisBeda, 'cari' => 'Cari bahan…', 'paginasi' => 25, 'class' => 'satu-baris',
    ]);
    ?></div>
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
  <div class="bungkus-tabel"><?php
  $barisDaftar = [];
  foreach ($daftar as $a) {
      $barisDaftar[] = [
          'atribut' => ! empty($a['cari_teks']) ? 'data-cari-teks="' . esc($a['cari_teks'], 'attr') . '"' : '',
          'sel' => [
              ['html' => '<strong>' . esc($a['kode']) . '</strong>'],
              ['teks' => $a['nama'] ?? '', 'class' => 'potong', 'title' => $a['nama'] ?? ''],
              ['teks' => $a['satuan'] ?? ''],
              ['html' => rp($a['harga_satuan'] ?? 0) . ((float) ($a['harga_satuan'] ?? 0) <= 0 ? ' <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">belum terverifikasi</span>' : ''), 'angka' => true],
              ['teks' => (string) (int) $a['jml_komponen'], 'angka' => true],
              ['html' => (int) $a['dipakai'] . ' pekerjaan<br><span class="kecil-teks">' . (int) ($a['dipakai_didanai'] ?? 0) . ' di gedung didanai' . ((int) ($a['dipakai_lain'] ?? 0) > 0 ? ' · ' . (int) $a['dipakai_lain'] . ' di gedung tidak didanai' : '') . '</span>', 'angka' => true],
              ['html' => '<a class="tombol kecil" href="' . base_url('analisa/' . $a['id']) . '">Buka</a>'],
          ],
      ];
  }
  echo tabel_bantu([
      'kolom' => [['judul' => 'Kode'], ['judul' => 'Nama Analisa'], ['judul' => 'Satuan'], ['judul' => 'Harga Satuan', 'angka' => true], ['judul' => 'Komponen', 'angka' => true], ['judul' => 'Dipakai di Pekerjaan', 'angka' => true], ['judul' => 'Aksi']],
      'baris' => $barisDaftar, 'cari' => '', 'paginasi' => 25, 'class' => 'satu-baris',
  ]);
  ?></div>
</div>

<?= form_modal([
    'id' => 'modal-konversi',
    'judul' => 'Atur Faktor Konversi',
    'aksi' => base_url('analisa/simpan-konversi'),
    'lebar' => 440,
    'tersembunyi' => ['bahan_id', 'satuan_komponen'],
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px"><strong id="fk-nama"></strong><br>Satuan komponen: <strong id="fk-satuan-komponen"></strong> · Satuan master: <strong id="fk-satuan-master"></strong></p>',
    'kolom' => [
        ['nama' => 'faktor', 'label' => 'Faktor (pengali ke satuan master)', 'placeholder' => 'mis. 0,02', 'penuh' => true],
    ],
    'catatan_html' => '<p class="kecil-teks" style="margin:12px 0 0" id="fk-pratinjau">—</p>',
    'segarkan' => ['#panel-satuan'],
    'tombol' => ['simpan_teks' => 'Simpan Faktor', 'hapus' => false],
]) ?>

<script>
// Jembatan modal konversi: helper form_modal() merender modalnya; skrip
// ini hanya mengisi keterangan bahan/satuan + pratinjau arti faktor.
(function () {
  function angka(s) {
    s = String(s || '').trim();
    if (!s) return 0;
    if (s.indexOf(',') >= 0) s = s.replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
  }
  function fmt(n) { return String(Math.round(n * 100000000) / 100000000).replace('.', ','); }
  function pratinjau() {
    var inp = document.querySelector('#modal-konversi input[name="faktor"]');
    var f = angka(inp ? inp.value : '');
    var sk = document.getElementById('fk-satuan-komponen').textContent;
    var sm = document.getElementById('fk-satuan-master').textContent;
    document.getElementById('fk-pratinjau').textContent = f > 0
      ? 'Berarti: 1 ' + sk + ' = ' + fmt(f) + ' ' + sm + ' · contoh 100 ' + sk + ' terhitung ' + fmt(100 * f) + ' ' + sm + '.'
      : 'Isi faktor untuk melihat artinya. Contoh semen: komponen kg, master zak → faktor 0,02.';
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-atur-konversi]');
    if (!b) return;
    document.getElementById('fk-nama').textContent = b.dataset.nama;
    document.getElementById('fk-satuan-komponen').textContent = b.dataset.satuanKomponen;
    document.getElementById('fk-satuan-master').textContent = b.dataset.satuanMaster;
    window.bukaFormModal('modal-konversi', {
      bahan_id: b.dataset.bahan, satuan_komponen: b.dataset.satuanKomponen, faktor: b.dataset.faktor,
    });
    pratinjau();
  });
  document.addEventListener('input', function (e) {
    if (e.target.matches && e.target.matches('#modal-konversi input[name="faktor"]')) pratinjau();
  });
})();
</script>

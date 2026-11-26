<div class="panel">
  <h2 style="margin-top:0">Validasi Harga Satuan</h2>
  <p class="kecil-teks" style="margin-top:0">Dua pemeriksaan atas harga satuan di tabel pekerjaan untuk <strong>kode analisa yang sama</strong> (sekolah aktif): (1) antar pekerjaan — kode yang sama seharusnya memakai harga yang sama; (2) pekerjaan vs <strong>kalkulasi analisa_komponen</strong> (jumlah koefisien × tarif efektif, sama dengan harga AHSP yang dipakai aplikasi). Perbaikan menyamakan <em>harga_satuan</em> pekerjaan dalam satu kode; harga berjalan dari BA Revisi yang disahkan tidak disentuh.</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <span class="lencana <?= $bedaAntar ? 'kuning' : 'hijau' ?>">Antar pekerjaan berbeda: <?= count($bedaAntar) ?> kode</span>
    <span class="lencana <?= $bedaKalkulasi ? 'kuning' : 'hijau' ?>">Berbeda dari kalkulasi: <?= count($bedaKalkulasi) ?> kode</span>
    <span class="lencana abu">Total kode diperiksa: <?= count($semua) ?></span>
  </div>
</div>

<div class="panel">
  <h2 style="margin-top:0">1 · Harga Berbeda Antar Pekerjaan (Kode Sama)</h2>
  <?php if ($bedaAntar === []): ?>
  <p class="kecil-teks" style="margin-bottom:0">Tidak ada kode analisa yang harga satuannya berbeda antar pekerjaan. Semua pekerjaan berkode sama sudah memakai harga yang sama.</p>
  <?php else: ?>
  <div class="bungkus-tabel">
    <?php
    // Kedua tabel pemeriksaan memakai helper tabel_bantu().
    $barisAntar = [];
    foreach ($bedaAntar as $v) {
        $hargaHtml = '';
        $aksiHtml = '';
        foreach ($v['harga'] as $h => $n) {
            $hargaHtml .= '<div>' . rp($h) . ' <span class="kecil-teks">× ' . (int) $n . ' pekerjaan</span></div>';
            $aksiHtml .= '<form method="post" action="' . base_url('validasi-harga/samakan-pekerjaan') . '" data-konfirmasi="Samakan harga satuan semua pekerjaan berkode ' . esc($v['kode'], 'attr') . ' ke ' . esc(rp($h), 'attr') . '?" style="display:inline-block;margin:0 4px 4px 0">'
                . csrf_field()
                . '<input type="hidden" name="kode" value="' . esc($v['kode'], 'attr') . '">'
                . '<input type="hidden" name="harga" value="' . esc($h, 'attr') . '">'
                . '<button type="submit" class="tombol kecil">Samakan ke ' . rp($h) . '</button></form>';
        }
        $barisAntar[] = ['sel' => [
            ['html' => '<strong>' . esc($v['kode']) . '</strong>'],
            ['html' => esc($v['nama'] ?? '—') . ' <span class="kecil-teks">(' . (int) $v['n'] . ' pekerjaan)</span>', 'class' => 'potong', 'title' => (string) ($v['nama'] ?? '')],
            ['html' => $hargaHtml],
            ['html' => $aksiHtml],
        ]];
    }
    echo tabel_bantu([
        'class' => 'satu-baris',
        'cari' => '',
        'paginasi' => 15,
        'kolom' => [
            ['judul' => 'Kode'],
            ['judul' => 'Analisa'],
            ['judul' => 'Harga yang Dipakai'],
            ['judul' => 'Aksi Samakan'],
        ],
        'baris' => $barisAntar,
    ]);
    ?>
  </div>
  <p class="kecil-teks" style="margin-bottom:0">Pilih harga yang benar untuk kode itu — semua pekerjaan berkode sama akan memakai harga tersebut.</p>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 style="margin-top:0">2 · Harga Pekerjaan vs Kalkulasi Analisa Komponen</h2>
  <?php if ($bedaKalkulasi === []): ?>
  <p class="kecil-teks" style="margin-bottom:0">Tidak ada perbedaan: harga satuan pekerjaan sudah sama dengan kalkulasi komponen untuk semua kode yang analisanya memiliki komponen.</p>
  <?php else: ?>
  <div class="aksi" style="justify-content:flex-start;margin-bottom:10px">
    <form method="post" action="<?= base_url('validasi-harga/samakan-kalkulasi-semua') ?>" data-konfirmasi="Samakan harga satuan pekerjaan pada <?= count($bedaKalkulasi) ?> kode ke kalkulasi komponennya masing-masing?" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="tombol">Samakan Semua ke Kalkulasi (<?= count($bedaKalkulasi) ?> kode)</button>
    </form>
  </div>
  <div class="bungkus-tabel">
    <?php
    $barisKalkulasi = [];
    foreach ($bedaKalkulasi as $v) {
        $hargaHtml = '';
        foreach ($v['harga'] as $h => $n) {
            $gaya = abs((float) $h - $v['kalkulasi']) > 1 ? ' style="color:var(--ina-red-700,#a61e1e);font-weight:600"' : '';
            $hargaHtml .= '<div' . $gaya . '>' . rp($h) . ' <span class="kecil-teks" style="font-weight:400;color:inherit">× ' . (int) $n . '</span></div>';
        }
        $aksiHtml = '<form method="post" action="' . base_url('validasi-harga/samakan-kalkulasi') . '" data-konfirmasi="Samakan harga satuan semua pekerjaan berkode ' . esc($v['kode'], 'attr') . ' ke kalkulasi komponen ' . esc(rp($v['kalkulasi']), 'attr') . '?" style="display:inline">'
            . csrf_field()
            . '<input type="hidden" name="kode" value="' . esc($v['kode'], 'attr') . '">'
            . '<button type="submit" class="tombol kecil">Samakan ke Kalkulasi</button></form>';
        $barisKalkulasi[] = ['sel' => [
            ['html' => '<strong>' . esc($v['kode']) . '</strong>'],
            ['html' => esc($v['nama'] ?? '—') . ' <span class="kecil-teks">(' . (int) $v['n'] . ' pekerjaan)</span>', 'class' => 'potong', 'title' => (string) ($v['nama'] ?? '')],
            ['html' => $hargaHtml],
            ['teks' => $v['harga_a'] !== null && $v['harga_a'] > 0 ? rp($v['harga_a']) : '—', 'angka' => true],
            ['html' => '<strong>' . rp($v['kalkulasi']) . '</strong>', 'angka' => true],
            ['html' => $aksiHtml],
        ]];
    }
    echo tabel_bantu([
        'class' => 'satu-baris',
        'cari' => '',
        'paginasi' => 15,
        'kolom' => [
            ['judul' => 'Kode'],
            ['judul' => 'Analisa'],
            ['judul' => 'Harga di Pekerjaan'],
            ['judul' => 'Harga Analisa (Harsat)', 'angka' => true],
            ['judul' => 'Kalkulasi Komponen', 'angka' => true],
            ['judul' => 'Aksi'],
        ],
        'baris' => $barisKalkulasi,
    ]);
    ?>
  </div>
  <p class="kecil-teks" style="margin-bottom:0">Harga berwarna merah berbeda dari kalkulasi komponen. Kolom Harga Analisa (Harsat) adalah harga satuan tersimpan di tabel analisa dari dokumen — ditampilkan sebagai bahan pertimbangan; yang menjadi pembanding resmi adalah kalkulasi komponen.</p>
  <?php endif; ?>
</div>

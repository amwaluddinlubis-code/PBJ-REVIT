<h1>Acuan <?= esc($acuan['kode_kita']) ?> — Banding Acuan Sekolah</h1>
<p class="kecil-teks"><a href="<?= base_url('acuan') ?>">&larr; Acuan AHSP</a></p>

<div class="panel">
  <table class="satu-baris">
    <tbody>
      <tr><td style="width:180px"><strong>Analisa Kita</strong></td>
          <td><?= $analisa ? esc($acuan['kode_kita']) . ' — ' . esc($analisa['nama']) . ' (' . esc($analisa['satuan']) . ')' : '— belum ada di sekolah aktif —' ?></td></tr>
      <tr><td><strong>Padanan Nasional</strong></td>
          <td><?php if ($acuan['tanpa_padanan']): ?>Tidak ada padanan pada AHSP Cipta Karya<?php else: ?><?= esc($acuan['kode_nasional']) ?> — <?= esc($acuan['nama']) ?> (<?= esc($acuan['satuan']) ?>)<?php endif; ?></td></tr>
      <?php if (! empty($acuan['catatan'])): ?>
      <tr><td><strong>Catatan</strong></td><td><?= esc($acuan['catatan']) ?></td></tr>
      <?php endif; ?>
      <?php if ($banding): ?>
      <tr><td><strong>Harga Satuan</strong></td>
          <td>Kita Rp <?= number_format($banding['harga_kita'], 0, ',', '.') ?> · Dengan koefisien acuan Rp <?= number_format($banding['harga_acuan'], 0, ',', '.') ?><?php if ($banding['selisih_harga'] !== null): ?> (<?= ($banding['selisih_harga'] > 0 ? '+' : '−') . number_format(abs($banding['selisih_harga']), 1, ',', '.') ?>%)<?php endif; ?></td></tr>
      <?php endif; ?>
      <?php if ($analisa): ?>
      <tr><td><strong>Perbaiki Analisa</strong></td>
          <td><a class="tombol kecil" href="<?= base_url('analisa/' . $analisa['id']) ?>">Buka di Analisa</a> <span class="kecil-teks">Koefisien diubah di halaman analisa — kolom Koef. Acuan &amp; Nilai Acuan tampil di samping setiap komponennya.</span></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($banding): ?>
<div class="panel">
  <div class="bungkus-tabel">
    <?php
    // Tabel banding komponen memakai helper tabel_bantu(); lebar kolom
    // persen menggantikan colgroup (kelas tabel-acuan: table-layout fixed).
    // Tabel ringkasan kunci-nilai di atas dipertahankan manual — bukan
    // tabel daftar (tanpa kepala kolom).
    $barisBanding = [];
    foreach ($banding['baris'] as $b) {
        $selisih = '—';
        if ($b['selisih'] !== null) {
            $gaya = '';
            if (abs($b['selisih']) > 25) {
                $gaya = ' style="color:var(--ina-red-700,#a61e1e);font-weight:600"';
            } elseif (abs($b['selisih']) <= 5) {
                $gaya = ' style="color:var(--ina-green-700,#1e6b2a)"';
            }
            $selisih = '<span' . $gaya . '>' . ($b['selisih'] > 0 ? '+' : '−') . number_format(abs($b['selisih']), 1, ',', '.') . '%</span>';
        }
        $barisBanding[] = ['sel' => [
            (string) $b['nama'],
            (string) $b['satuan'],
            ['teks' => $b['kita'] !== null ? number_format($b['kita'], 5, ',', '.') : '—', 'angka' => true],
            ['teks' => $b['acuan'] !== null ? number_format($b['acuan'], 5, ',', '.') : '—', 'angka' => true],
            ['html' => $selisih, 'angka' => true],
        ]];
    }
    echo tabel_bantu([
        'class' => 'satu-baris tabel-acuan',
        'cari' => '',
        'kolom' => [
            ['judul' => 'Komponen (nama acuan)', 'lebar' => '34%'],
            ['judul' => 'Satuan', 'lebar' => '12%'],
            ['judul' => 'Koef. Kita', 'angka' => true, 'lebar' => '19%'],
            ['judul' => 'Koef. Acuan', 'angka' => true, 'lebar' => '19%'],
            ['judul' => 'Selisih %', 'angka' => true, 'lebar' => '16%'],
        ],
        'baris' => $barisBanding,
    ]);
    ?>
  </div>
  <p class="kecil-teks">Selisih merah = menyimpang lebih dari 25% dari acuan. Koefisien agregat (pasir/kerikil) dikonversi antar satuan memakai berat isi standar aplikasi (pasir 1.400 kg/m³, kerikil 1.600 kg/m³). Komponen Kepala Tukang &amp; Mandor hanya ada di acuan (sudah dihilangkan dari aplikasi) dan tidak ikut harga acuan. Perbaikan koefisien dilakukan di modul Analisa (tombol Buka di Analisa di atas) — halaman ini hanya pembanding dan tidak mengubah data.</p>
</div>
<?php elseif (! $acuan['tanpa_padanan']): ?>
<div class="panel"><p class="kecil-teks">Analisa berkode <?= esc($acuan['kode_kita']) ?> belum ada di sekolah aktif, jadi belum bisa dibandingkan.</p></div>
<?php endif; ?>

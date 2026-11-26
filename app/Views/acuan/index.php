<h1>Acuan AHSP — <?= esc($sekolah['nama_sekolah'] ?? 'Sekolah Aktif') ?></h1>
<p class="kecil-teks">Set acuan koefisien <strong>milik sekolah aktif ini</strong>, disalin dari acuan nasional SE Dirjen Bina Konstruksi No. 68/SE/Dk/2024, Lampiran VI — AHSP Bidang Cipta Karya dan Perumahan (dasar Permen PUPR No. 8 Tahun 2023). Setiap sekolah punya salinannya sendiri, jadi set ini tidak mengubah acuan sekolah lain. Yang dibandingkan adalah <strong>koefisien</strong> analisa sekolah ini terhadap item acuannya (pemetaan lewat kode analisa); harga acuan dihitung ulang memakai tarif komponen sekolah sendiri, dengan Kepala Tukang &amp; Mandor bertarif Rp 0 karena sudah dihilangkan dari aplikasi. Koefisien acuan bukan angka wajib — penyimpangan besar layak ditanyakan ke perencana.</p>

<?php if (! $ada): ?>
<div class="panel">
  <p class="kecil-teks">Tabel acuan belum ada. Jalankan dari folder aplikasi: <code>php spark migrate</code>.</p>
</div>
<?php elseif ($daftar === []): ?>
<div class="panel">
  <p style="margin-top:0">Sekolah ini belum punya set acuan.</p>
  <?php if (! empty($perSekolah) && ! empty($adaTemplat)): ?>
  <form method="post" action="<?= base_url('acuan/siapkan') ?>">
    <?= csrf_field() ?>
    <button type="submit">Siapkan Acuan dari Nasional</button>
  </form>
  <p class="kecil-teks" style="margin-bottom:0">Menyalin seluruh item acuan nasional ke sekolah ini. Sesudah itu set ini berdiri sendiri dan tidak lagi mengikuti perubahan templat.</p>
  <?php else: ?>
  <p class="kecil-teks" style="margin-bottom:0">Templat nasional juga belum dimuat. Jalankan dari folder aplikasi: <code>php spark migrate</code> lalu <code>php spark impor:acuan</code>, kemudian kembali ke halaman ini.</p>
  <?php endif; ?>
</div>
<?php else: ?>
<?php
$terpadan = 0; $tanpa = 0;
foreach ($daftar as $d) { if ($d['acuan']['tanpa_padanan']) { $tanpa++; } elseif ($d['analisa']) { $terpadan++; } }
?>
<div class="panel">
  <p class="kecil-teks"><?= count($daftar) ?> item acuan sekolah ini · <?= $terpadan ?> terpadankan ke analisa sekolah ini · <?= $tanpa ?> tanpa padanan nasional</p>
  <div class="bungkus-tabel">
    <?php
    // Tabel daftar acuan memakai helper tabel_bantu(); lebar kolom persen
    // menggantikan colgroup (kelas tabel-acuan memakai table-layout fixed).
    $barisAcuan = [];
    foreach ($daftar as $d) {
        $ac = $d['acuan']; $an = $d['analisa']; $rg = $d['ringkas'];
        $selisih = '—';
        if ($rg && $rg['selisih_harga'] !== null) {
            $selisih = '<span style="color:' . ($rg['selisih_harga'] > 0 ? 'var(--ina-green-700,#1e6b2a)' : 'var(--ina-red-700,#a61e1e)') . '">'
                . ($rg['selisih_harga'] > 0 ? '+' : '−') . number_format(abs($rg['selisih_harga']), 1, ',', '.') . '%</span>';
        }
        $barisAcuan[] = ['sel' => [
            (string) $ac['kode_kita'],
            $an ? (string) $an['nama'] : ['html' => '<span class="kecil-teks">— belum ada di sekolah ini —</span>'],
            $ac['tanpa_padanan']
                ? ['html' => '<span class="kecil-teks">tidak ada padanan</span>']
                : $ac['kode_nasional'] . ' — ' . $ac['nama'],
            ['teks' => $rg ? 'Rp ' . number_format($rg['harga_kita'], 0, ',', '.') : '—', 'angka' => true],
            ['teks' => $rg ? 'Rp ' . number_format($rg['harga_acuan'], 0, ',', '.') : '—', 'angka' => true],
            ['html' => $selisih, 'angka' => true],
            ['teks' => $rg ? (int) $rg['menyimpang'] . ' komponen' : '—', 'angka' => true],
            ['html' => '<a class="tombol kecil" href="' . base_url('acuan/' . $ac['id']) . '">Banding</a>'],
        ]];
    }
    echo tabel_bantu([
        'class' => 'satu-baris tabel-acuan',
        'cari' => '',
        'paginasi' => 25,
        'kolom' => [
            ['judul' => 'Kode Kita', 'lebar' => '7%'],
            ['judul' => 'Analisa Kita (Sekolah Ini)', 'lebar' => '23%'],
            ['judul' => 'Padanan Nasional', 'lebar' => '20%'],
            ['judul' => 'Harga Kita', 'angka' => true, 'lebar' => '12%'],
            ['judul' => 'Harga Acuan', 'angka' => true, 'lebar' => '12%'],
            ['judul' => 'Selisih %', 'angka' => true, 'lebar' => '8%'],
            ['judul' => 'Menyimpang', 'angka' => true, 'lebar' => '8%'],
            ['judul' => 'Aksi', 'lebar' => '10%'],
        ],
        'baris' => $barisAcuan,
    ]);
    ?>
  </div>
</div>
<?php endif; ?>

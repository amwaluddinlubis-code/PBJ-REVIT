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
    <table class="satu-baris tabel-acuan" data-cari data-paginasi="25">
      <colgroup><col style="width:7%"><col style="width:23%"><col style="width:20%"><col style="width:12%"><col style="width:12%"><col style="width:8%"><col style="width:8%"><col style="width:10%"></colgroup>
      <thead><tr>
        <th>Kode Kita</th><th>Analisa Kita (Sekolah Ini)</th><th>Padanan Nasional</th>
        <th class="angka">Harga Kita</th><th class="angka">Harga Acuan</th><th class="angka">Selisih %</th>
        <th class="angka">Menyimpang</th><th>Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($daftar as $d): $ac = $d['acuan']; $an = $d['analisa']; $rg = $d['ringkas']; ?>
        <tr>
          <td><?= esc($ac['kode_kita']) ?></td>
          <td><?= $an ? esc($an['nama']) : '<span class="kecil-teks">— belum ada di sekolah ini —</span>' ?></td>
          <td><?php if ($ac['tanpa_padanan']): ?><span class="kecil-teks">tidak ada padanan</span><?php else: ?><?= esc($ac['kode_nasional']) ?> — <?= esc($ac['nama']) ?><?php endif; ?></td>
          <td class="angka"><?= $rg ? 'Rp ' . number_format($rg['harga_kita'], 0, ',', '.') : '—' ?></td>
          <td class="angka"><?= $rg ? 'Rp ' . number_format($rg['harga_acuan'], 0, ',', '.') : '—' ?></td>
          <td class="angka"><?php if ($rg && $rg['selisih_harga'] !== null): ?><span style="color:<?= $rg['selisih_harga'] > 0 ? 'var(--ina-green-700,#1e6b2a)' : 'var(--ina-red-700,#a61e1e)' ?>"><?= ($rg['selisih_harga'] > 0 ? '+' : '−') . number_format(abs($rg['selisih_harga']), 1, ',', '.') ?>%</span><?php else: ?>—<?php endif; ?></td>
          <td class="angka"><?= $rg ? (int) $rg['menyimpang'] . ' komponen' : '—' ?></td>
          <td><a class="tombol kecil" href="<?= base_url('acuan/' . $ac['id']) ?>">Banding</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

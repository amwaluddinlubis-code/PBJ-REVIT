<h1>Master Acuan AHSP Nasional</h1>
<p class="kecil-teks">Acuan koefisien nasional: SE Dirjen Bina Konstruksi No. 68/SE/Dk/2024, Lampiran VI — AHSP Bidang Cipta Karya dan Perumahan (dasar Permen PUPR No. 8 Tahun 2023). Yang dibandingkan adalah <strong>koefisien</strong> analisa sekolah aktif terhadap item nasional padanannya (pemetaan lewat kode analisa); harga acuan dihitung ulang memakai tarif komponen sekolah sendiri, dengan Kepala Tukang &amp; Mandor bertarif Rp 0 karena sudah dihilangkan dari aplikasi. Koefisien nasional adalah acuan, bukan angka wajib — penyimpangan besar layak ditanyakan ke perencana.</p>

<?php if (! $ada || $daftar === []): ?>
<div class="panel">
  <p class="kecil-teks">Master acuan belum dimuat. Jalankan dari folder aplikasi: <code>php spark migrate</code> lalu <code>php spark impor:acuan</code>.</p>
</div>
<?php else: ?>
<?php
$terpadan = 0; $tanpa = 0;
foreach ($daftar as $d) { if ($d['acuan']['tanpa_padanan']) { $tanpa++; } elseif ($d['analisa']) { $terpadan++; } }
?>
<div class="panel">
  <p class="kecil-teks"><?= count($daftar) ?> item acuan · <?= $terpadan ?> terpadankan ke analisa sekolah aktif · <?= $tanpa ?> tanpa padanan nasional</p>
  <div class="bungkus-tabel">
    <table class="satu-baris" data-cari data-paginasi="25">
      <thead><tr>
        <th>Kode Kita</th><th>Analisa Kita (Sekolah Aktif)</th><th>Padanan Nasional</th>
        <th class="angka">Harga Kita</th><th class="angka">Harga Acuan</th><th class="angka">Selisih %</th>
        <th class="angka">Menyimpang</th><th>Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($daftar as $d): $ac = $d['acuan']; $an = $d['analisa']; $rg = $d['ringkas']; ?>
        <tr>
          <td><?= esc($ac['kode_kita']) ?></td>
          <td><?= $an ? esc($an['nama']) : '<span class="kecil-teks">— belum ada di sekolah aktif —</span>' ?></td>
          <td><?php if ($ac['tanpa_padanan']): ?><span class="kecil-teks">tidak ada padanan</span><?php else: ?><?= esc($ac['kode_nasional']) ?> — <?= esc($ac['nama']) ?><?php endif; ?></td>
          <td class="angka"><?= $rg ? 'Rp ' . number_format($rg['harga_kita'], 0, ',', '.') : '—' ?></td>
          <td class="angka"><?= $rg ? 'Rp ' . number_format($rg['harga_acuan'], 0, ',', '.') : '—' ?></td>
          <td class="angka"><?php if ($rg && $rg['selisih_harga'] !== null): ?><span style="color:<?= $rg['selisih_harga'] > 0 ? 'var(--ina-green-700,#1e6b2a)' : 'var(--ina-red-700,#a61e1e)' ?>"><?= ($rg['selisih_harga'] > 0 ? '+' : '−') . number_format(abs($rg['selisih_harga']), 1, ',', '.') ?>%</span><?php else: ?>—<?php endif; ?></td>
          <td class="angka"><?= $rg ? (int) $rg['menyimpang'] . ' komponen' : '—' ?></td>
          <td><a class="aksi" href="<?= base_url('acuan/' . $ac['id']) ?>">Banding</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

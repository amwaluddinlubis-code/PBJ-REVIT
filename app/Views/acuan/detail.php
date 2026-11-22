<h1>Acuan <?= esc($acuan['kode_kita']) ?> — Banding AHSP Nasional</h1>
<p class="kecil-teks"><a href="<?= base_url('acuan') ?>">&larr; Master Acuan AHSP Nasional</a></p>

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
    </tbody>
  </table>
</div>

<?php if ($banding): ?>
<div class="panel">
  <div class="bungkus-tabel">
    <table class="satu-baris" data-cari>
      <thead><tr><th>Komponen (nama acuan)</th><th>Satuan</th><th class="angka">Koef. Kita</th><th class="angka">Koef. Acuan</th><th class="angka">Selisih %</th></tr></thead>
      <tbody>
      <?php foreach ($banding['baris'] as $b): ?>
        <tr>
          <td><?= esc($b['nama']) ?></td>
          <td><?= esc($b['satuan']) ?></td>
          <td class="angka"><?= $b['kita'] !== null ? number_format($b['kita'], 5, ',', '.') : '—' ?></td>
          <td class="angka"><?= $b['acuan'] !== null ? number_format($b['acuan'], 5, ',', '.') : '—' ?></td>
          <td class="angka"><?php if ($b['selisih'] !== null): ?><span<?php if (abs($b['selisih']) > 25): ?> style="color:var(--ina-red-700,#a61e1e);font-weight:600"<?php elseif (abs($b['selisih']) <= 5): ?> style="color:var(--ina-green-700,#1e6b2a)"<?php endif; ?>><?= ($b['selisih'] > 0 ? '+' : '−') . number_format(abs($b['selisih']), 1, ',', '.') ?>%</span><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="kecil-teks">Selisih merah = menyimpang lebih dari 25% dari acuan. Koefisien agregat (pasir/kerikil) dikonversi antar satuan memakai berat isi standar aplikasi (pasir 1.400 kg/m³, kerikil 1.600 kg/m³). Komponen Kepala Tukang &amp; Mandor hanya ada di acuan (sudah dihilangkan dari aplikasi) dan tidak ikut harga acuan.</p>
</div>
<?php elseif (! $acuan['tanpa_padanan']): ?>
<div class="panel"><p class="kecil-teks">Analisa berkode <?= esc($acuan['kode_kita']) ?> belum ada di sekolah aktif, jadi belum bisa dibandingkan.</p></div>
<?php endif; ?>

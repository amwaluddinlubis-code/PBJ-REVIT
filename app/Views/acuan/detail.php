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
      <?php if ($banding['bisa_count'] > 0): ?>
      <tr><td><strong>Perbaiki ke Acuan</strong></td>
          <td>
            <form class="inline" method="post" action="<?= base_url('acuan/samakan-semua') ?>" data-konfirmasi="Samakan <?= (int) $banding['bisa_count'] ?> koefisien komponen analisa ini ke nilai acuan nasional? Tarif tidak berubah; harga satuan analisa dihitung ulang dan perubahan tercatat di Jejak Audit." data-konfirmasi-judul="Samakan ke Acuan Nasional" data-konfirmasi-tombol="Ya, Samakan Semua">
              <?= csrf_field() ?>
              <input type="hidden" name="acuan_id" value="<?= (int) $acuan['id'] ?>">
              <button type="submit" class="kecil">Samakan Semua ke Acuan (<?= (int) $banding['bisa_count'] ?>)</button>
            </form>
            <span class="kecil-teks">atau satu per satu lewat tombol Samakan pada baris komponen di bawah.</span>
          </td></tr>
      <?php endif; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($banding): ?>
<div class="panel">
  <div class="bungkus-tabel">
    <table class="satu-baris" data-cari>
      <thead><tr><th>Komponen (nama acuan)</th><th>Satuan</th><th class="angka">Koef. Kita</th><th class="angka">Koef. Acuan</th><th class="angka">Selisih %</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($banding['baris'] as $b): ?>
        <tr>
          <td><?= esc($b['nama']) ?></td>
          <td><?= esc($b['satuan']) ?></td>
          <td class="angka"><?= $b['kita'] !== null ? number_format($b['kita'], 5, ',', '.') : '—' ?></td>
          <td class="angka"><?= $b['acuan'] !== null ? number_format($b['acuan'], 5, ',', '.') : '—' ?></td>
          <td class="angka"><?php if ($b['selisih'] !== null): ?><span<?php if (abs($b['selisih']) > 25): ?> style="color:var(--ina-red-700,#a61e1e);font-weight:600"<?php elseif (abs($b['selisih']) <= 5): ?> style="color:var(--ina-green-700,#1e6b2a)"<?php endif; ?>><?= ($b['selisih'] > 0 ? '+' : '−') . number_format(abs($b['selisih']), 1, ',', '.') ?>%</span><?php else: ?>—<?php endif; ?></td>
          <td><?php if ($b['bisa']): ?>
            <form class="inline" method="post" action="<?= base_url('acuan/samakan') ?>" data-konfirmasi="Ubah koefisien <?= esc($b['nama']) ?> dari <?= number_format($b['kita'], 5, ',', '.') ?> menjadi <?= number_format($b['target'], 5, ',', '.') ?> <?= esc($b['satuan']) ?> mengikuti acuan nasional <?= esc($acuan['kode_nasional']) ?>? Tarif tidak berubah; harga satuan analisa dihitung ulang." data-konfirmasi-judul="Samakan ke Acuan" data-konfirmasi-tombol="Ya, Samakan">
              <?= csrf_field() ?>
              <input type="hidden" name="acuan_id" value="<?= (int) $acuan['id'] ?>">
              <input type="hidden" name="grup" value="<?= esc($b['grup'], 'attr') ?>">
              <button type="submit" class="kecil">Samakan ke Acuan</button>
            </form>
          <?php elseif ($b['jumlah_kita'] > 1 && $b['acuan'] !== null): ?>
            <span class="kecil-teks"><?= (int) $b['jumlah_kita'] ?> komponen kita — ubah lewat Analisa</span>
          <?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="kecil-teks">Selisih merah = menyimpang lebih dari 25% dari acuan. Koefisien agregat (pasir/kerikil) dikonversi antar satuan memakai berat isi standar aplikasi (pasir 1.400 kg/m³, kerikil 1.600 kg/m³). Komponen Kepala Tukang &amp; Mandor hanya ada di acuan (sudah dihilangkan dari aplikasi) dan tidak ikut harga acuan. Tombol <strong>Samakan ke Acuan</strong> mengganti koefisien komponen kita dengan nilai acuan (khusus operator/admin, tercatat di Jejak Audit); grup yang berisi lebih dari satu komponen kita hanya bisa diubah lewat modul Analisa.</p>
</div>
<?php elseif (! $acuan['tanpa_padanan']): ?>
<div class="panel"><p class="kecil-teks">Analisa berkode <?= esc($acuan['kode_kita']) ?> belum ada di sekolah aktif, jadi belum bisa dibandingkan.</p></div>
<?php endif; ?>

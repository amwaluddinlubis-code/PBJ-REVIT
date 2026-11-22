<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 6, ',', '.'), '0'), ',');
$lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb'];
$total = 0.0;
foreach ($komponen as $k) {
    $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0);
}
?>
<p style="margin:0 0 10px"><a href="<?= base_url('analisa') ?>">← Semua Analisa</a></p>
<div class="panel">
  <h2 style="margin-top:0"><?= esc($a['kode']) ?> — <?= esc($a['nama'] ?? '') ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:99px;font-size:12px;vertical-align:middle">harga satuan belum terverifikasi</span><?php endif; ?></h2>
  <?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px">Harga satuan analisa ini tidak terbaca dari sheet Harsat dokumen sumber (tertulis #REF! di dokumennya). Menyimpan perubahan komponen di bawah akan mengisinya dari jumlah komponen.</p>
  <?php endif; ?>
  <p class="kecil-teks" style="margin-top:0">Satuan analisa: <strong><?= esc($a['satuan'] ?? '-') ?></strong> · Harga satuan tersimpan: <strong><?= $rp($a['harga_satuan'] ?? 0) ?></strong> · Jumlah komponen: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong> per satuan. Ubah koefisien/tarif lalu klik Simpan — kebutuhan bahan &amp; upah di semua gedung yang memakai analisa ini langsung berubah.</p>

  <form method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-simpan') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel"><table class="satu-baris">
      <tr><th>Jenis</th><th>Komponen</th><th style="width:120px">Koefisien</th><th>Satuan</th><th style="width:150px">Tarif (Rp)</th><th class="angka">Nilai / Satuan</th><th>Aksi</th></tr>
      <?php foreach ($komponen as $k): ?>
      <tr>
        <td><span style="background:<?= $lencana[$k['jenis']] ?? '#e5e7eb' ?>;padding:1px 7px;border-radius:99px;font-size:11px"><?= esc(ucfirst($k['jenis'])) ?></span></td>
        <td class="potong" title="<?= esc($k['nama']) ?>"><?= esc($k['nama_master'] ?? $k['nama']) ?><?php if (! empty($k['kode_master'])): ?> <span class="kecil-teks">(<?= esc($k['kode_master']) ?>)</span><?php endif; ?></td>
        <td><input type="text" name="komp[<?= (int) $k['id'] ?>][koefisien]" value="<?= esc((string) $k['koefisien']) ?>" style="width:105px"></td>
        <td><?= esc($k['satuan'] ?? '') ?></td>
        <td><input type="text" name="komp[<?= (int) $k['id'] ?>][tarif]" value="<?= (int) ($k['tarif'] ?? 0) ?>" style="width:135px"></td>
        <td class="angka"><?= $rp((float) $k['koefisien'] * (float) ($k['tarif'] ?? 0)) ?></td>
        <td><button type="submit" class="tombol kecil abu" form="hapus-komp-<?= (int) $k['id'] ?>">Hapus</button></td>
      </tr>
      <?php endforeach; ?>
      <tr class="baris-total"><td colspan="5"><strong>Total komponen per satuan</strong></td><td class="angka"><strong><?= $rp($total) ?></strong></td><td></td></tr>
    </table></div>
    <p style="margin:10px 0 0"><button type="submit" class="tombol">Simpan Koefisien &amp; Tarif</button></p>
  </form>
  <?php foreach ($komponen as $k): ?>
  <form id="hapus-komp-<?= (int) $k['id'] ?>" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-hapus/' . $k['id']) ?>" data-konfirmasi="Hapus komponen <?= esc($k['nama']) ?> dari analisa ini?" style="display:none"><?= csrf_field() ?></form>
  <?php endforeach; ?>
</div>

<div class="panel">
  <h3 style="margin-top:0">Tambah Komponen</h3>
  <form method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-tambah') ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
    <?= csrf_field() ?>
    <label>Jenis
      <select name="jenis" data-select-cari>
        <option value="bahan">Bahan</option>
        <option value="upah">Upah</option>
        <option value="alat">Alat</option>
      </select>
    </label>
    <label>Bahan dari master (khusus jenis Bahan)
      <?= select_cari('bahan_id', array_merge([['value' => '', 'label' => '— ketik nama manual —']], array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama']], $bahan))) ?>
    </label>
    <label>Nama komponen <input type="text" name="nama" placeholder="nama komponen"></label>
    <label>Koefisien <input type="text" name="koefisien" size="8" placeholder="0,0000"></label>
    <label>Satuan <input type="text" name="satuan" size="6" placeholder="m3 / OH / zak"></label>
    <label>Tarif (Rp) <input type="text" name="tarif" size="10" placeholder="otomatis bila dari master"></label>
    <button type="submit" class="tombol kecil">+ Tambah Komponen</button>
  </form>
  <p class="kecil-teks">Untuk jenis Upah, isi Nama persis seperti jenis di Master Upah agar tarif master berlaku. Untuk jenis Bahan dari master, tarif otomatis memakai harga acuan bahan bila kolom tarif dikosongkan.</p>
</div>

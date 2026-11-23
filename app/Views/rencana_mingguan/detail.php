<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$qty = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
$polos = static fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
?>
<p style="margin:0 0 10px"><a href="<?= base_url('rencana-mingguan') ?>">← Semua Rencana Mingguan</a></p>
<div class="panel">
  <h2 style="margin-top:0">Rencana Mingguan — <?= esc($gedung['nama'] ?? '') ?></h2>
  <p class="kecil-teks" style="margin-top:0">Minggu <?= esc(date('d/m/Y', strtotime($r['minggu_mulai']))) ?> – <?= esc(date('d/m/Y', strtotime($r['minggu_mulai'] . ' +6 days'))) ?><?= ! empty($r['keterangan']) ? ' · ' . esc($r['keterangan']) : '' ?>. Isi volume yang ditargetkan selesai minggu ini, lalu daftar belanja bersih terhitung di bawah.</p>

  <?php if (! empty($adaKurva)): ?>
  <form method="post" action="<?= base_url('rencana-mingguan/' . $r['id'] . '/target-kurva') ?>" data-konfirmasi="Isi target otomatis dari rencana Kurva S resmi? Target per bab dihitung dari rencana kumulatif dokumen s.d. minggu kalender tanggal mulai rencana ini, dikurangi realisasi yang sudah tercatat (mengejar ketertinggalan), lalu dibagi ke pekerjaan menurut sisa nilainya. Target yang sudah terisi pada pekerjaan di bab terpetakan akan diganti; pekerjaan lain tidak diubah." data-konfirmasi-judul="Ambil Target dari Kurva S" data-konfirmasi-tombol="Ya, Isi Target" style="margin:0 0 12px">
    <?= csrf_field() ?>
    <button type="submit" class="tombol">Ambil Target dari Kurva S</button>
    <span class="kecil-teks">Mengisi target dari jadwal resmi perencana untuk minggu kalender yang memuat tanggal mulai di atas.</span>
  </form>
  <?php endif; ?>

  <form method="post" action="<?= base_url('rencana-mingguan/' . $r['id'] . '/target') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari pekerjaan…">
      <tr><th>Pekerjaan</th><th>Analisa</th><th class="angka">Volume Berjalan</th><th class="angka">Sudah Realisasi</th><th class="angka">Sisa</th><th class="angka" data-tanpa-urut>Target Minggu Ini</th></tr>
      <?php foreach ($pekerjaan as $p): $sisa = max(0, (float) $p['vol_berjalan'] - (float) $p['terealisasi']); ?>
      <tr>
        <td class="potong" title="<?= esc($p['nama']) ?>"><?= esc($p['bab'] ? $p['bab'] . ' — ' : '') ?><?= esc($p['nama']) ?></td>
        <td><?= esc($p['kode_analisa'] ?? '-') ?></td>
        <td class="angka"><?= $qty($p['vol_berjalan']) ?> <?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= $qty($p['terealisasi']) ?></td>
        <td class="angka"><?= $qty($sisa) ?></td>
        <td class="angka"><input type="text" name="target[<?= (int) $p['id'] ?>]" value="<?= $p['target'] !== null ? esc($polos($p['target'])) : '' ?>" placeholder="0" style="width:100px;margin:0;text-align:right"></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p style="margin-top:10px"><button type="submit" class="tombol">Simpan Target &amp; Hitung Belanja</button></p>
  </form>
</div>

<?php if ($bahan !== []): ?>
<div class="panel">
  <h2 style="margin-top:0">Daftar Belanja Bersih Minggu Ini</h2>
  <p class="kecil-teks" style="margin-top:0">Perlu Beli = kebutuhan dari target − stok tersedia (sudah di-BAST + saldo Buku Material) − pesanan berjalan (SPK yang belum di-BAST). Jumlah beli boleh diubah sebelum dibuatkan SPK. Estimasi total belanja bersih: <strong><?= $rp($total_bersih) ?></strong> · sisa pagu sekolah saat ini: <strong><?= $rp($sisa_pagu) ?></strong>.</p>
  <form method="post" action="<?= base_url('rencana-mingguan/' . $r['id'] . '/buat-spk') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari bahan…">
      <tr><th data-tanpa-urut>Beli</th><th>Bahan</th><th>Satuan</th><th class="angka">Kebutuhan</th><th class="angka">Stok Tersedia</th><th class="angka">Dalam Pesanan</th><th class="angka" data-tanpa-urut>Jumlah Beli</th><th class="angka">Harga Acuan</th><th class="angka">Nilai</th></tr>
      <?php foreach ($bahan as $b): ?>
      <tr>
        <td><input type="checkbox" name="pilih[]" value="<?= (int) $b['bahan_id'] ?>"<?= $b['bersih'] > 0 ? ' checked' : '' ?>></td>
        <td class="potong" title="<?= esc($b['nama']) ?>"><strong><?= esc($b['kode']) ?></strong> <?= esc($b['nama']) ?><?php if (($b['analisa_pertama'] ?? 0) > 0): ?> <a class="tombol kecil abu" href="<?= base_url('analisa/' . (int) $b['analisa_pertama']) ?>" title="Buka analisa sumber bahan ini">Analisa</a><?php endif; ?></td>
        <td><?= esc($b['satuan'] ?? '') ?></td>
        <td class="angka"><?= $qty($b['kotor']) ?></td>
        <td class="angka"><?= $qty($b['stok']) ?></td>
        <td class="angka"><?= $qty($b['berjalan']) ?></td>
        <td class="angka"><input type="text" name="qty[<?= (int) $b['bahan_id'] ?>]" value="<?= esc($polos($b['bersih'])) ?>" style="width:100px;margin:0;text-align:right"></td>
        <td class="angka"><?= $rp($b['harga']) ?></td>
        <td class="angka"><?= $rp($b['nilai']) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="baris-total"><td colspan="8"><strong>Total estimasi belanja bersih</strong></td><td class="angka"><strong><?= $rp($total_bersih) ?></strong></td></tr>
    </table></div>
    <p style="margin-top:10px"><button type="submit" class="tombol">Buat SPK dari Daftar Ini</button></p>
  </form>
</div>
<?php endif; ?>

<?php if ($upah !== []): ?>
<div class="panel">
  <h2 style="margin-top:0">Kebutuhan Upah Minggu Ini</h2>
  <div class="bungkus-tabel"><table class="satu-baris">
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (Oh)</th><th class="angka">Perkiraan Nilai</th></tr>
    <?php foreach ($upah as $u): ?>
    <tr><td><?= esc($u['nama']) ?></td><td class="angka"><?= $qty($u['oh']) ?></td><td class="angka"><?= $rp($u['nilai']) ?></td></tr>
    <?php endforeach; ?>
    <tr class="baris-total"><td><strong>Total perkiraan upah</strong></td><td></td><td class="angka"><strong><?= $rp($total_upah) ?></strong></td></tr>
  </table></div>
  <p class="kecil-teks">Oh = orang-hari. Bagi kebutuhan Oh dengan jumlah hari kerja untuk memperkirakan jumlah tukang/pekerja yang perlu hadir setiap hari.</p>
</div>
<?php endif; ?>

<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total Anggaran (Pagu)</div><div class="nilai"><?= $anggaran > 0 ? $rp($anggaran) : 'Belum diatur' ?></div></div>
  <div class="kpi"><div class="label">Belanja Terbayar</div><div class="nilai"><?= $rp($terbayar) ?></div></div>
  <div class="kpi"><div class="label">Sisa Pagu</div><div class="nilai"><?= $sisa_pagu !== null ? $rp($sisa_pagu) : '—' ?></div></div>
  <div class="kpi"><div class="label">Gedung Tercakup</div><div class="nilai"><?= count($semuaGedung ?? []) ?> gedung</div></div>
</div>
<?php if ($anggaran > 0): $persen = min(100, $terbayar / $anggaran * 100); ?>
<div class="panel">
  <h2>Kontrol Pagu (Total Belanja ≤ Total Anggaran)</h2>
  <div class="bar<?= $persen > 90 ? '' : ' hijau' ?>" style="height:10px"><div style="width:<?= $persen ?>%;<?= $persen > 90 ? 'background:var(--ina-red-500,#f02d2d)' : '' ?>"></div></div>
  <p class="kecil-teks" style="margin-bottom:0">Terpakai <?= number_format($persen, 1, ',', '.') ?>%. Harga satuan belanja boleh melebihi harga dokumen perencana (selisihnya tampil di laporan); yang dikunci hanya total keseluruhan. Pembayaran yang menembus pagu akan ditahan aplikasi.</p>
</div>
<?php endif; ?>

<form method="get" action="<?= base_url('rencana-belanja') ?>" style="margin-bottom:14px">
  <label style="display:inline-block">Tampilkan kebutuhan:
    <select data-select-cari name="gedung" onchange="this.form.submit()" style="width:auto;display:inline-block;margin-left:6px">
      <option value="0">Seluruh sekolah (gabungan gedung)</option>
      <?php foreach ($semuaGedung ?? [] as $gd): ?>
        <option value="<?= (int) $gd['id'] ?>"<?= $gedung_filter === (int) $gd['id'] ? ' selected' : '' ?>><?= esc($gd['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</form>

<div class="panel">
  <h2>Rencana Belanja Bahan — Dari Kebutuhan Pekerjaan</h2>
  <form method="post" action="<?= base_url('rencana-belanja/buat-spk') ?>">
    <?= csrf_field() ?>
    <table data-paginasi="25" data-cari="Cari bahan…">
      <tr><th style="width:34px"></th><th>Bahan</th><th class="angka">Kebutuhan</th><th class="angka">Sudah Dipesan</th><th class="angka">Sisa Kebutuhan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Sisa</th><th style="width:130px">Qty Rencana</th></tr>
      <?php $totalSisa = 0; foreach ($kebutuhan['tertaut'] as $b): $nilaiSisa = (float) $b['sisa'] * (float) $b['harga']; $totalSisa += $nilaiSisa; ?>
      <tr>
        <td><input type="checkbox" name="pilih[]" value="<?= (int) $b['bahan_id'] ?>"<?= $b['sisa'] > 0 ? ' checked' : '' ?> style="width:auto;margin:0"></td>
        <td><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?><?= ! empty($b['dikonversi']) ? ' <span class="lencana kuning">dikonversi</span>' : '' ?><?= ! empty($b['satuan_beda']) ? ' <span class="lencana abu">satuan beda</span>' : '' ?><br><span class="kecil-teks">satuan: <?= esc($b['satuan']) ?></span></td>
        <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?></td>
        <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
        <td class="angka"><b><?= number_format((float) $b['sisa'], 2, ',', '.') ?></b></td>
        <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
        <td class="angka"><?= number_format($nilaiSisa, 0, ',', '.') ?></td>
        <td><input type="text" name="qty[<?= (int) $b['bahan_id'] ?>]" value="<?= esc(rtrim(rtrim(number_format((float) $b['sisa'], 2, '.', ''), '0'), '.')) ?>" style="margin:0"></td>
      </tr>
      <?php endforeach; ?>
      <tr class="total"><td colspan="6">Total Nilai Sisa Kebutuhan</td><td class="angka"><?= number_format($totalSisa, 0, ',', '.') ?></td><td></td></tr>
    </table>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" style="margin-top:14px">
      <label>Gedung tujuan SPK
        <select data-select-cari name="gedung_id">
          <option value="">— umum —</option>
          <?php foreach ($semuaGedung ?? [] as $gd): ?>
            <option value="<?= (int) $gd['id'] ?>"<?= $gedung_filter === (int) $gd['id'] ? ' selected' : '' ?>><?= esc($gd['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="aksi"><button type="submit">Buat SPK dari Pilihan</button></div>
    </div>
    <p class="kecil-teks">SPK dibuat dengan nomor berikutnya, tanggal hari ini, dan harga acuan saat ini. Penyedia dapat dilengkapi sesudahnya di Register SPK. Jumlah rencana bawaan = sisa kebutuhan; silakan sesuaikan (mis. dibeli bertahap).</p>
  </form>
</div>

<div class="panel">
  <h2>Rencana Upah — Dari Kebutuhan Tenaga</h2>
  <table data-paginasi="25" data-cari="Cari jenis tenaga…">
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Tarif / OH</th><th class="angka">Nilai Kebutuhan</th></tr>
    <?php $totalUpah = 0; foreach ($kebutuhan_upah as $u): $n = (float) $u['orang_hari'] * (float) $u['tarif']; $totalUpah += $n; ?>
    <tr><td><?= esc($u['jenis']) ?></td><td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td><td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td><td class="angka"><?= number_format($n, 0, ',', '.') ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="3">Total Nilai Kebutuhan Upah</td><td class="angka"><?= number_format($totalUpah, 0, ',', '.') ?></td></tr>
  </table>
  <p class="kecil-teks">Pembayaran upah dicatat per gedung di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a> dan ikut terhitung dalam kontrol pagu.</p>
</div>

<?php $polos = static fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); ?>
<div class="kartu-kpi">
  <div class="kpi"><div class="label">Total Anggaran (Pagu)</div><div class="nilai"><?= $anggaran > 0 ? rp($anggaran) : 'Belum diatur' ?></div></div>
  <div class="kpi"><div class="label">Belanja Terbayar</div><div class="nilai"><?= rp($terbayar) ?></div></div>
  <div class="kpi"><div class="label">Sisa Pagu</div><div class="nilai"><?= $sisa_pagu !== null ? rp($sisa_pagu) : '—' ?></div></div>
  <div class="kpi"><div class="label">Gedung Tercakup</div><div class="nilai"><?= count($semuaGedung ?? []) ?> gedung</div></div>
</div>
<?php if ($anggaran > 0): $persen = min(100, $terbayar / $anggaran * 100); ?>
<details class="panel panel-lipat" data-lipat-id="rencana-kontrol-pagu-total-belanja-total-anggaran" open>
  <summary>Kontrol Pagu (Total Belanja ≤ Total Anggaran)</summary>

  <div class="bar<?= $persen > 90 ? '' : ' hijau' ?>" style="height:10px"><div style="width:<?= $persen ?>%;<?= $persen > 90 ? 'background:var(--ina-red-500,#f02d2d)' : '' ?>"></div></div>
  <p class="kecil-teks" style="margin-bottom:0">Terpakai <?= number_format($persen, 1, ',', '.') ?>%. Harga satuan belanja boleh melebihi harga dokumen perencana (selisihnya tampil di laporan); yang dikunci hanya total keseluruhan. Pembayaran yang menembus pagu akan ditahan aplikasi.</p>
</details>
<?php endif; ?>

<details class="panel panel-lipat" data-lipat-id="rencana-langkah-1-pilih-pekerjaan" open>
  <summary>Langkah 1 — Pilih Pekerjaan</summary>

  <p class="kecil-teks" style="margin-top:0">Centang pekerjaan yang mau direncanakan belanjanya — boleh dari satu gedung atau gabungan beberapa gedung. Volume Rencana bawaan = sisa volume (volume berjalan − realisasi tercatat); silakan diubah bila belanja direncanakan bertahap.</p>
  <form method="get" action="<?= base_url('rencana-belanja') ?>">
    <label style="display:inline-block;margin-bottom:10px">Tampilkan pekerjaan:
      <select data-select-cari name="gedung" onchange="this.form.submit()" style="width:auto;display:inline-block;margin-left:6px">
        <option value="0">Seluruh sekolah (gabungan gedung)</option>
        <?php foreach ($semuaGedung ?? [] as $gd): ?>
          <option value="<?= (int) $gd['id'] ?>"<?= $gedung_filter === (int) $gd['id'] ? ' selected' : '' ?>><?= esc($gd['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php /* Tabel Langkah 1 dipertahankan manual: centang + isian volume per baris adalah inti alur hitung (form GET). */ ?>
    <table data-paginasi="25" data-cari="Cari pekerjaan…">
      <tr>
        <th style="width:34px"><input type="checkbox" id="centang-semua-pekerjaan" style="width:auto;margin:0" title="Centang semua pekerjaan"></th>
        <th>Gedung</th><th>Pekerjaan</th><th>Analisa</th>
        <th class="angka">Volume Berjalan</th><th class="angka">Realisasi</th><th class="angka">Sisa Volume</th>
        <th style="width:130px">Volume Rencana</th>
      </tr>
      <?php foreach ($daftar_pekerjaan as $p): $pid = (int) $p['id']; $terpilih = in_array($pid, $pilihan_ids ?? [], true); ?>
      <tr>
        <td><input type="checkbox" name="pilih_pekerjaan[]" value="<?= $pid ?>"<?= $terpilih ? ' checked' : '' ?> style="width:auto;margin:0"></td>
        <td><?= esc($p['gedung_nama']) ?></td>
        <td><?= esc($p['nama']) ?><?php if (! empty($p['bab'])): ?><br><span class="kecil-teks"><?= esc($p['bab']) ?></span><?php endif; ?></td>
        <td><?= esc($p['kode_analisa'] ?? '') ?><?php if (! empty($p['nama_analisa'])): ?><br><span class="kecil-teks"><?= esc($p['nama_analisa']) ?></span><?php endif; ?></td>
        <td class="angka"><?= number_format((float) $p['volume_berjalan'], 2, ',', '.') ?> <?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= number_format((float) $p['realisasi'], 2, ',', '.') ?></td>
        <td class="angka"><b><?= number_format((float) $p['sisa_volume'], 2, ',', '.') ?></b></td>
        <td><input type="text" name="vol[<?= $pid ?>]" value="<?= esc($polos($terpilih ? ($vol_rencana[$pid] ?? $p['sisa_volume']) : $p['sisa_volume'])) ?>" style="margin:0"></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-top:12px">
      <label>Target hari kerja (untuk hitungan jumlah pekerja di Langkah 4)
        <input type="number" name="hari" min="1" value="<?= (int) $hari_kerja ?>" style="width:90px">
      </label>
      <div class="aksi"><button type="submit" name="hitung" value="1">Hitung Kebutuhan (Langkah 2–4)</button></div>
    </div>
  </form>
  <script>
  document.getElementById('centang-semua-pekerjaan').addEventListener('change', function () {
    document.querySelectorAll('input[name="pilih_pekerjaan[]"]').forEach(function (c) { c.checked = document.getElementById('centang-semua-pekerjaan').checked; });
  });
  </script>
</details>

<?php if (empty($hasil)): ?>
<div class="panel">
  <p style="margin:0">Sesudah pekerjaan dicentang dan tombol <b>Hitung Kebutuhan</b> ditekan, di bawah ini akan muncul berturut-turut: <b>Langkah 2</b> analisa yang dipakai pekerjaan terpilih, <b>Langkah 3</b> kebutuhan bahannya, dan <b>Langkah 4</b> kebutuhan pekerja/tukangnya.</p>
</div>
<?php else: ?>

<details class="panel panel-lipat" data-lipat-id="rencana-langkah-2-analisa-dari-pekerjaan-terpilih">
  <summary>Langkah 2 — Analisa dari <?= (int) $hasil['jumlah_pekerjaan'] ?> Pekerjaan Terpilih</summary>

  <?php
  $barisAnalisa = [];
  $totalNilaiAnalisa = 0;
  foreach ($hasil['analisa'] as $a) {
      $totalNilaiAnalisa += (float) $a['nilai'];
      $barisAnalisa[] = ['sel' => [
          ['html' => '<a class="lencana abu" style="text-decoration:none" href="' . base_url('analisa/' . (int) $a['id']) . '">' . esc($a['kode']) . '</a>'],
          (string) $a['nama'],
          ['teks' => (string) (int) $a['pekerjaan'], 'angka' => true],
          ['teks' => number_format((float) $a['volume'], 2, ',', '.') . ' ' . ($a['satuan'] ?? ''), 'angka' => true],
          ['teks' => number_format((float) $a['harga'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $a['nilai'], 0, ',', '.'), 'angka' => true],
      ]];
  }
  $barisAnalisa[] = ['total' => [
      ['teks' => 'Total Nilai Analisa Pekerjaan Terpilih', 'colspan' => 5],
      ['teks' => number_format($totalNilaiAnalisa, 0, ',', '.'), 'angka' => true],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari analisa…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Kode'],
          ['judul' => 'Analisa'],
          ['judul' => 'Dipakai Pekerjaan', 'angka' => true],
          ['judul' => 'Total Volume Rencana', 'angka' => true],
          ['judul' => 'Harga AHSP', 'angka' => true],
          ['judul' => 'Nilai', 'angka' => true],
      ],
      'baris' => $barisAnalisa,
  ]);
  ?>
</details>

<details class="panel panel-lipat" data-lipat-id="rencana-langkah-3-kebutuhan-bahan-dari-pekerjaan-t">
  <summary>Langkah 3 — Kebutuhan Bahan dari Pekerjaan Terpilih</summary>

  <form method="post" action="<?= base_url('rencana-belanja/buat-spk') ?>">
    <?= csrf_field() ?>
    <?php foreach ($vol_rencana ?? [] as $pidK => $vK): ?>
    <input type="hidden" name="kerja[<?= (int) $pidK ?>]" value="<?= esc($polos($vK)) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="hari" value="<?= (int) ($hari_kerja ?? 6) ?>">
    <?php /* Tabel Langkah 3 dipertahankan manual: centang + isian qty per baris adalah inti simpan buat-spk. */ ?>
    <table data-paginasi="25" data-cari="Cari bahan…">
      <tr><th style="width:34px"></th><th>Bahan</th><th class="angka">Kebutuhan</th><th class="angka">Sudah Dipesan</th><th class="angka">Sisa Kebutuhan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Sisa</th><th style="width:130px">Qty Rencana</th><th data-tanpa-urut style="width:165px">Aksi</th></tr>
      <?php $totalSisa = 0; foreach ($hasil['kebutuhan']['tertaut'] as $b): $nilaiSisa = (float) $b['sisa'] * (float) $b['harga']; $totalSisa += $nilaiSisa; ?>
      <tr>
        <td><input type="checkbox" name="pilih[]" value="<?= (int) $b['bahan_id'] ?>"<?= $b['sisa'] > 0 ? ' checked' : '' ?> style="width:auto;margin:0"></td>
        <td><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?><?= ! empty($b['dikonversi']) ? ' <span class="lencana kuning">dikonversi</span>' : '' ?><?= ! empty($b['satuan_beda']) ? ' <span class="lencana abu">satuan beda</span>' : '' ?><br><span class="kecil-teks">satuan: <?= esc($b['satuan']) ?></span></td>
        <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?></td>
        <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
        <td class="angka"><b><?= number_format((float) $b['sisa'], 2, ',', '.') ?></b></td>
        <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
        <td class="angka"><?= number_format($nilaiSisa, 0, ',', '.') ?></td>
        <td><input type="text" name="qty[<?= (int) $b['bahan_id'] ?>]" value="<?= esc($polos($b['sisa'])) ?>" style="margin:0"></td>
        <td>
          <?php if (($b['analisa_pertama'] ?? 0) > 0): ?><a class="tombol kecil abu" href="<?= base_url('analisa/' . (int) $b['analisa_pertama']) ?>" title="Buka analisa sumber bahan ini">Analisa</a> <?php endif; ?>
          <button type="submit" class="tombol kecil bahaya" form="hapus-rencana-bahan-<?= (int) $b['bahan_id'] ?>">Hapus</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="total"><td colspan="6">Total Nilai Sisa Kebutuhan</td><td class="angka"><?= number_format($totalSisa, 0, ',', '.') ?></td><td></td><td></td></tr>
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
  <?php foreach ($hasil['kebutuhan']['tertaut'] as $b): ?>
  <form id="hapus-rencana-bahan-<?= (int) $b['bahan_id'] ?>" method="post" action="<?= base_url('rencana-belanja/hapus-bahan/' . (int) $b['bahan_id']) ?>" data-konfirmasi="Hapus bahan <?= esc($b['nama']) ?> dari rencana belanja? Bahan ini berasal dari <?= (int) ($b['komponen_dari'] ?? 0) ?> komponen di <?= (int) ($b['analisa_dari'] ?? 0) ?> analisa — komponennya akan dihapus dari modul Analisa sehingga bahan hilang dari kebutuhan SEMUA gedung. SPK yang sudah terlanjur memakai bahan ini tidak berubah, dan penghapusan dapat dipulihkan lewat Pemeliharaan Database (Sampah)." hidden>
    <?= csrf_field() ?>
  </form>
  <?php endforeach; ?>
  <?php if (! empty($hasil['kebutuhan']['tak_tertaut'])): ?>
  <p class="kecil-teks" style="margin-bottom:0">Komponen bahan yang belum tertaut ke master bahan (belum bisa direncanakan belanjanya): <?php $tt = []; foreach ($hasil['kebutuhan']['tak_tertaut'] as $nama => $q) { $tt[] = esc($nama) . ' (' . number_format((float) $q, 2, ',', '.') . ')'; } echo implode(', ', $tt); ?>. Tautkan dari tab Kebutuhan Bahan pada halaman <a href="<?= base_url('gedung') ?>">Gedung</a> — panel Komponen Belum Tertaut ada di sana.</p>
  <?php endif; ?>
</details>

<details class="panel panel-lipat" data-lipat-id="rencana-langkah-4-kebutuhan-pekerja-dari-pekerjaan">
  <summary>Langkah 4 — Kebutuhan Pekerja dari Pekerjaan Terpilih</summary>

  <?php
  $barisUpah = [];
  $totalUpah = 0;
  $totalPekerja = 0;
  foreach ($hasil['upah'] as $u) {
      $totalUpah += (float) $u['nilai'];
      $totalPekerja += (int) $u['jumlah_pekerja'];
      $barisUpah[] = ['sel' => [
          (string) $u['jenis'],
          ['teks' => number_format((float) $u['orang_hari'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['tarif'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['nilai'], 0, ',', '.'), 'angka' => true],
          ['html' => '<b>' . (int) $u['jumlah_pekerja'] . ' orang</b>', 'angka' => true],
      ]];
  }
  $barisUpah[] = ['total' => [
      ['teks' => 'Total Nilai Kebutuhan Upah', 'colspan' => 3],
      ['teks' => number_format($totalUpah, 0, ',', '.'), 'angka' => true],
      ['teks' => $totalPekerja . ' orang', 'angka' => true],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari jenis tenaga…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Jenis Tenaga'],
          ['judul' => 'Kebutuhan (OH)', 'angka' => true],
          ['judul' => 'Tarif / OH', 'angka' => true],
          ['judul' => 'Nilai Kebutuhan', 'angka' => true],
          ['judul' => 'Jumlah Pekerja Dibutuhkan', 'angka' => true],
      ],
      'baris' => $barisUpah,
  ]);
  ?>
  <p class="kecil-teks">Jumlah pekerja dihitung dari kebutuhan OH dibagi target hari kerja (<?= (int) $hari_kerja ?> hari, diatur di Langkah 1) dan dibulatkan ke atas — itulah jumlah orang per jenis tenaga yang perlu disiapkan bila pekerjaan terpilih mau diselesaikan dalam target hari itu. Pembayaran upah dicatat per gedung di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a> dan ikut terhitung dalam kontrol pagu.</p>
</details>
<?php endif; ?>

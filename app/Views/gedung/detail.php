<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$tabAktif = $tab ?? 'boq';
?>
<div style="margin-bottom:12px">
  <span class="lencana biru">Nilai BOQ <b>&nbsp;<?= $rp($g['nilai_boq']) ?></b></span>
  <span class="lencana hijau">Realisasi <b>&nbsp;<?= $rp($nilai_realisasi) ?> (<?= number_format($persen_fisik, 1, ',', '.') ?>%)</b></span>
  <?php if (! empty($g['sumber_sheet'])): ?><span class="lencana abu">Sumber sheet: <?= esc($g['sumber_sheet']) ?></span><?php endif; ?>
  <a class="tombol kecil" style="float:right" href="<?= base_url('rencana-belanja?gedung=' . $g['id']) ?>">Buat Rencana Belanja Gedung Ini</a>
</div>
<div class="tab">
  <a href="<?= base_url('gedung/' . $g['id'] . '/boq') ?>"<?= $tabAktif === 'boq' ? ' class="aktif"' : '' ?>>BOQ Pekerjaan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/bahan') ?>"<?= $tabAktif === 'bahan' ? ' class="aktif"' : '' ?>>Kebutuhan Bahan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/upah') ?>"<?= $tabAktif === 'upah' ? ' class="aktif"' : '' ?>>Kebutuhan Upah</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/realisasi') ?>"<?= $tabAktif === 'realisasi' ? ' class="aktif"' : '' ?>>Realisasi</a>
  <a href="<?= base_url('lpj/gedung/' . $g['id']) ?>">LPJ Gedung</a>
</div>

<?php if ($tabAktif === 'boq'): ?>
<div class="panel">
  <h2>BOQ — Daftar Pekerjaan</h2>
  <table data-paginasi="25">
    <tr><th>Pekerjaan</th><th>Kode</th><th class="angka">Volume</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Jumlah</th><th style="width:210px">Ubah Volume / Hapus</th></tr>
    <?php $babLalu = null; $total = 0; foreach ($pekerjaan as $p): $total += (float) $p['jumlah']; ?>
      <?php if ($p['bab'] !== $babLalu): $babLalu = $p['bab']; ?>
      <tr><td colspan="7" style="font-weight:600;background:#fcfcfd"><?= esc($babLalu ?: '—') ?></td></tr>
      <?php endif; ?>
      <tr>
        <td><?= esc($p['nama']) ?></td>
        <td><?= $p['kode_analisa'] ? '<span class="lencana abu">' . esc($p['kode_analisa']) . '</span>' : '—' ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?></td>
        <td><?= esc($p['satuan'] ?? '') ?></td>
        <td class="angka"><?= number_format((float) $p['harga_satuan'], 0, ',', '.') ?></td>
        <td class="angka"><?= number_format((float) $p['jumlah'], 0, ',', '.') ?></td>
        <td>
          <form class="inline" method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/volume') ?>">
            <?= csrf_field() ?>
            <input type="text" name="volume" value="<?= esc((string) $p['volume']) ?>" style="width:76px;display:inline-block;margin:0;padding:4px 7px">
            <button class="kecil" type="submit">Simpan</button>
          </form>
          <form class="inline" method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus pekerjaan ini?')">
            <?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="5">Grand Total BOQ</td><td class="angka"><?= number_format($total, 0, ',', '.') ?></td><td></td></tr>
  </table>
  <p class="kecil-teks">Mengubah volume pekerjaan menghitung ulang kebutuhan bahan &amp; upah gedung ini secara otomatis.</p>
</div>
<div class="panel">
  <h2>Tambah Pekerjaan Manual</h2>
  <form method="post" action="<?= base_url('gedung/pekerjaan/simpan') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama pekerjaan<input type="text" name="nama" required></label>
      <label>Bab<input type="text" name="bab" placeholder="mis. II. PEKERJAAN TANAH"></label>
      <label>Kode analisa<input type="text" name="kode_analisa" placeholder="mis. B.01"></label>
      <label>Satuan<input type="text" name="satuan" placeholder="m3 / m2 / ls"></label>
      <label>Volume<input type="text" name="volume" value="0"></label>
      <label>Harga satuan (Rp)<input type="text" name="harga_satuan" value="0"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Pekerjaan</button></div>
    </div>
  </form>
</div>
<div class="panel">
  <h2>Ubah Nama / Keterangan Gedung</h2>
  <form method="post" action="<?= base_url('gedung/' . $g['id'] . '/perbarui') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" value="<?= esc($g['nama']) ?>" required></label>
      <label>Keterangan<input type="text" name="keterangan" value="<?= esc($g['keterangan'] ?? '') ?>"></label>
      <div class="aksi md:col-span-2"><button type="submit">Simpan</button></div>
    </div>
  </form>
</div>

<?php elseif ($tabAktif === 'bahan'): ?>
<div class="panel">
  <h2>Kebutuhan Bahan — Dihitung dari BOQ × Koefisien AHSP</h2>
  <table data-paginasi="25">
    <tr><th>Kode</th><th>Bahan</th><th class="angka">Kebutuhan</th><th>Satuan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dipesan*</th><th class="angka">Sisa</th></tr>
    <?php $totalNilai = 0; foreach ($kebutuhan['tertaut'] as $b): $nilai = (float) $b['qty_butuh'] * (float) $b['harga']; $totalNilai += $nilai; $sisa = (float) $b['qty_butuh'] - (float) $b['dipesan']; ?>
    <tr>
      <td><?= esc($b['kode']) ?></td>
      <td><?= esc($b['nama']) ?><?= ! empty($b['dikonversi']) ? ' <span class="lencana kuning" title="Dikonversi dari satuan AHSP ke satuan beli">dikonversi</span>' : '' ?><?= ! empty($b['satuan_beda']) ? ' <span class="lencana abu" title="Satuan komponen AHSP berbeda dari satuan bahan master — periksa kewajaran jumlahnya">satuan beda</span>' : '' ?></td>
      <td class="angka"><?= number_format((float) $b['qty_butuh'], 2, ',', '.') ?></td>
      <td><?= esc($b['satuan']) ?></td>
      <td class="angka"><?= number_format((float) $b['harga'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $b['dipesan'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="5">Total Nilai Kebutuhan Bahan Tertaut</td><td class="angka"><?= number_format($totalNilai, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table>
  <p class="kecil-teks">*Sudah dipesan = jumlah pada SPK yang tertaut ke gedung ini. Harga acuan mengikuti jenjang aplikasi (negosiasi → penawaran → RAB). Kebutuhan semen/agregat dikonversi dari kg AHSP: semen ÷50 per zak, pasir ÷1.400 per m³, kerikil/batu ÷1.600 per m³.</p>
</div>
<?php if (! empty($kebutuhan['tak_tertaut'])): ?>
<div class="panel">
  <h2>Komponen Belum Tertaut ke Master Bahan (<?= count($kebutuhan['tak_tertaut']) ?>)</h2>
  <p style="margin-top:0" class="kecil-teks">Komponen AHSP berikut belum punya padanan di master bahan sehingga belum terhitung. Tautkan ke bahan yang sesuai — penautan berlaku untuk semua gedung.</p>
  <table>
    <tr><th>Komponen AHSP</th><th class="angka">Kebutuhan</th><th style="width:46%">Tautkan ke Bahan Master</th></tr>
    <?php foreach ($kebutuhan['tak_tertaut'] as $t): ?>
    <tr>
      <td><?= esc($t['nama']) ?></td>
      <td class="angka"><?= number_format((float) $t['qty_butuh'], 2, ',', '.') ?></td>
      <td>
        <form method="post" action="<?= base_url('gedung/tautkan') ?>" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="nama_komponen" value="<?= esc($t['nama']) ?>">
          <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
          <select name="bahan_id" required style="margin:0">
            <option value="">— pilih bahan —</option>
            <?php foreach ($semuaBahan as $sb): ?><option value="<?= (int) $sb['id'] ?>"><?= esc($sb['kode']) ?> — <?= esc($sb['nama']) ?></option><?php endforeach; ?>
          </select>
          <button class="kecil" type="submit">Tautkan</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<?php elseif ($tabAktif === 'upah'): ?>
<div class="panel">
  <h2>Kebutuhan Upah — Dihitung dari BOQ × Koefisien Tenaga</h2>
  <table>
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Tarif / OH</th><th class="angka">Nilai Kebutuhan</th><th class="angka">Sudah Dibayar (OH)</th><th class="angka">Sudah Dibayar (Rp)</th></tr>
    <?php $totalU = 0; foreach ($kebutuhan_upah as $u): $nilai = (float) $u['orang_hari'] * (float) $u['tarif']; $totalU += $nilai; $byr = $upah_dibayar[$u['jenis']] ?? null; ?>
    <tr>
      <td><?= esc($u['jenis']) ?></td>
      <td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($nilai, 0, ',', '.') ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['oh'], 2, ',', '.') : '—' ?></td>
      <td class="angka"><?= $byr ? number_format((float) $byr['jml'], 0, ',', '.') : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="3">Total Nilai Kebutuhan Upah</td><td class="angka"><?= number_format($totalU, 0, ',', '.') ?></td><td colspan="2"></td></tr>
  </table>
  <p class="kecil-teks">OH = orang-hari. Pembayaran upah dicatat di menu <a href="<?= base_url('upah') ?>">Pembayaran Upah</a>. Tarif mengikuti daftar upah dokumen perencana (berbasis UMK) dan dapat diubah di sana.</p>
</div>

<?php elseif ($tabAktif === 'realisasi'): ?>
<div class="panel">
  <h2>Kemajuan per Pekerjaan</h2>
  <table data-paginasi="25">
    <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th>% Fisik</th><th style="width:300px">Catat Kemajuan</th></tr>
    <?php foreach ($pekerjaan as $p): $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0; ?>
    <tr>
      <td><?= esc($p['nama']) ?><br><span class="kecil-teks"><?= esc($p['bab'] ?? '') ?></span></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume_terealisasi'], 3, ',', '.'), '0'), ',') ?></td>
      <td><div class="bar<?= $persen >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= $persen ?>%"></div></div><span class="kecil-teks"><?= number_format($persen, 1, ',', '.') ?>%</span></td>
      <td>
        <form method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/realisasi') ?>" style="display:flex;gap:5px">
          <?= csrf_field() ?>
          <input type="text" name="volume" placeholder="Volume" required style="margin:0;width:84px">
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" style="margin:0;width:132px">
          <button class="kecil" type="submit">Catat</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="panel">
  <h2>Riwayat Pencatatan Terakhir</h2>
  <table>
    <tr><th>Tanggal</th><th>Pekerjaan</th><th class="angka">Volume</th><th>Keterangan</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r): ?>
    <tr>
      <td><?= esc($r['tanggal']) ?></td>
      <td><?= esc($r['nama_pekerjaan']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $r['volume'], 3, ',', '.'), '0'), ',') ?></td>
      <td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('gedung/realisasi/' . $r['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus catatan ini?')"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($riwayat === []): ?><tr><td colspan="5">Belum ada pencatatan kemajuan.</td></tr><?php endif; ?>
  </table>
</div>
<?php endif; ?>

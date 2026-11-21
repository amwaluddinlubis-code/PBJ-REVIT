<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><a class="tombol" href="<?= base_url('bahan/baru') ?>">+ Tambah Bahan</a></div>
<div class="panel">
<table data-paginasi="10">
  <tr><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="angka">Harga RAB</th><th class="angka">Qty RAB</th><th class="angka">Anggaran</th><th class="angka">Terpakai (SPK)</th><th class="angka">Sisa</th><th></th></tr>
  <?php foreach ($bahan as $b): $sisa = (float) $b['qty_total'] - (float) $b['terpakai']; ?>
  <tr<?= $sisa < 0 ? ' class="negatif"' : '' ?>>
    <td><?= esc($b['kode']) ?></td>
    <td><?= esc($b['nama']) ?></td>
    <td><?= esc($b['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($b['harga_rab']) ?></td>
    <td class="angka"><?= number_format((float) $b['qty_total'], 2, ',', '.') ?></td>
    <td class="angka"><?= $rp($b['anggaran']) ?></td>
    <td class="angka"><?= number_format((float) $b['terpakai'], 2, ',', '.') ?></td>
    <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    <td><a href="<?= base_url('bahan/ubah/' . $b['id']) ?>">Ubah</a></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<?php if (! empty($duplikatBahan)): ?>
<div class="panel" style="border-left:4px solid var(--ta-kuning,#b58900)">
  <h2>ALERT: Bahan Sama dengan Satuan / Harga Berbeda</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Bahan berikut tercatat lebih dari satu kali dengan nama yang sama tetapi satuan atau harga yang berbeda. <strong>Perbaiki dulu</strong> — tentukan satuan &amp; harga final dan baris master utamanya — <strong>barulah gabungkan</strong> ke Master Bahan: semua referensi di SPK, analisa, survei harga, negosiasi, dan buku material dialihkan ke master itu, kuantitas RAB dijumlahkan, dan baris duplikat dihapus. Sesudah digabung, bahan ini hanya punya satu satuan dan satu harga acuan untuk semua modul.</p>
  <?php foreach ($duplikatBahan as $grup): ?>
  <form method="post" action="<?= base_url('bahan/gabung') ?>" data-konfirmasi="Gabungkan duplikat bahan ini? Referensi dari baris lain akan dialihkan ke baris utama yang dipilih.">
    <?= csrf_field() ?>
    <table>
      <tr><th style="width:36px">Utama</th><th>Kode</th><th>Nama</th><th>Satuan</th><th class="angka">Harga RAB</th><th class="angka">Qty RAB Total</th></tr>
      <?php foreach ($grup['anggota'] as $i => $b): ?>
      <tr>
        <td><input type="radio" name="utama" value="<?= (int) $b['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><?= esc($b['kode']) ?><input type="hidden" name="anggota[]" value="<?= (int) $b['id'] ?>"></td>
        <td><?= esc($b['nama']) ?></td>
        <td><?= esc($b['satuan'] ?? '') ?></td>
        <td class="angka"><?= $rp($b['harga_rab']) ?></td>
        <td class="angka"><?= number_format((float) $b['qty_rkb'] + (float) $b['qty_adm'] + (float) $b['qty_toilet'], 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Satuan final (perbaiki bila perlu) <input type="text" name="satuan_final" value="<?= esc($grup['anggota'][0]['satuan'] ?? '') ?>" size="8"></label>
      <label>Harga final (Rp) <input type="text" name="harga_final" value="<?= (int) $grup['anggota'][0]['harga_rab'] ?>"></label>
      <span class="kecil-teks">Satuan &amp; harga final ini yang menjadi acuan master setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Master Upah — Referensi Tarif Tenaga Kerja</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Satu jenis tenaga = satu tarif acuan. Master ini dipakai modul Upah saat mencatat pembayaran dan menjadi acuan komponen upah pada analisa. Menambah jenis dengan nama yang sama akan ditolak.</p>
  <table>
    <tr><th>Jenis Tenaga</th><th>Satuan</th><th class="angka">Tarif</th><th style="width:300px">Ubah Tarif</th><th></th></tr>
    <?php foreach ($upah as $u): ?>
    <tr>
      <td><?= esc($u['jenis']) ?></td>
      <td><?= esc($u['satuan'] ?? 'OH') ?></td>
      <td class="angka"><?= $rp($u['tarif']) ?></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('bahan/upah-perbarui/' . $u['id']) ?>"><?= csrf_field() ?><input type="text" name="tarif" value="<?= (int) $u['tarif'] ?>" size="10"> <button class="kecil" type="submit">Simpan</button></form>
      </td>
      <td><form class="inline" method="post" action="<?= base_url('bahan/upah-hapus/' . $u['id']) ?>" data-konfirmasi="Hapus jenis tenaga ini dari Master Upah? Pembayaran lama tidak berubah."><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($upah === []): ?><tr><td colspan="5">Master upah masih kosong.</td></tr><?php endif; ?>
  </table>
  <form method="post" action="<?= base_url('bahan/upah-simpan') ?>" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:end">
    <?= csrf_field() ?>
    <label>Jenis tenaga baru <input type="text" name="jenis" required placeholder="mis. Tukang Batu"></label>
    <label>Satuan <input type="text" name="satuan" value="OH" size="5"></label>
    <label>Tarif (Rp) <input type="text" name="tarif" required placeholder="mis. 93578"></label>
    <button type="submit" class="kecil">+ Tambah ke Master Upah</button>
  </form>

  <?php if (! empty($duplikatUpah)): ?>
  <h3 style="margin-bottom:6px">Duplikat Jenis Tenaga</h3>
  <?php foreach ($duplikatUpah as $grup): ?>
  <form method="post" action="<?= base_url('bahan/upah-gabung') ?>" data-konfirmasi="Gabungkan duplikat jenis tenaga ini mengikuti tarif baris utama?">
    <?= csrf_field() ?>
    <table>
      <tr><th style="width:36px">Utama</th><th>Jenis</th><th>Satuan</th><th class="angka">Tarif</th></tr>
      <?php foreach ($grup['anggota'] as $i => $u): ?>
      <tr>
        <td><input type="radio" name="utama" value="<?= (int) $u['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><?= esc($u['jenis']) ?><input type="hidden" name="anggota[]" value="<?= (int) $u['id'] ?>"></td>
        <td><?= esc($u['satuan'] ?? 'OH') ?></td>
        <td class="angka"><?= $rp($u['tarif']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Tarif final (Rp) <input type="text" name="tarif_final" value="<?= (int) $grup['anggota'][0]['tarif'] ?>"></label>
      <span class="kecil-teks">Tarif final ini yang menjadi acuan Master Upah setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit" class="kecil">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

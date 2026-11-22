<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2 style="margin-top:0">Analisa Pekerjaan (AHSP)</h2>
  <p class="kecil-teks" style="margin-top:0">Analisa menentukan kebutuhan bahan &amp; upah dari volume BOQ. Klik <strong>Buka</strong> untuk mengubah koefisien atau tarif komponen tanpa impor ulang — harga satuan analisa dihitung ulang dari komponennya dan kebutuhan di semua gedung langsung mengikuti.</p>
  <?php if (($belum ?? 0) > 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px"><strong><?= (int) $belum ?> analisa</strong> harga satuannya <strong>belum terverifikasi</strong> — tidak terbaca dari sheet Harsat dokumen sumber (di dokumennya sendiri tertulis #REF!). Harga BOQ tetap yang dipakai untuk nilai pekerjaan; penanda ini hanya agar Bapak tahu analisa mana yang harga satuannya belum bisa dicek silang.</p>
  <?php endif; ?>
  <?php if (! empty($komponen_nol)): ?>
  <details style="margin-bottom:12px">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Komponen Bertarif 0 — <?= count($komponen_nol) ?> komponen belum punya tarif pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk rincian)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><table class="satu-baris" data-cari="Cari komponen…" data-paginasi="25">
      <tr><th>Analisa</th><th>Jenis</th><th>Komponen</th><th class="angka">Koefisien</th><th>Satuan</th><th>Aksi</th></tr>
      <?php foreach ($komponen_nol as $k): ?>
      <tr>
        <td><strong><?= esc($k['kode']) ?></strong> <span class="kecil-teks"><?= esc($k['analisa_nama']) ?></span></td>
        <td><?= esc(ucfirst($k['jenis'])) ?></td>
        <td class="potong" title="<?= esc($k['komponen']) ?>"><?= esc($k['komponen']) ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ',') ?></td>
        <td><?= esc($k['satuan'] ?? '') ?></td>
        <td><a class="tombol kecil" href="<?= base_url('analisa/' . $k['analisa_id']) ?>">Isi Tarif</a></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p class="kecil-teks">Komponen bertarif 0 membuat nilai kebutuhan analisa itu kurang dari seharusnya. Isi tarifnya lewat tombol Isi Tarif, atau dari Master Bahan/Master Upah bila komponennya tertaut master.</p>
  </details>
  <?php endif; ?>
  <?php if (! empty($beda_satuan)): ?>
  <details style="margin-bottom:12px">
    <summary style="cursor:pointer;font-weight:600;color:#92400e">Satuan Bahan Berbeda — <?= count($beda_satuan) ?> pasangan satuan komponen ≠ satuan master pada analisa <?= $tab === 'didanai' ? 'gedung didanai' : 'lainnya' ?> (klik untuk memperbaiki)</summary>
    <div class="bungkus-tabel" style="margin-top:8px"><table class="satu-baris" data-cari="Cari bahan…" data-paginasi="25">
      <tr><th>Bahan</th><th>Satuan Master</th><th>Satuan Komponen</th><th class="angka">Komponen</th><th class="angka">Faktor Berlaku</th><th style="width:260px">Faktor Manual (perbaikan)</th></tr>
      <?php foreach ($beda_satuan as $r): ?>
      <tr>
        <td class="potong" title="<?= esc($r['nama']) ?>"><strong><?= esc($r['kode']) ?></strong> <?= esc($r['nama']) ?></td>
        <td><?= esc($r['satuan_master']) ?></td>
        <td><?= esc($r['satuan_komponen']) ?></td>
        <td class="angka"><?= (int) $r['jml'] ?></td>
        <td class="angka"><?= rtrim(rtrim(number_format((float) $r['faktor_berlaku'], 8, ',', '.'), '0'), ',') ?><?= $r['faktor_manual'] !== null ? ' <span style="background:#dcfce7;color:#166534;padding:1px 7px;border-radius:99px;font-size:11px">manual</span>' : '' ?></td>
        <td>
          <form class="inline" method="post" action="<?= base_url('analisa/simpan-konversi') ?>" style="display:inline-flex;gap:6px;align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="bahan_id" value="<?= (int) $r['bahan_id'] ?>">
            <input type="hidden" name="satuan_komponen" value="<?= esc($r['satuan_komponen']) ?>">
            <input type="text" name="faktor" value="<?= $r['faktor_manual'] !== null ? esc(rtrim(rtrim(number_format((float) $r['faktor_manual'], 8, ',', '.'), '0'), ',')) : '' ?>" placeholder="mis. 0,02" style="width:90px;margin:0">
            <button type="submit" class="tombol kecil">Simpan</button>
          </form>
          <?php if ($r['faktor_manual'] !== null): ?>
          <form class="inline" method="post" action="<?= base_url('analisa/hapus-konversi') ?>" data-konfirmasi="Hapus konversi manual untuk <?= esc($r['nama']) ?> (<?= esc($r['satuan_komponen']) ?>)? Pasangan ini kembali memakai aturan bawaan." style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="bahan_id" value="<?= (int) $r['bahan_id'] ?>">
            <input type="hidden" name="satuan_komponen" value="<?= esc($r['satuan_komponen']) ?>">
            <button type="submit" class="tombol kecil abu">Hapus Manual</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p class="kecil-teks">Faktor = pengali dari satuan komponen ke satuan master (contoh: semen komponen kg, master zak → faktor 0,02 karena 1 kg = 1/50 zak). Faktor manual mengalahkan aturan bawaan dan langsung mengubah hitungan kebutuhan bahan di Gedung, Rencana Belanja, dan LPJ.</p>
  </details>
  <?php endif; ?>
  <div class="aksi" style="justify-content:flex-start;margin-bottom:12px">
    <a class="tombol<?= ($tab ?? 'didanai') === 'didanai' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=didanai') ?>">Dipakai Gedung Didanai (<?= (int) ($jml_didanai ?? 0) ?>)</a>
    <a class="tombol<?= ($tab ?? 'didanai') === 'lainnya' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=lainnya') ?>">Lainnya — Tidak Didanai / Belum Dipakai (<?= (int) ($jml_lainnya ?? 0) ?>)</a>
  </div>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks"><?= ($tab ?? 'didanai') === 'didanai' ? 'Tidak ada analisa yang dipakai oleh gedung yang didanai.' : 'Semua analisa dipakai oleh gedung yang didanai.' ?></p>
  <?php endif; ?>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari data-paginasi="25">
    <tr><th>Kode</th><th>Nama Analisa</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Komponen</th><th class="angka">Dipakai di Pekerjaan</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $a): ?>
    <tr>
      <td><strong><?= esc($a['kode']) ?></strong></td>
      <td class="potong" title="<?= esc($a['nama'] ?? '') ?>"><?= esc($a['nama'] ?? '') ?></td>
      <td><?= esc($a['satuan'] ?? '') ?></td>
      <td class="angka"><?= $rp($a['harga_satuan'] ?? 0) ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">belum terverifikasi</span><?php endif; ?></td>
      <td class="angka"><?= (int) $a['jml_komponen'] ?></td>
      <td class="angka"><?= (int) $a['dipakai'] ?> pekerjaan<br><span class="kecil-teks"><?= (int) ($a['dipakai_didanai'] ?? 0) ?> di gedung didanai<?= (int) ($a['dipakai_lain'] ?? 0) > 0 ? ' · ' . (int) $a['dipakai_lain'] . ' di gedung tidak didanai' : '' ?></span></td>
      <td><a class="tombol kecil" href="<?= base_url('analisa/' . $a['id']) ?>">Buka</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>

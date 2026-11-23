<details class="panel panel-lipat" data-lipat-id="template-laporan-mesin-laporan-dari-template-excel" open>
  <summary>Mesin Laporan dari Template Excel</summary>

  <p style="margin-top:0">Mengikuti mesin template SPJ BOSP: buat dokumen/laporan dalam format bebas di Excel dengan menaruh <strong>penanda <code>{{NAMA_PLACEHOLDER}}</code></strong> pada sel, unggah berkasnya di sini, lalu render dari data nyata aplikasi (satu SPK, satu gedung, atau rekap sekolah). Baris contoh yang penandanya berawalan <code>RINCIAN_</code>, <code>TENAGA_</code>, <code>KERJA_</code>, atau <code>REKAP_</code> digandakan otomatis mengikuti jumlah baris data. Render <strong>dibatalkan</strong> bila masih ada penanda yang tidak terisi — dokumen setengah jadi tidak akan terunduh.</p>
  <p style="margin-bottom:0">
    Unduh template contoh:
    <?php foreach ($jenis as $kode => $j): ?>
      <a class="tombol kecil abu" href="<?= base_url('template-laporan/contoh/' . $kode) ?>"><?= esc($j['label']) ?></a>
    <?php endforeach; ?>
    <span class="kecil-teks">— berisi semua penanda katalog, tinggal ditata ulang di Excel.</span>
  </p>
</details>

<details class="panel panel-lipat" data-lipat-id="template-laporan-unggah-template">
  <summary>Unggah Template</summary>

  <form method="post" enctype="multipart/form-data" action="<?= base_url('template-laporan/unggah') ?>">
    <?= csrf_field() ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <label>Jenis dokumen<br>
        <select name="jenis" required>
          <?php foreach ($jenis as $kode => $j): ?><option value="<?= esc($kode) ?>"><?= esc($j['label']) ?> (konteks: <?= esc($j['konteks']) ?>)</option><?php endforeach; ?>
        </select>
      </label>
      <label>Nama template<br><input type="text" name="nama" placeholder="mis. SPK format dinas kabupaten" style="width:260px"></label>
      <label>Berkas .xlsx<br><input type="file" name="berkas" accept=".xlsx" required></label>
      <button class="tombol" type="submit">Unggah Template</button>
    </div>
  </form>
</details>

<details class="panel panel-lipat" data-lipat-id="template-laporan-perpustakaan-template">
  <summary>Perpustakaan Template</summary>

  <?php if ($template === []): ?>
    <p class="kecil-teks" style="margin-bottom:0">Belum ada template. Unduh template contoh di atas, tata di Excel, lalu unggah di formulir ini.</p>
  <?php else: ?>
  <table data-paginasi="10" data-cari="Cari template…">
    <tr><th>Jenis</th><th>Nama</th><th>Placeholder Terdeteksi</th><th>Aktif</th><th>Render dari Data</th><th></th></tr>
    <?php foreach ($template as $t): $terpakai = array_filter(explode(',', (string) ($t['placeholder_terpakai'] ?? ''))); ?>
    <tr>
      <td><?= esc($jenis[$t['jenis']]['label'] ?? $t['jenis']) ?></td>
      <td><strong><?= esc($t['nama']) ?></strong><br><span class="kecil-teks"><?= esc(tglWaktu($t['created_at'] ?? null)) ?></span></td>
      <td><span class="kecil-teks"><?= count($terpakai) ?> penanda<?= $terpakai ? ': ' . esc(implode(', ', array_slice($terpakai, 0, 6))) . (count($terpakai) > 6 ? ', …' : '') : '' ?></span></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('template-laporan/aktif/' . $t['id']) ?>"><?= csrf_field() ?><button type="submit" class="kecil <?= (int) $t['aktif'] === 1 ? '' : 'abu' ?>"><?= (int) $t['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></button></form>
      </td>
      <td>
        <?php if ($t['jenis'] === 'spk'): ?>
          <form class="inline" method="get" action="<?= base_url('template-laporan/render/' . $t['id']) ?>" style="display:flex;gap:6px">
            <select name="spk" data-select-cari data-placeholder="— pilih SPK —" required>
              <option value="">— pilih SPK —</option>
              <?php foreach ($daftar_spk as $s): ?><option value="<?= (int) $s['id'] ?>">SPK <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="kecil">Render .xlsx</button>
          </form>
        <?php elseif ($t['jenis'] === 'gedung'): ?>
          <form class="inline" method="get" action="<?= base_url('template-laporan/render/' . $t['id']) ?>" style="display:flex;gap:6px">
            <select name="gedung" data-select-cari data-placeholder="— pilih gedung —" required>
              <option value="">— pilih gedung —</option>
              <?php foreach ($daftar_gedung as $g): ?><option value="<?= (int) $g['id'] ?>"><?= esc($g['nama']) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="kecil">Render .xlsx</button>
          </form>
        <?php else: ?>
          <a class="tombol kecil" href="<?= base_url('template-laporan/render/' . $t['id']) ?>">Render .xlsx</a>
        <?php endif; ?>
      </td>
      <td style="white-space:nowrap">
        <a class="tombol kecil abu" href="<?= base_url('template-laporan/unduh/' . $t['id']) ?>">Unduh Berkas</a>
        <form class="inline" method="post" action="<?= base_url('template-laporan/hapus/' . $t['id']) ?>" data-konfirmasi="Hapus template ini dari perpustakaan?"><?= csrf_field() ?><button type="submit" class="kecil bahaya">Hapus</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</details>

<?php foreach ($jenis as $kode => $j): ?>
<details class="panel panel-lipat">
  <summary>Katalog Placeholder — <?= esc($j['label']) ?> (konteks: <?= esc($j['konteks']) ?>)<?= $kode === 'spk' && ! empty($contoh['spk']) ? ' · contoh nilai dari SPK nyata pertama' : '' ?></summary>
  <?php foreach ($katalog[$kode] as $grup => $peta): ?>
    <p style="margin:12px 0 4px"><strong><?= esc($grup) ?></strong></p>
    <table>
      <tr><th style="width:300px">Penanda</th><th>Keterangan</th><th style="width:34%">Contoh Nilai</th></tr>
      <?php foreach ($peta as $kunci => $label): ?>
      <tr><td><code>{{<?= esc($kunci) ?>}}</code></td><td><?= esc($label) ?></td><td><?= esc($contoh[$kode][0][$kunci] ?? '—') ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
  <?php foreach ($katalog_berulang[$kode] as $awalan => $peta): ?>
    <p style="margin:12px 0 4px"><strong>Baris Berulang — <?= esc($j['berulang'][$awalan] ?? $awalan) ?></strong> <span class="kecil-teks">(tulis pada SATU baris contoh; baris digandakan per data)</span></p>
    <table>
      <tr><th style="width:300px">Penanda</th><th>Keterangan</th></tr>
      <?php foreach ($peta as $kunci => $label): ?>
      <tr><td><code>{{<?= esc($kunci) ?>}}</code></td><td><?= esc($label) ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
</details>
<?php endforeach; ?>

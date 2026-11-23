<?php /* Partial biayalain/index: daftar kelompok biaya (details per kelompok + tabel baris + aksi). */ ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>  <?php foreach ($kelompok as $namaKelompok => $barisList): ?>
  <details class="panel biaya-data"<?= count($barisList) <= 10 ? ' open' : '' ?>>
    <summary class="tabel-kepala">
      <h2 style="margin:0;font-size:13px;font-weight:600"><?= esc($namaKelompok) ?></h2>
      <span class="petunjuk-field"><?= count($barisList) ?> baris</span>
      <svg class="lipat-panah" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="tabel-envelop">
    <table data-cari="Cari baris biaya…" data-paginasi="25" class="tabel-data">
      <thead>
        <tr>
          <th style="width:34px"><input type="checkbox" title="Pilih semua baris biaya" @change="pilihSemua($event)"></th>
          <th>Uraian</th>
          <th class="angka">Volume</th>
          <th class="angka">Periode</th>
          <th class="angka">Harga Satuan</th>
          <th class="angka">Jumlah</th>
          <th class="angka">Potongan Pajak</th>
          <th class="angka">Bersih Diterima</th>
          <th style="width:110px">Sumber</th>
          <th style="width:140px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $sub = 0; $subPot = 0; foreach ($barisList as $r): $sub += (float) $r['jumlah']; $subPot += (float) $r['potongan']; ?>
      <tr>
        <td><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"></td>
        <td>
          <div class="baris-uraian">
            <b><?= esc($r['uraian']) ?></b><?php if (! empty($r['keterangan'])): ?><br><span class="kecil-teks"><?= esc($r['keterangan']) ?></span><?php endif; ?>
          </div>
        </td>
        <td class="angka"><?= $angka($r['volume']) ?> <span class="kecil-teks" style="color:var(--ta-teks-3)"><?= esc($r['satuan'] ?? '') ?></span></td>
        <td class="angka"><?php if ($r['periode'] !== null): ?><?= $angka($r['periode']) ?> <span class="kecil-teks" style="color:var(--ta-teks-3)"><?= esc($r['satuan_periode'] ?? '') ?></span><?php else: ?>—<?php endif; ?></td>
        <td class="angka"><?= rp($r['harga_satuan']) ?></td>
        <td class="angka"><b><?= rp($r['jumlah']) ?></b></td>
        <td class="angka"><?php if ((float) $r['potongan'] > 0): ?><?= rp($r['potongan']) ?><br><span class="kecil-teks"><?= esc($r['label_pajak']) ?></span><?php else: ?>—<?php endif; ?></td>
        <td class="angka"><?= rp($r['bersih']) ?></td>
        <td><span class="lencana <?= $r['sumber'] === 'impor' ? 'biru' : 'abu' ?>"><?= $r['sumber'] === 'impor' ? 'Impor' : 'Manual' ?></span></td>
        <td>
          <div class="aksi-baris">
            <button type="button" class="tombol kecil ubah-baris" onclick="isiForm(<?= htmlspecialchars(json_encode([
                'id' => (int) $r['id'], 'kelompok' => $r['kelompok'], 'uraian' => $r['uraian'],
                'volume' => $angka($r['volume']), 'satuan' => $r['satuan'] ?? '',
                'periode' => $r['periode'] !== null ? $angka($r['periode']) : '', 'satuan_periode' => $r['satuan_periode'] ?? '',
                'harga' => (string) (int) round((float) $r['harga_satuan']), 'keterangan' => $r['keterangan'] ?? '',
                'pajak' => $r['pajak'] ?? '',
            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>, this)">Ubah</button>
            <form method="post" action="<?= base_url('biaya-non-gedung/hapus/' . $r['id']) ?>" style="display:inline" data-konfirmasi="Hapus baris biaya &quot;<?= esc($r['uraian']) ?>&quot;?">
              <?= csrf_field() ?>
              <button type="submit" class="tombol kecil bahaya">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="subtotal-baris">
        <td colspan="5"><b>Subtotal <?= esc($namaKelompok) ?></b></td>
        <td class="angka"><b><?= rp($sub) ?></b></td>
        <td class="angka"><b><?= rp($subPot) ?></b></td>
        <td class="angka"><b><?= rp($sub - $subPot) ?></b></td>
        <td colspan="2"></td>
      </tr>
      </tbody>
    </table>
    </div>
  </details>
  <?php endforeach; ?>

</div>

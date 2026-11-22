<?php $rp = static fn ($n) => $n === null ? '—' : 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div data-bulk-akar x-data="komponenBulk('form-bulk-nego','ids-nego')">
<div class="panel">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <form id="form-bulk-nego" method="post" action="<?= base_url('negosiasi/hapus-massal') ?>" data-konfirmasi-judul="Hapus Hasil Negosiasi Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-nego"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus hasil negosiasi {n} bahan terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('negosiasi/kosongkan') ?>" data-konfirmasi="Kosongkan seluruh hasil negosiasi sekolah ini? Semua baris penawaran & negosiasi akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Hasil Negosiasi" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Hasil Negosiasi</button>
    </form>
    <span class="kecil-teks">Centang bahan pada tabel di bawah untuk menghapus hasil negosiasinya.</span>
  </div>
</div>
<div class="panel">
  <p>Negosiasi dilakukan dengan penyedia pemilik <strong>Harga Terpilih</strong> (survei termurah). Harga Penawaran terisi awal dari harga terpilih; isi Harga Negosiasi bila ada kesepakatan lebih rendah. Total dan Hemat dihitung dari qty RAB.</p>
  <p class="kecil-teks">Penyedia default: <?= esc($identitas['penyedia_default_nama'] ?: '— (belum diisi di Pengaturan)') ?></p>
  <form method="post" action="<?= base_url('negosiasi/simpan') ?>">
    <?= csrf_field() ?>
    <div style="overflow-x:auto">
    <table data-paginasi="25" data-cari="Cari bahan…">
      <tr><th style="width:34px"><input type="checkbox" title="Pilih semua bahan" @change="pilihSemua($event)"></th><th>Kode</th><th>Nama Bahan</th><th class="angka">Qty RAB</th><th class="angka">Harga Terpilih</th><th class="angka">Harga Penawaran</th><th class="angka">Harga Negosiasi</th><th class="angka">Total Penawaran</th><th class="angka">Total Negosiasi</th><th class="angka">Hemat</th></tr>
      <?php $tPen = 0; $tNeg = 0; foreach ($bahan as $b):
        $qty = (float) $b['qty_total'];
        $pen = $b['penawaran']; $neg = $b['nego'] ?? $pen;
        $totPen = $pen !== null ? $qty * (float) $pen : null;
        $totNeg = $neg !== null ? $qty * (float) $neg : null;
        if ($totPen !== null) $tPen += $totPen;
        if ($totNeg !== null) $tNeg += $totNeg;
      ?>
      <tr>
        <td><input type="checkbox" data-pilih value="<?= (int) $b['id'] ?>" x-model="terpilih"></td>
        <td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td>
        <td class="angka"><?= number_format($qty, 2, ',', '.') ?></td>
        <td class="angka"><?= $rp($b['terpilih']) ?></td>
        <td class="angka"><input type="number" name="penawaran[<?= (int) $b['id'] ?>]" min="0" step="1" value="<?= esc($pen ?? '') ?>" style="width:120px"></td>
        <td class="angka"><input type="number" name="nego[<?= (int) $b['id'] ?>]" min="0" step="1" value="<?= esc($b['nego'] ?? '') ?>" style="width:120px"></td>
        <td class="angka"><?= $rp($totPen) ?></td>
        <td class="angka"><?= $rp($totNeg) ?></td>
        <td class="angka"><?= ($totPen !== null && $totNeg !== null) ? $rp($totPen - $totNeg) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="baris-total"><th colspan="7" style="text-align:right">TOTAL</th><th class="angka"><?= $rp($tPen) ?></th><th class="angka"><?= $rp($tNeg) ?></th><th class="angka"><?= $rp($tPen - $tNeg) ?></th></tr>
    </table>
    </div>
    <div class="aksi md:col-span-2"><button type="submit">Simpan Negosiasi</button></div>
  </form>
</div>
</div>

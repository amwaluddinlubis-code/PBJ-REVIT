<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><a class="tombol" href="<?= base_url('invoice/baru') ?>">+ Invoice Baru</a>
<a class="tombol abu" href="<?= base_url('invoice/jurnal') ?>">Jurnal Pembayaran</a></div>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-invoice','ids-invoice')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-invoice" method="post" action="<?= base_url('invoice/hapus-massal') ?>" data-konfirmasi-judul="Hapus Invoice Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-invoice"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} invoice terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('invoice/kosongkan') ?>" data-konfirmasi="Kosongkan daftar invoice sekolah ini? Semua <?= count($inv) ?> invoice aktif akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Daftar Invoice" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Daftar Invoice</button>
    </form>
  </div>
<table data-paginasi="10" data-cari="Cari invoice…">
  <tr><th style="width:34px"><input type="checkbox" title="Pilih semua invoice" @change="pilihSemua($event)"></th><th>No.</th><th>No. Invoice Toko</th><th>Tanggal</th><th>SPK</th><th>Penyedia</th><th class="angka">Nilai Invoice</th><th class="angka">Total SPK Terkait</th><th>Status</th><th></th></tr>
  <?php foreach ($inv as $i): $beda = (float) $i['nilai'] !== (float) $i['total_spk']; ?>
  <tr<?= $beda ? ' class="negatif"' : '' ?>>
    <td><input type="checkbox" data-pilih value="<?= (int) $i['id'] ?>" x-model="terpilih"></td>
    <td><?= (int) $i['nomor_urut'] ?></td>
    <td><?= esc($i['no_toko'] ?? '—') ?></td>
    <td><?= esc($i['tanggal'] ?? '—') ?></td>
    <td><a href="<?= base_url('spk/detail/' . $i['spk_id']) ?>"><?= (int) $i['spk_nomor'] ?></a></td>
    <td><?= esc($i['penyedia'] ?? '—') ?></td>
    <td class="angka"><?= $rp($i['nilai']) ?></td>
    <td class="angka"><?= $rp($i['total_spk']) ?><?= $beda ? ' ⚠' : '' ?></td>
    <td><?= esc($i['status']) ?></td>
    <td><a href="<?= base_url('invoice/ubah/' . $i['id']) ?>">Ubah</a> · <a href="<?= base_url('invoice/cetak/' . $i['id']) ?>" target="_blank">Cetak</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="kecil-teks">Baris merah + tanda ⚠ berarti nilai invoice tidak sama dengan total SPK terkait — sama seperti pengendali di workbook v3, cek dokumen fisiknya sebelum membayar.</p>
</div>

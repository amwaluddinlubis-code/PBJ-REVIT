<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaInvoiceBaru()">+ Invoice Baru</button>
<a class="tombol abu" href="<?= base_url('invoice/jurnal') ?>">Jurnal Pembayaran</a>
<a class="tombol abu" href="<?= base_url('dokumen') ?>">← Dokumen BAP & BAST</a></div>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-invoice','ids-invoice')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-invoice" method="post" action="<?= base_url('invoice/hapus-massal') ?>" data-konfirmasi-judul="Hapus Invoice Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-invoice"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} invoice terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('invoice/kosongkan') ?>" data-konfirmasi="Kosongkan daftar invoice sekolah ini? Semua <?= count($inv) ?> invoice aktif akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Daftar Invoice" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline;margin-left:auto">
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
    <td><?= esc(tgl($i['tanggal'] ?? null)) ?></td>
    <td><a href="<?= base_url('spk/detail/' . $i['spk_id']) ?>"><?= (int) $i['spk_nomor'] ?></a></td>
    <td><?= esc($i['penyedia'] ?? '—') ?></td>
    <td class="angka"><?= rp($i['nilai']) ?></td>
    <td class="angka"><?= rp($i['total_spk']) ?><?= $beda ? ' ⚠' : '' ?></td>
    <td><span class="lencana <?= $i['status'] === 'Sudah Bayar' ? 'hijau' : 'kuning' ?>"><?= esc($i['status']) ?></span></td>
    <td><a href="#" onclick="bukaInvoiceUbah(<?= (int) $i['id'] ?>); return false;">Ubah</a> · <a href="<?= base_url('invoice/cetak/' . $i['id']) ?>" target="_blank">Cetak</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php
$totNilai = array_sum(array_map(static fn ($x) => (float) $x['nilai'], $inv));
$totLunas = array_sum(array_map(static fn ($x) => $x['status'] === 'Sudah Bayar' ? (float) $x['nilai'] : 0.0, $inv));
?>
<p style="margin:10px 0 2px">Total nilai invoice: <strong><?= rp($totNilai) ?></strong> · Sudah dibayar: <strong><?= rp($totLunas) ?></strong> · Belum dibayar: <strong><?= rp($totNilai - $totLunas) ?></strong></p>
<p class="kecil-teks">Baris merah + tanda ⚠ berarti nilai invoice tidak sama dengan total SPK terkait — periksa dokumen fisiknya sebelum membayar.</p>
</div>
<?= $this->include('invoice/_modal_form') ?>

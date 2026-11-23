<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
  <form method="get" action="<?= base_url('upah/cetak-daftar') ?>" target="_blank" style="display:flex;gap:8px;align-items:end">
    <label>Bulan daftar upah <input type="month" name="bulan" value="<?= date('Y-m') ?>"></label>
    <button type="submit" class="kecil">Cetak Daftar Pembayaran Upah</button>
  </form>
  <span class="kecil-teks">Format dokumen pembayaran upah Juknis — lengkap dengan kolom tanda tangan penerima.</span>
</div>
<div class="panel">
  <h2>Catat Pembayaran Upah</h2>
  <form method="post" action="<?= base_url('upah/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Tanggal<input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label>Gedung
        <select data-select-cari name="gedung_id" id="pilih-gedung">
          <option value="">— umum —</option>
          <?php foreach ($semuaGedung ?? [] as $gd): ?><option value="<?= (int) $gd['id'] ?>"><?= esc($gd['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Pekerjaan (opsional)
        <select data-select-cari name="pekerjaan_id" id="pilih-pekerjaan"><option value="">— tidak tertaut pekerjaan —</option></select>
      </label>
      <label>Jenis tenaga
        <input type="text" name="jenis_tenaga" list="daftar-jenis" required placeholder="mis. Tukang Kayu">
        <datalist id="daftar-jenis">
          <?php foreach ($master as $m): ?><option value="<?= esc($m['jenis']) ?>"><?= esc($m['jenis']) ?> — <?= $rp($m['tarif']) ?>/OH</option><?php endforeach; ?>
        </datalist>
      </label>
      <label>Nama penerima / mandor<input type="text" name="penerima" placeholder="mis. Sarmin (mandor)"></label>
      <label>Orang-hari (OH)<input type="text" name="orang_hari" required placeholder="mis. 12,5"></label>
      <label>Tarif per OH (Rp) — kosongkan untuk tarif master<input type="text" inputmode="numeric" data-uang name="tarif" placeholder="mis. 93578"></label>
      <label>Keterangan<input type="text" name="keterangan" placeholder="mis. Upah pasang pondasi minggu ke-3"></label>
      <div class="aksi md:col-span-2"><button type="submit">Catat Pembayaran</button></div>
    </div>
  </form>
  <p class="kecil-teks">Jumlah bayar = orang-hari × tarif, dihitung otomatis. Pembayaran upah ikut terhitung dalam kontrol pagu total anggaran. PPh 21 mengikuti Juknis Revitalisasi: upah harian sampai dengan Rp450.000 per hari tidak dipotong; di atasnya dipotong 5% dari kelebihan per hari (batas kumulatif Rp4.500.000 dalam 22 hari kerja per bulan untuk penerima yang sama perlu dicek manual).</p>
</div>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-upah','ids-upahbayar')">
  <h2>Riwayat Pembayaran Upah — Total <?= $rp($total) ?> · PPh 21 <?= $rp($total_pph21) ?> · Bersih <?= $rp($total - $total_pph21) ?></h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-upah" method="post" action="<?= base_url('upah/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Upah Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-upahbayar"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan pembayaran upah terpilih? Total bayar dan kontrol pagu akan berubah.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('upah/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat pembayaran upah sekolah ini? Semua <?= count($daftar) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Riwayat Upah" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Riwayat Upah</button>
    </form>
  </div>
  <table data-paginasi="25" data-cari="Cari pembayaran upah…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)"></th><th>Tanggal</th><th>Gedung</th><th>Jenis Tenaga</th><th>Penerima</th><th class="angka">OH</th><th class="angka">Tarif</th><th class="angka">Jumlah</th><th class="angka">PPh 21</th><th class="angka">Bersih</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $u): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $u['id'] ?>" x-model="terpilih"></td>
      <td><?= esc(tgl($u['tanggal'])) ?></td>
      <td><?= esc($u['nama_gedung'] ?? '—') ?><?= $u['nama_pekerjaan'] ? '<br><span class="kecil-teks">' . esc($u['nama_pekerjaan']) . '</span>' : '' ?></td>
      <td><?= esc($u['jenis_tenaga']) ?></td>
      <td><?= esc($u['penerima'] ?? '—') ?></td>
      <td class="angka"><?= number_format((float) $u['orang_hari'], 2, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $u['tarif'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $u['jumlah'], 0, ',', '.') ?></td>
      <td class="angka"><?= (float) $u['pph21'] > 0 ? number_format((float) $u['pph21'], 0, ',', '.') : '—' ?></td>
      <td class="angka"><?= number_format((float) $u['bersih'], 0, ',', '.') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('upah/' . $u['id'] . '/hapus') ?>" data-konfirmasi="Hapus catatan ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($daftar === []): ?><tr><td colspan="11">Belum ada pembayaran upah tercatat.</td></tr><?php endif; ?>
  </table>
</div>

<div class="panel">
  <h2>Master Tarif Upah (per OH)</h2>
  <table data-cari="Cari jenis tenaga…">
    <tr><th>Jenis Tenaga</th><th style="width:300px">Tarif (Rp)</th></tr>
    <?php foreach ($master as $m): ?>
    <tr>
      <td><?= esc($m['jenis']) ?></td>
      <td>
        <form method="post" action="<?= base_url('upah/tarif/' . $m['id']) ?>" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="text" inputmode="numeric" data-uang name="tarif" value="<?= (int) round((float) $m['tarif']) ?>" style="margin:0">
          <button class="kecil" type="submit">Simpan</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <form method="post" action="<?= base_url('upah/master') ?>" style="margin-top:12px">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Jenis tenaga baru<input type="text" name="jenis" placeholder="mis. Tukang Aluminium"></label>
      <label>Tarif per OH (Rp)<input type="text" inputmode="numeric" data-uang name="tarif" placeholder="mis. 93578"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah ke Master</button></div>
    </div>
  </form>
  <p class="kecil-teks">Tarif bawaan berasal dari daftar upah dokumen perencana (berbasis UMK). Tarif Mandor &amp; Kepala Tukang pada salinan dokumen ini tersimpan 0 — silakan isi sesuai daftar upah yang berlaku.</p>
</div>

<script>
const pekerjaanPerGedung = <?= json_encode($pekerjaan_per_gedung) ?>;
const selGedung = document.getElementById('pilih-gedung');
const selPekerjaan = document.getElementById('pilih-pekerjaan');
function segarkanPekerjaan() {
  const daftar = pekerjaanPerGedung[selGedung.value] || [];
  selPekerjaan.innerHTML = '<option value="">— tidak tertaut pekerjaan —</option>' +
    daftar.map(p => `<option value="${p.id}">${p.nama}</option>`).join('');
}
selGedung.addEventListener('change', segarkanPekerjaan);
segarkanPekerjaan();
</script>

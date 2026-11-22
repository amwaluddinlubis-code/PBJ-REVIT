<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2>Penerimaan Dana Bantuan Revitalisasi — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Menurut Juknis Revitalisasi SMP 2026, dana bantuan dicairkan bertahap: <b>Tahap 1 sebesar 70%</b> dan <b>Tahap 2 sebesar 30%</b> setelah kemajuan penyelesaian pekerjaan mencapai minimal 50% (dibuktikan Laporan Kemajuan + kuitansi penerimaan uang). Setiap penerimaan tercatat di sini dan otomatis masuk Buku Kas.</p>
  <table>
    <tr><th>Tahap</th><th class="angka">Target</th><th class="angka">Sudah Diterima</th><th class="angka">Kekurangan</th><th>Status</th></tr>
    <tr>
      <td>Tahap 1 (70%)</td><td class="angka"><?= $rp($target1) ?></td><td class="angka"><?= $rp($terima1) ?></td><td class="angka"><?= $rp(max(0, $target1 - $terima1)) ?></td>
      <td><?= $terima1 >= $target1 && $pagu > 0 ? 'Lengkap' : ($terima1 > 0 ? 'Sebagian' : 'Belum diterima') ?></td>
    </tr>
    <tr>
      <td>Tahap 2 (30%)</td><td class="angka"><?= $rp($target2) ?></td><td class="angka"><?= $rp($terima2) ?></td><td class="angka"><?= $rp(max(0, $target2 - $terima2)) ?></td>
      <td><?= $terima2 >= $target2 && $pagu > 0 ? 'Lengkap' : ($terima2 > 0 ? 'Sebagian' : ($fisik >= 50 ? 'Dapat diajukan — kemajuan fisik ' . number_format($fisik, 1, ',', '.') . '% sudah ≥ 50%' : 'Belum dapat diajukan — kemajuan fisik ' . number_format($fisik, 1, ',', '.') . '% (syarat ≥ 50%)')) ?></td>
    </tr>
    <?php if ($terimaLain > 0): ?><tr><td>Penerimaan lain (tanpa tahap)</td><td class="angka">—</td><td class="angka"><?= $rp($terimaLain) ?></td><td></td><td></td></tr><?php endif; ?>
    <tr><td><b>Total bantuan (pagu)</b></td><td class="angka"><b><?= $rp($pagu) ?></b></td><td class="angka"><b><?= $rp($terima1 + $terima2 + $terimaLain) ?></b></td><td class="angka"><b><?= $rp(max(0, $pagu - $terima1 - $terima2 - $terimaLain)) ?></b></td><td></td></tr>
  </table>
  <?php if ($pagu <= 0): ?><p class="kecil-teks">Pagu (total anggaran) belum diisi — atur di Identitas &amp; Pengaturan agar target per tahap terhitung.</p><?php endif; ?>
</div>

<div class="panel">
  <h2>Catat Penerimaan Dana</h2>
  <form method="post" action="<?= base_url('penerimaan-dana/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
      <label>Tanggal Terima <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label>Tahap
        <select name="tahap"><option value="1">Tahap 1 (70%)</option><option value="2">Tahap 2 (30%)</option></select>
      </label>
      <label>Jumlah Diterima (Rp) <input type="text" name="jumlah" inputmode="numeric" required placeholder="437500000"></label>
      <label>Kanal
        <select name="kanal"><option value="bank">Bank (rekening sekolah)</option><option value="tunai">Tunai</option></select>
      </label>
      <label class="md:col-span-2">Keterangan <input type="text" name="keterangan" placeholder="cth. Transfer dari KPPN, SP2D No. ..."></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Catat Penerimaan</button></div>
  </form>
</div>

<?php if ($daftar !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-terima','ids-terima')">
  <h2>Riwayat Penerimaan</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-terima" method="post" action="<?= base_url('penerimaan-dana/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Penerimaan Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-terima"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan penerimaan terpilih? Catatan itu ikut hilang dari Buku Kas.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('penerimaan-dana/kosongkan') ?>" data-konfirmasi="Kosongkan catatan penerimaan sekolah ini? Semua <?= count($daftar) ?> catatan penerimaan akan dihapus (hapus lunak) dan ikut hilang dari Buku Kas." data-konfirmasi-judul="Kosongkan Catatan Penerimaan" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Catatan Penerimaan</button>
    </form>
  </div>
  <table data-cari="Cari penerimaan…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)"></th><th>Tanggal</th><th>Tahap</th><th class="angka">Jumlah</th><th>Keterangan</th><th></th></tr>
    <?php foreach ($daftar as $d): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $d['id'] ?>" x-model="terpilih"></td>
      <td><?= esc($d['tanggal']) ?></td>
      <td><?= $d['tahap'] ? 'Tahap ' . (int) $d['tahap'] : '—' ?></td>
      <td class="angka"><?= $rp($d['jumlah']) ?></td>
      <td><?= esc($d['keterangan'] ?? '') ?></td>
      <td><a class="tombol kecil abu" href="<?= base_url('penerimaan-dana/kuitansi/' . $d['id']) ?>" target="_blank">Cetak Kuitansi</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

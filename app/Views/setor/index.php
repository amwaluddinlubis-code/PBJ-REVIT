<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $label = ['pph22' => 'PPh Pasal 22', 'ppn' => 'PPN dipungut', 'pph21' => 'PPh Pasal 21', 'pph23' => 'PPh Pasal 23', 'pph42' => 'PPh Pasal 4 ayat 2', 'sisa_dana' => 'Pengembalian sisa dana (SIMPONI)']; ?>
<div class="panel">
  <h2>Kewajiban Pajak per Masa — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Pajak yang dipungut/dipotong dari pembayaran invoice (PPh 22 &amp; PPN), pembayaran upah (PPh 21), dan biaya non-gedung (PPh 23 / PPh 4 ayat 2 / PPh 21 honor) wajib disetor ke kas negara dengan kode e-billing; bukti sahnya adalah NTPN. Tabel ini membandingkan kewajiban dengan bukti setor yang sudah dicatat.</p>
  <table data-cari="Cari pajak / masa…">
    <tr><th>Jenis Pajak</th><th>Masa</th><th class="angka">Kewajiban</th><th class="angka">Sudah Disetor</th><th class="angka">Kekurangan</th><th>Status</th></tr>
    <?php foreach ($baris as $b): ?>
    <tr>
      <td><?= esc($label[$b['jenis']] ?? $b['jenis']) ?></td>
      <td><?= esc($b['masa']) ?></td>
      <td class="angka"><?= $rp($b['wajib']) ?></td>
      <td class="angka"><?= $rp($b['disetor']) ?></td>
      <td class="angka"><?= $rp(max(0, $b['kurang'])) ?></td>
      <td><?= esc($b['status']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($baris === []): ?><tr><td colspan="6">Belum ada kewajiban pajak dari transaksi yang tercatat.</td></tr><?php endif; ?>
  </table>
</div>

<div class="panel">
  <h2>Sisa Dana Bantuan</h2>
  <table>
    <tr><td style="width:300px">Sisa dana (diterima − digunakan)</td><td class="angka"><?= $rp($sisa) ?></td></tr>
    <tr><td>Sudah disetor kembali ke kas negara</td><td class="angka"><?= $rp($setor_sisa) ?></td></tr>
    <tr><td><b>Belum disetor</b></td><td class="angka"><b><?= $rp(max(0, $sisa - $setor_sisa)) ?></b></td></tr>
  </table>
  <p class="kecil-teks">Menurut Juknis, sisa dana bantuan yang tidak digunakan wajib disetorkan kembali ke kas negara melalui SIMPONI dan bukti setoran dilampirkan pada laporan akhir.</p>
</div>

<div class="panel">
  <h2>Catat Bukti Setor</h2>
  <form method="post" action="<?= base_url('bukti-setor/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
      <label>Jenis Setoran
        <select data-select-cari name="jenis">
          <?php foreach ($label as $k => $v): ?><option value="<?= $k ?>"><?= esc($v) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Masa Pajak <input type="month" name="masa"></label>
      <label>Tanggal Setor <input type="date" name="tanggal_setor" value="<?= date('Y-m-d') ?>" required></label>
      <label>Jumlah Disetor (Rp) <input type="text" name="jumlah" inputmode="numeric" required></label>
      <label>Kode Billing <input type="text" name="kode_billing" placeholder="dari e-billing / SIMPONI"></label>
      <label>NTPN <input type="text" name="ntpn" placeholder="Nomor Transaksi Penerimaan Negara"></label>
      <label class="md:col-span-3">Keterangan <input type="text" name="keterangan" placeholder="opsional"></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Catat Bukti Setor</button></div>
  </form>
</div>

<?php if ($setor !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-setor','ids-setor')">
  <h2>Riwayat Bukti Setor</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-setor" method="post" action="<?= base_url('bukti-setor/hapus-massal') ?>" data-konfirmasi-judul="Hapus Bukti Setor Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-setor"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} bukti setor terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('bukti-setor/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat bukti setor sekolah ini? Semua <?= count($setor) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Bukti Setor" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Bukti Setor</button>
    </form>
  </div>
  <table data-paginasi="10" data-cari="Cari bukti setor…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua bukti setor" @change="pilihSemua($event)"></th><th>Tanggal</th><th>Jenis</th><th>Masa</th><th class="angka">Jumlah</th><th>Kode Billing</th><th>NTPN</th><th></th></tr>
    <?php foreach ($setor as $s): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $s['id'] ?>" x-model="terpilih"></td>
      <td><?= esc($s['tanggal_setor']) ?></td>
      <td><?= esc($label[$s['jenis']] ?? $s['jenis']) ?></td>
      <td><?= esc($s['masa'] ?? '—') ?></td>
      <td class="angka"><?= $rp($s['jumlah']) ?></td>
      <td><?= esc($s['kode_billing'] ?? '—') ?></td>
      <td><?= esc($s['ntpn'] ?? '—') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('bukti-setor/hapus/' . $s['id']) ?>" data-konfirmasi="Hapus bukti setor ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

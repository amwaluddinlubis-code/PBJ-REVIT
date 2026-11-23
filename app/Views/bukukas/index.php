<?php $namaBulan = static fn ($ym) => date('F Y', strtotime($ym . '-01')); ?>
<div class="panel">
  <h2><?= esc($nama_buku) ?> — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Pembukuan menurut Juknis Revitalisasi SMP 2026. Isi buku tergabung otomatis dari pembayaran invoice (bank), pembayaran upah (tunai), bukti setor ke kas negara, dan transaksi manual di bawah. Buku ditutup setiap akhir bulan.</p>
  <div class="aksi" style="justify-content:flex-start;gap:8px;flex-wrap:wrap">
    <?php foreach (['bku' => 'Buku Kas Umum', 'bank' => 'Buku Bank', 'tunai' => 'Buku Pembantu Kas Tunai'] as $k => $label): ?>
      <a class="tombol <?= $buku === $k ? '' : 'abu' ?>" href="<?= base_url('buku-kas?buku=' . $k . '&bulan=' . $bulan) ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <form method="get" action="<?= base_url('buku-kas') ?>" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="buku" value="<?= esc($buku) ?>">
      <input type="month" name="bulan" value="<?= esc($bulan) ?>">
      <button type="submit" class="kecil">Tampilkan</button>
    </form>
    <a class="tombol abu" href="<?= base_url('buku-kas/cetak?buku=' . $buku . '&bulan=' . $bulan) ?>" target="_blank">Cetak Bulan Ini</a>
  </div>
</div>

<div class="panel">
  <h2><?= esc($nama_buku) ?> — Bulan <?= esc($namaBulan($bulan)) ?></h2>
  <table data-paginasi="25" data-cari="Cari transaksi…">
    <tr><th>Tanggal</th><th>Uraian</th><th class="angka">Penerimaan</th><th class="angka">Pengeluaran</th><th class="angka">Saldo</th></tr>
    <tr><td colspan="2"><b>Saldo awal bulan</b></td><td></td><td></td><td class="angka"><b><?= rp($saldo_awal) ?></b></td></tr>
    <?php foreach ($baris as $e): ?>
    <tr>
      <td><?= esc(tgl($e['tanggal'] ?? null)) ?></td>
      <td><?= esc($e['uraian']) ?> <span class="kecil-teks">(<?= $e['sumber'] === 'manual' ? 'manual' : ($e['sumber'] === 'invoice' ? 'jurnal pembayaran' : ($e['sumber'] === 'upah' ? 'upah' : 'bukti setor')) ?>)</span></td>
      <td class="angka"><?= $e['masuk'] > 0 ? rp($e['masuk']) : '—' ?></td>
      <td class="angka"><?= $e['keluar'] > 0 ? rp($e['keluar']) : '—' ?></td>
      <td class="angka"><?= rp($e['saldo']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($baris === []): ?><tr><td colspan="5">Tidak ada transaksi pada bulan ini.</td></tr><?php endif; ?>
    <tr><td colspan="2"><b>Saldo akhir bulan (penutupan buku)</b></td><td></td><td></td><td class="angka"><b><?= rp($saldo_akhir) ?></b></td></tr>
  </table>
</div>

<div class="panel">
  <h2>Catat Transaksi Kas Manual</h2>
  <form method="post" action="<?= base_url('buku-kas/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
      <label>Tanggal <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label class="md:col-span-2">Uraian <input type="text" name="uraian" required placeholder="cth. Penerimaan dana bantuan tahap 1"></label>
      <label>Kategori
        <select data-select-cari name="kategori">
          <option value="penerimaan">Penerimaan dana bantuan</option>
          <option value="lainnya">Lainnya</option>
          <option value="pajak">Pajak (di luar bukti setor)</option>
          <option value="sisa_dana">Sisa dana</option>
        </select>
      </label>
      <label>Tahap (untuk penerimaan)
        <select data-select-cari name="tahap"><option value="">—</option><option value="1">Tahap 1 (70%)</option><option value="2">Tahap 2 (30%)</option></select>
      </label>
      <label>Jenis
        <select data-select-cari name="jenis"><option value="masuk">Uang masuk</option><option value="keluar">Uang keluar</option></select>
      </label>
      <label>Kanal
        <select data-select-cari name="kanal"><option value="bank">Bank</option><option value="tunai">Tunai</option></select>
      </label>
      <label>Jumlah (Rp) <input type="text" name="jumlah" inputmode="numeric" data-uang required placeholder="750000000"></label>
      <label>Keterangan <input type="text" name="keterangan" placeholder="opsional"></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Catat Transaksi</button></div>
  </form>
  <p class="kecil-teks">Pembayaran invoice, pembayaran upah, dan penyetoran pajak/sisa dana tidak perlu dicatat di sini — sudah masuk buku secara otomatis dari modulnya masing-masing.</p>
</div>

<?php if ($manual !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-kas','ids-kas')">
  <h2>Transaksi Manual Tercatat</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-kas" method="post" action="<?= base_url('buku-kas/hapus-massal') ?>" data-konfirmasi-judul="Hapus Transaksi Kas Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-kas"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} transaksi kas terpilih dari buku?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('buku-kas/kosongkan') ?>" data-konfirmasi="Kosongkan transaksi kas manual sekolah ini? Semua <?= count($manual) ?> transaksi kas yang dicatat manual (termasuk penerimaan dana) akan dihapus (hapus lunak). Baris buku dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus." data-konfirmasi-judul="Kosongkan Transaksi Kas Manual" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Transaksi Manual</button>
    </form>
  </div>
  <table data-paginasi="10" data-cari="Cari transaksi…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua transaksi" @change="pilihSemua($event)"></th><th>Tanggal</th><th>Uraian</th><th>Kategori</th><th>Kanal</th><th class="angka">Jumlah</th><th></th></tr>
    <?php foreach ($manual as $m): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $m['id'] ?>" x-model="terpilih"></td>
      <td><?= esc(tgl($m['tanggal'])) ?></td>
      <td><?= esc($m['uraian']) ?></td>
      <td><?= esc($m['kategori']) ?><?= $m['tahap'] ? ' · tahap ' . (int) $m['tahap'] : '' ?></td>
      <td><?= esc($m['kanal']) ?> · <?= esc($m['jenis']) ?></td>
      <td class="angka"><?= rp($m['jumlah']) ?></td>
      <td><form class="inline" method="post" action="<?= base_url('buku-kas/hapus/' . $m['id']) ?>" data-konfirmasi="Hapus transaksi kas ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

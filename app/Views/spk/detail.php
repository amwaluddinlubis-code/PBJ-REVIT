<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('spk') ?>">Register SPK</a> <span aria-hidden="true">/</span> <span>SPK Nomor <?= (int) $s['nomor'] ?></span></nav>
<div class="panel">
  <h2>SPK Nomor <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?></h2>
  <div class="kecil-teks">
    Tanggal <?= esc(tgl($s['tanggal'] ?? null)) ?> · Penyedia <?= trim((string) ($s['penyedia'] ?? '')) !== '' ? esc($s['penyedia']) : '<a href="' . base_url('spk/ubah/' . $s['id']) . '">— (belum diisi, lengkapi)</a>' ?><?php if (! empty($s['penerima_manual'])): ?> <span class="lencana abu">penyedia Masyarakat</span><?php endif; ?> (ttd: <?= esc($s['ttd_penyedia'] ?? '—') ?>) ·
    Periode <?= esc(tgl($s['tgl_mulai'] ?? null)) ?> s.d. <?= esc(tgl($s['tgl_selesai'] ?? null)) ?> · Status <?= esc($s['status']) ?> ·
    <a href="<?= base_url('spk/ubah/' . $s['id']) ?>">Ubah kepala SPK</a>
  </div>
</div>
<div class="panel">
  <h2>Ketentuan Juknis Revitalisasi untuk Paket Ini</h2>
  <p class="kecil-teks" style="margin-bottom:0">
    Nilai paket <b><?= $rp($nilai_paket) ?></b> · Bentuk surat pemesanan menurut nilai: <b><?= esc($ketentuan['dokumen']) ?></b> ·
    Metode penetapan penyedia: <b><?= esc($ketentuan['metode']) ?></b> dengan calon penyedia minimal <b><?= (int) $ketentuan['min_penyedia'] ?></b> ·
    Toko pembanding tercatat di Survei Harga untuk bahan paket ini: <b><?= (int) $pembanding ?> toko</b><?php if ($pembanding < (int) $ketentuan['min_penyedia']): ?> — <b style="color:#b91c1c">masih kurang dari minimum menurut Juknis, lengkapi di <a href="<?= base_url('survei') ?>">Survei Harga</a></b><?php endif; ?>.
    <?php if ($nilai_paket <= 50000000): ?>Menurut Juknis, nilai sampai dengan Rp50 juta cukup dengan nota/kuitansi — SPK yang lebih lengkap seperti ini tetap boleh dipakai.<?php endif; ?>
    <?php if ($ketentuan['materai']): ?>Kuitansi pembayaran Rp5 juta ke atas wajib dibubuhi materai Rp10.000.<?php endif; ?>
  </p>
</div>
<div class="panel">
<?php if ($detail === []): ?>
  <p style="margin-top:0"><strong>SPK ini belum mempunyai rincian.</strong> Tambahkan lewat formulir Tambah Rincian di bawah halaman ini, atau buat SPK-nya dari <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a> / <a href="<?= base_url('rencana-mingguan') ?>">Rencana Mingguan</a> agar rinciannya terisi otomatis. Tanpa rincian, BAP belum bisa dibuat dari halaman <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</p>
<?php endif; ?>
<table data-paginasi="10">
  <tr><th>Kode</th><th>Nama Bahan</th><?php if (! empty($gabungan)): ?><th>Gedung Tujuan</th><?php endif; ?><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga</th><th class="angka">Total</th><th class="angka">PPN</th><th>Ubah Qty/Harga</th><th></th></tr>
  <?php $grand = 0; $grandPpn = 0; foreach ($detail as $d): $grand += (float) $d['total']; $grandPpn += (float) $d['ppn']; ?>
  <tr>
    <td><?= esc($d['kode']) ?></td>
    <td><?= esc($d['nama']) ?></td>
    <?php if (! empty($gabungan)): ?><td><?= esc($d['gedung_rincian'] ?? '—') ?></td><?php endif; ?>
    <td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td>
    <td><?= esc($d['satuan'] ?? '') ?></td>
    <td class="angka"><?= $rp($d['harga']) ?></td>
    <td class="angka"><?= $rp($d['total']) ?></td>
    <td class="angka"><?= $d['ppn'] ? $rp($d['ppn']) : '—' ?></td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-ubah/' . $d['id']) ?>">
        <?= csrf_field() ?>
        <input type="number" name="qty" step="0.001" value="<?= esc($d['qty']) ?>" style="width:80px">
        <input type="text" inputmode="numeric" data-uang name="harga" step="1" value="<?= esc($d['harga']) ?>" style="width:110px">
        <label class="kecil-teks"><input type="checkbox" name="pajak" value="1" style="width:auto"<?= $d['pajak'] ? ' checked' : '' ?>> kena PPN</label>
        <button type="submit" class="kecil abu">Simpan</button>
      </form>
    </td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-hapus/' . $d['id']) ?>" data-konfirmasi="Hapus baris ini?">
        <?= csrf_field() ?>
        <button type="submit" class="kecil bahaya">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr class="baris-total"><th colspan="5" style="text-align:right">TOTAL</th><th class="angka"><?= $rp($grand) ?></th><th class="angka"><?= $rp($grandPpn) ?></th><th colspan="2"></th></tr>
</table>
</div>
<div class="panel">
  <h2>Tuntaskan SPK — Satu Panel</h2>
  <?php if ($tuntas['invoice'] && $tuntas['invoice']['status'] === 'Sudah Bayar'): ?>
    <p style="margin-top:0">Rantai SPK ini <strong>sudah selesai</strong>: BAP, BAST, Invoice, dan pembayaran tercatat. Tinggal cetak berkasnya.</p>
  <?php elseif ($tuntas['jml_bap'] === 0 && ! $tuntas['invoice']): ?>
    <form method="post" action="<?= base_url('spk/tuntaskan/' . $s['id']) ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <label>Tanggal terima barang (untuk BAP, BAST &amp; Invoice)<br><input type="date" name="tanggal_terima" value="<?= esc($tuntas['tgl_terima']) ?>" required></label>
        <button class="tombol" type="submit">Buat BAP + BAST + Invoice Sekaligus</button>
      </div>
      <p class="kecil-teks">Satu klik membuat BAP (semua rincian, kondisi Cukup), BAST yang tertaut ke BAP itu, dan Invoice senilai total SPK <?= $rp($nilai_paket) ?> — ketiganya bertanggal terima yang sama, jadi tidak ada lagi tanggal kosong. Tanggal dan kondisi tetap bisa diubah belakangan dari halaman Dokumen.</p>
    </form>
  <?php elseif ($tuntas['invoice']): ?>
    <p style="margin-top:0">Invoice urut <?= (int) $tuntas['invoice']['nomor_urut'] ?> sebesar <strong><?= $rp($tuntas['invoice']['nilai']) ?></strong> menunggu pembayaran.
    <?php if ($tuntas['pajak']): ?>DPP <?= $rp($tuntas['pajak']['dpp']) ?> · PPN <?= $rp($tuntas['pajak']['ppn']) ?> · PPh 22 <?= $rp($tuntas['pajak']['pph22']) ?> · bersih diterima penyedia <strong><?= $rp($tuntas['pajak']['dibayar']) ?></strong>.<?php endif; ?></p>
    <form method="post" action="<?= base_url('spk/bayar-cepat/' . $s['id']) ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <label>Tanggal bayar<br><input type="date" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required></label>
        <button class="tombol" type="submit">Catat Bayar</button>
      </div>
      <p class="kecil-teks">Pembayaran tercatat di Jurnal Pembayaran dan status invoice menjadi Sudah Bayar; kontrol pagu anggaran tetap berlaku.</p>
    </form>
  <?php else: ?>
    <p class="kecil-teks" style="margin-top:0">SPK ini sudah mempunyai BAP/BAST tetapi belum ada invoice — <a href="<?= base_url('invoice/baru?spk=' . $s['id']) ?>">buat invoice-nya sekarang</a> (formulir Register Invoice langsung memilih SPK ini); BAP/BAST dikelola dari halaman <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</p>
  <?php endif; ?>
  <p style="margin-bottom:0"><a class="tombol abu" href="<?= base_url('dokumen/cetak-spj/' . $s['id']) ?>" target="_blank">Cetak SPJ Lengkap (SPK + BAP + BAST + Invoice)</a>
  <?php foreach ($template_spk ?? [] as $tpl): ?>
    <a class="tombol abu" href="<?= base_url('template-laporan/render/' . $tpl['id'] . '?spk=' . $s['id']) ?>" title="Render SPK ini dari Template Laporan (.xlsx)">Unduh dari Template: <?= esc($tpl['nama']) ?></a>
  <?php endforeach; ?></p>
</div>
<div class="panel">
  <h2>Kebutuhan Tenaga Kerja</h2>
  <p class="kecil-teks" style="margin-top:0">Catatan perkiraan tenaga kerja untuk paket ini — jenis tenaga, keahliannya, kebutuhan OH (orang-hari), dan perkiraan jumlah orang yang harus disediakan panitia. Terisi otomatis bila SPK dibuat dari Rencana Belanja; dapat ditambah atau diubah manual di sini, dan ikut tercetak pada SPK.</p>
  <?php if ($tenaga): ?>
  <table data-cari="Cari tenaga…">
    <tr><th>Jenis Tenaga</th><th>Keahlian</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Perkiraan Orang</th><th>Keterangan</th><th></th></tr>
    <?php $totOh = 0; $totOrang = 0; foreach ($tenaga as $t): $totOh += (float) $t['oh']; $totOrang += (int) ($t['jumlah_orang'] ?? 0); ?>
    <tr>
      <td><?= esc($t['jenis']) ?></td>
      <td><?= esc($t['keahlian'] ?? '—') ?></td>
      <td class="angka"><?= number_format((float) $t['oh'], 2, ',', '.') ?></td>
      <td class="angka"><?= $t['jumlah_orang'] === null ? '—' : (int) $t['jumlah_orang'] ?></td>
      <td><?= esc($t['keterangan'] ?? '—') ?></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('spk/tenaga-hapus/' . $t['id']) ?>" data-konfirmasi="Hapus baris kebutuhan tenaga ini?">
          <?= csrf_field() ?>
          <button type="submit" class="kecil bahaya">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr class="baris-total"><th colspan="2" style="text-align:right">TOTAL</th><th class="angka"><?= number_format($totOh, 2, ',', '.') ?> OH</th><th class="angka"><?= $totOrang ?> orang</th><th colspan="2"></th></tr>
  </table>
  <?php endif; ?>
  <form method="post" action="<?= base_url('spk/tenaga-tambah/' . $s['id']) ?>" style="margin-top:10px">
    <?= csrf_field() ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <label>Jenis<br>
        <select name="jenis" style="width:130px"><option value="Pekerja">Pekerja</option><option value="Tukang">Tukang</option></select>
      </label>
      <label>Keahlian (untuk Tukang)<br><input type="text" name="keahlian" placeholder="mis. Batu, Kayu, Besi, Cat" style="width:190px"></label>
      <label>Kebutuhan (OH)<br><input type="text" name="oh" inputmode="decimal" placeholder="0,00" style="width:110px"></label>
      <label>Perkiraan orang<br><input type="number" name="jumlah_orang" min="0" placeholder="—" style="width:110px"></label>
      <label>Keterangan<br><input type="text" name="keterangan" style="width:200px"></label>
      <button class="tombol kecil" type="submit">+ Tambah Baris</button>
    </div>
  </form>
</div>
<div class="panel">
  <h2>Ceklis Kelengkapan Dokumen Bukti Belanja</h2>
  <p class="kecil-teks" style="margin-top:0">Menurut Juknis, pengadaan di luar SIPLah dibuktikan dengan dokumen perencanaan, hasil pembandingan, hasil negosiasi, surat pemesanan, hasil pemeriksaan barang, dan BAST. Status modul terisi otomatis; dokumen fisik ditandai manual.</p>
  <table data-cari="Cari dokumen…">
    <tr><th style="width:60px">Status</th><th>Dokumen</th><th style="width:170px">Penanda</th></tr>
    <?php foreach ($ceklis as $c): ?>
    <tr>
      <td><?= $c['ada'] ? '✓ Ada' : '✗ Belum' ?></td>
      <td><?= esc($c['label']) ?></td>
      <td>
        <?php if ($c['manual']): ?>
        <form class="inline" method="post" action="<?= base_url('spk/ceklis/' . $s['id']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="item" value="<?= esc($c['kunci']) ?>">
          <label style="display:inline-flex;gap:6px;align-items:center"><input type="checkbox" name="ada" value="1" <?= $c['ada'] ? 'checked' : '' ?> onchange="this.form.submit()"> Sudah ada (fisik)</label>
        </form>
        <?php elseif (! empty($c['tautan'])): ?>
        <a class="kecil-teks" href="<?= base_url($c['tautan']) ?>"><?= esc($c['label_tautan'] ?? 'Buka modulnya') ?> →</a>
        <?php else: ?>
        <span class="kecil-teks">otomatis dari modul</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="panel">
  <h2>Tambah Baris Barang</h2>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('spk/detail-tambah/' . $s['id']) ?>">
    <?= csrf_field() ?>
    <label>Bahan
      <select name="bahan_id" id="pilihBahan" required data-select-cari data-placeholder="— pilih bahan —">
        <option value="">— pilih bahan —</option>
        <?php foreach ($bahan as $b): ?>
          <option value="<?= (int) $b['id'] ?>" data-harga="<?= (int) $b['harga_acuan'] ?>"><?= esc($b['kode']) ?> — <?= esc($b['nama']) ?> (<?= esc($b['satuan'] ?? '') ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Qty <input type="number" name="qty" step="0.001" min="0" required></label>
    <label>Harga Satuan (kosongkan = harga acuan) <input type="text" inputmode="numeric" data-uang name="harga" step="1" id="hargaIsi" placeholder="otomatis"></label>
    <label>Kena PPN
      <select data-select-cari name="pajak"><option value="0">Tidak</option><option value="1">Ya</option></select>
    </label>
    <div class="aksi md:col-span-2"><button type="submit">Tambah Baris</button></div>
  </form>
  <p class="kecil-teks">Harga acuan diambil berjenjang: harga negosiasi → harga penawaran → harga RAB.</p>
</div>
<script>
document.getElementById('pilihBahan').addEventListener('change', function () {
  var h = this.options[this.selectedIndex].getAttribute('data-harga');
  document.getElementById('hargaIsi').placeholder = h ? ('acuan: ' + Number(h).toLocaleString('id-ID')) : 'otomatis';
});
</script>

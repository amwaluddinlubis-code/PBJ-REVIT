<?php /* Partial spk/detail: panel Tuntaskan SPK satu panel (BAST, invoice, pembayaran, LPJ). */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-tuntaskan-spk-satu-panel">
  <summary>Tuntaskan SPK — Satu Panel</summary>

  <?php if ($tuntas['invoice'] && $tuntas['invoice']['status'] === 'Sudah Bayar'): ?>
    <p style="margin-top:0">Rantai SPK ini <strong>sudah selesai</strong>: BAP, BAST, Invoice, dan pembayaran tercatat. Tinggal cetak berkasnya.</p>
  <?php elseif ($tuntas['jml_bap'] === 0 && ! $tuntas['invoice']): ?>
    <form method="post" action="<?= base_url('spk/tuntaskan/' . $s['id']) ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <label>Tanggal terima barang (untuk BAP, BAST &amp; Invoice)<br><input type="date" name="tanggal_terima" value="<?= esc($tuntas['tgl_terima']) ?>" required></label>
        <button class="tombol" type="submit">Buat BAP + BAST + Invoice Sekaligus</button>
      </div>
      <p class="kecil-teks">Satu klik membuat BAP (semua rincian, kondisi Cukup), BAST yang tertaut ke BAP itu, dan Invoice senilai total SPK <?= rp($nilai_paket) ?> — ketiganya bertanggal terima yang sama, jadi tidak ada lagi tanggal kosong. Tanggal dan kondisi tetap bisa diubah belakangan dari halaman Dokumen.</p>
    </form>
  <?php elseif ($tuntas['invoice']): ?>
    <p style="margin-top:0">Invoice urut <?= (int) $tuntas['invoice']['nomor_urut'] ?> sebesar <strong><?= rp($tuntas['invoice']['nilai']) ?></strong> menunggu pembayaran.
    <?php if ($tuntas['pajak']): ?>DPP <?= rp($tuntas['pajak']['dpp']) ?> · PPN <?= rp($tuntas['pajak']['ppn']) ?> · PPh 22 <?= rp($tuntas['pajak']['pph22']) ?> · bersih diterima penyedia <strong><?= rp($tuntas['pajak']['dibayar']) ?></strong>.<?php endif; ?></p>
    <form method="post" action="<?= base_url('spk/bayar-cepat/' . $s['id']) ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <label>Tanggal bayar<br><input type="date" name="tanggal_bayar" value="<?= esc($tuntas['tgl_bayar'] ?? date('Y-m-d')) ?>" min="<?= esc($tuntas['buku_min'] ?? '') ?>" max="<?= esc($tuntas['buku_max'] ?? '') ?>" required><span class="kecil-teks" style="display:block">Buku terbuka: <?= esc($tuntas['buku_label'] ?? '') ?></span></label>
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
</details>

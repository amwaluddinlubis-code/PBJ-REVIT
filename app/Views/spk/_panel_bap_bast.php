<?php /* Partial spk/detail: panel BAP & BAST — pengisian lengkap di halaman ini (desain ulang 26 Nov 2026). Buat BAP/BAST memakai endpoint Dokumen dengan balik=spk; Ubah BAP/BAST lewat modal helper form_modal (simpan AJAX ke Dokumen::bapSimpan/bastSimpan). Aturan: satu BAP hanya satu BAST. */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-bap-bast" id="panel-bap-bast" open>
  <summary>BAP, BAST &amp; Invoice — Pemeriksaan, Serah Terima &amp; Penagihan</summary>

  <p class="kecil-teks" style="margin-top:0">BAP dibuat dari rincian SPK; lengkapi tanggal pemeriksaan dan kondisi setiap barang lewat tombol Ubah BAP. <strong>Satu BAP hanya mempunyai satu BAST.</strong> Nomor surat mengikuti pola 422/&lt;JENIS&gt;/&lt;urut&gt;/&lt;tahun&gt;. Pengelolaan yang sama juga tersedia di halaman <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</p>

  <?php if ($detail === []): ?>
  <p><strong>SPK ini belum mempunyai rincian</strong> — BAP belum bisa dibuat. Tambahkan rincian lebih dulu lewat panel Tambah Rincian di halaman ini.</p>
  <?php else: ?>
    <?php if (($jml_terbap ?? 0) < count($detail)): ?>
    <form method="post" action="<?= base_url('dokumen/bap-buat/' . $s['id']) ?>" style="margin-bottom:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="balik" value="spk">
      <button type="submit" class="tombol">+ Buat BAP</button>
      <span class="kecil-teks">Membuat BAP baru dari rincian SPK (<?= (int) ($jml_terbap ?? 0) ?> dari <?= count($detail) ?> rincian sudah ter-BAP). Tanggal &amp; kondisi dilengkapi sesudahnya lewat Ubah BAP.</span>
    </form>
    <?php endif; ?>

    <?php if (($daftar_bap ?? []) === [] && ($bast_umum ?? []) === []): ?>
    <p class="kecil-teks">Belum ada BAP untuk SPK ini. <?php if (($jml_terbap ?? 0) >= count($detail)): ?>Semua rincian sudah ter-BAP.<?php endif; ?></p>
    <?php endif; ?>

    <?php foreach ($daftar_bap ?? [] as $b): $itemsB = $items_per_bap[(int) $b['id']] ?? []; ?>
    <div style="border:1px solid var(--ta-garis);border-radius:10px;padding:12px 14px;margin-bottom:12px">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <strong>BAP urut <?= (int) $b['nomor_urut'] ?></strong>
        <span class="kecil-teks"><?= esc($b['surat']) ?></span>
        <?php if (! empty($b['tanggal'])): ?><span><?= esc(tgl($b['tanggal'])) ?></span><?php else: ?><span style="background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:99px;font-size:11px">tanggal belum diisi</span><?php endif; ?>
        <span class="kecil-teks"><?= count($itemsB) ?> item diperiksa</span>
        <span style="margin-left:auto">
          <button type="button" class="tombol kecil" style="min-width:124px" onclick="bukaModalBap('modal-bap-<?= (int) $b['id'] ?>', <?= esc(json_encode(['id' => (int) $b['id'], 'nomor_urut' => (int) $b['nomor_urut'], 'tanggal' => (string) (! empty($b['tanggal']) ? $b['tanggal'] : ($s['tgl_selesai'] ?? date('Y-m-d')))], JSON_UNESCAPED_UNICODE), 'attr') ?>)"><span class="ikon-tombol" aria-hidden="true"><?= ikonSvg('pensil') ?></span> Ubah BAP</button>
          <a class="tombol kecil abu" style="min-width:124px" href="<?= base_url('dokumen/cetak-bap/' . $b['id']) ?>" target="_blank">Cetak BAP</a>
        </span>
      </div>
      <?php foreach ($bast_per_bap[(int) $b['id']] ?? [] as $bs): ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:10px;padding-top:10px;border-top:1px dashed var(--ta-garis)">
        <span aria-hidden="true">→</span>
        <strong>BAST urut <?= (int) $bs['nomor_urut'] ?></strong>
        <span class="kecil-teks"><?= esc($bs['surat']) ?></span>
        <?php if (! empty($bs['tanggal'])): ?><span><?= esc(tgl($bs['tanggal'])) ?></span><?php else: ?><span style="background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:99px;font-size:11px">tanggal belum diisi</span><?php endif; ?>
        <span style="margin-left:auto">
          <button type="button" class="tombol kecil" style="min-width:124px" onclick="bukaFormModal('modal-bast-<?= (int) $bs['id'] ?>', <?= esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) (! empty($bs['tanggal']) ? $bs['tanggal'] : ($bs['bap_tanggal'] ?? ''))], JSON_UNESCAPED_UNICODE), 'attr') ?>)"><span class="ikon-tombol" aria-hidden="true"><?= ikonSvg('pensil') ?></span> Ubah BAST</button>
          <a class="tombol kecil abu" style="min-width:124px" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank">Cetak BAST</a>
        </span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($bast_per_bap[(int) $b['id']])): ?>
      <form method="post" action="<?= base_url('dokumen/bast-buat/' . $b['id']) ?>" style="margin-top:10px">
        <?= csrf_field() ?>
        <input type="hidden" name="balik" value="spk">
        <button type="submit" class="tombol kecil">+ Buat BAST untuk BAP ini</button>
        <span class="kecil-teks">BAST dibuat tertaut ke BAP urut <?= (int) $b['nomor_urut'] ?>; tanggal serah terima dilengkapi lewat Ubah BAST.</span>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php foreach ($bast_umum ?? [] as $bs): ?>
    <div style="border:1px solid var(--ta-garis);border-radius:10px;padding:12px 14px;margin-bottom:12px">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <strong>BAST urut <?= (int) $bs['nomor_urut'] ?></strong>
        <span class="kecil-teks"><?= esc($bs['surat']) ?> · belum tertaut BAP</span>
        <?php if (! empty($bs['tanggal'])): ?><span><?= esc(tgl($bs['tanggal'])) ?></span><?php else: ?><span style="background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:99px;font-size:11px">tanggal belum diisi</span><?php endif; ?>
        <span style="margin-left:auto">
          <button type="button" class="tombol kecil" style="min-width:124px" onclick="bukaFormModal('modal-bast-<?= (int) $bs['id'] ?>', <?= esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) (! empty($bs['tanggal']) ? $bs['tanggal'] : ($bs['bap_tanggal'] ?? ''))], JSON_UNESCAPED_UNICODE), 'attr') ?>)"><span class="ikon-tombol" aria-hidden="true"><?= ikonSvg('pensil') ?></span> Ubah BAST</button>
          <a class="tombol kecil abu" style="min-width:124px" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank">Cetak BAST</a>
        </span>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php $adaBast = ! empty($bast_per_bap) || ! empty($bast_umum); ?>
  <div style="border:1px solid var(--ta-garis);border-radius:10px;padding:12px 14px;margin-bottom:12px">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <strong>Invoice</strong>
      <?php if (! empty($tuntas['invoice'])): ?>
        <span class="kecil-teks">urut <?= (int) $tuntas['invoice']['nomor_urut'] ?></span>
        <span><?= rp($tuntas['invoice']['nilai']) ?></span>
        <span class="lencana <?= $tuntas['invoice']['status'] === 'Sudah Bayar' ? 'hijau' : 'abu' ?>"><?= esc($tuntas['invoice']['status']) ?></span>
        <span style="margin-left:auto"><a class="tombol kecil abu" style="min-width:124px" href="<?= base_url('invoice') ?>">Lihat Invoice</a></span>
      <?php elseif ($adaBast): ?>
        <span class="kecil-teks">belum dibuat — nilai terisi otomatis sebesar sisa nilai BAST</span>
        <span style="margin-left:auto"><a class="tombol kecil" style="min-width:124px" href="<?= base_url('invoice/baru?spk=' . $s['id']) ?>">+ Buat Invoice</a></span>
      <?php else: ?>
        <span class="kecil-teks">dibuat sesudah BAST terbit</span>
      <?php endif; ?>
    </div>
    <?php if (! empty($tuntas['invoice']) && $tuntas['invoice']['status'] === 'Sudah Bayar'): ?>
      <p class="kecil-teks" style="margin:10px 0 0">Rantai SPK ini <strong>sudah selesai</strong>: BAP, BAST, Invoice, dan pembayaran tercatat. Tinggal cetak berkasnya.</p>
    <?php elseif (! empty($tuntas['invoice'])): ?>
      <p style="margin:10px 0 0">Invoice menunggu pembayaran.<?php if (! empty($tuntas['pajak'])): ?> DPP <?= rp($tuntas['pajak']['dpp']) ?> · PPN <?= rp($tuntas['pajak']['ppn']) ?> · PPh 22 <?= rp($tuntas['pajak']['pph22']) ?> · bersih diterima penyedia <strong><?= rp($tuntas['pajak']['dibayar']) ?></strong>.<?php endif; ?></p>
      <form method="post" action="<?= base_url('spk/bayar-cepat/' . $s['id']) ?>" style="margin-top:8px">
        <?= csrf_field() ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
          <label>Tanggal bayar<br><input type="date" name="tanggal_bayar" value="<?= esc($tuntas['tgl_bayar'] ?? date('Y-m-d')) ?>" min="<?= esc($tuntas['buku_min'] ?? '') ?>" max="<?= esc($tuntas['buku_max'] ?? '') ?>" required><span class="kecil-teks" style="display:block">Buku terbuka: <?= esc($tuntas['buku_label'] ?? '') ?></span></label>
          <button class="tombol" type="submit">Catat Bayar</button>
        </div>
        <p class="kecil-teks">Pembayaran tercatat di Jurnal Pembayaran dan status invoice menjadi Sudah Bayar; kontrol pagu anggaran tetap berlaku.</p>
      </form>
    <?php elseif (($tuntas['jml_bap'] ?? 0) === 0 && $detail !== []): ?>
      <form method="post" action="<?= base_url('spk/tuntaskan/' . $s['id']) ?>" style="margin-top:10px">
        <?= csrf_field() ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
          <label>Tanggal terima barang (untuk BAP, BAST &amp; Invoice)<br><input type="date" name="tanggal_terima" value="<?= esc($tuntas['tgl_terima']) ?>" required></label>
          <button class="tombol" type="submit">Buat BAP + BAST + Invoice Sekaligus</button>
        </div>
        <p class="kecil-teks">Satu klik membuat BAP (semua rincian, kondisi Cukup), BAST yang tertaut ke BAP itu, dan Invoice senilai total SPK <?= rp($nilai_paket) ?> — ketiganya bertanggal terima yang sama. Tanggal dan kondisi tetap bisa diubah belakangan dari halaman Dokumen.</p>
      </form>
    <?php endif; ?>
  </div>

  <p style="margin-bottom:0"><a class="tombol abu" href="<?= base_url('dokumen/cetak-spj/' . $s['id']) ?>" target="_blank">Cetak SPJ Lengkap (SPK + BAP + BAST + Invoice)</a>
  <?php foreach ($template_spk ?? [] as $tpl): ?>
    <a class="tombol abu" href="<?= base_url('template-laporan/render/' . $tpl['id'] . '?spk=' . $s['id']) ?>" title="Render SPK ini dari Template Laporan (.xlsx)">Unduh dari Template: <?= esc($tpl['nama']) ?></a>
  <?php endforeach; ?></p>
</details>

<?php foreach ($daftar_bap ?? [] as $b): $itemsB = $items_per_bap[(int) $b['id']] ?? []; ?>
<?php
$tabelItem = '<div class="bungkus-tabel"><table><tr><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th><th>Kondisi</th><th>Keterangan</th></tr>';
foreach ($itemsB as $it) {
    $selKondisi = '<select data-select-cari name="kondisi[' . (int) $it['bd_id'] . ']" style="width:140px">';
    foreach (['Baik', 'Cukup', 'Kurang', 'Tidak Sesuai'] as $k) {
        $selKondisi .= '<option' . ($it['kondisi'] === $k ? ' selected' : '') . '>' . $k . '</option>';
    }
    $selKondisi .= '</select>';
    $tabelItem .= '<tr><td>' . esc($it['nama']) . '</td>'
        . '<td class="angka">' . number_format((float) $it['qty'], 2, ',', '.') . '</td>'
        . '<td>' . esc($it['satuan'] ?? '') . '</td>'
        . '<td>' . $selKondisi . '</td>'
        . '<td><input name="keterangan[' . (int) $it['bd_id'] . ']" value="' . esc($it['keterangan'] ?? '', 'attr') . '" style="width:100%"></td></tr>';
}
$tabelItem .= '</table></div>';
?>
<?= form_modal([
    'id' => 'modal-bap-' . (int) $b['id'],
    'judul' => 'Ubah BAP',
    'judul_ubah' => 'Ubah BAP urut ' . (int) $b['nomor_urut'] . ' — SPK Nomor ' . (int) $s['nomor'],
    'aksi' => base_url('dokumen/bap-simpan/' . $b['id']),
    'lebar' => 860,
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Nomor surat: <strong>' . esc($b['surat']) . '</strong> · SPK tanggal <strong>' . esc(tgl($s['tanggal'] ?? null)) . '</strong> · periode <strong>' . esc(tgl($s['tgl_mulai'] ?? null)) . ' s.d. ' . esc(tgl($s['tgl_selesai'] ?? null)) . '</strong></p>',
    'kolom' => [
        ['nama' => 'nomor_urut', 'label' => 'Nomor Urut BAP', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'tanggal', 'label' => 'Tanggal Pemeriksaan', 'jenis' => 'tanggal'],
    ],
    'catatan_html' => $tabelItem,
    'tombol' => ['simpan_teks' => 'Simpan BAP', 'hapus' => false],
]) ?>
<?php endforeach; ?>

<?php
$semuaBast = $bast_umum ?? [];
foreach ($bast_per_bap ?? [] as $daftarBs) {
    foreach ($daftarBs as $bs) {
        $semuaBast[] = $bs;
    }
}
?>
<?php foreach ($semuaBast as $bs): ?>
<?= form_modal([
    'id' => 'modal-bast-' . (int) $bs['id'],
    'judul' => 'Ubah BAST',
    'judul_ubah' => 'Ubah BAST urut ' . (int) $bs['nomor_urut'] . ' — SPK Nomor ' . (int) $s['nomor'],
    'aksi' => base_url('dokumen/bast-simpan/' . $bs['id']),
    'lebar' => 560,
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Nomor surat: <strong>' . esc($bs['surat']) . '</strong>' . (! empty($bs['bap_id']) ? ' · BAP urut ' . (int) ($bs['bap_nomor_urut'] ?? 0) . ' tanggal <strong>' . esc(tgl($bs['bap_tanggal'] ?? null, '(tanggal belum diisi)')) . '</strong>' : ' · <em>belum tertaut BAP</em>') . '</p>',
    'kolom' => [
        ['nama' => 'nomor_urut', 'label' => 'Nomor Urut BAST', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'tanggal', 'label' => 'Tanggal Serah Terima', 'jenis' => 'tanggal'],
    ],
    'tombol' => ['simpan_teks' => 'Simpan BAST', 'hapus' => false],
]) ?>
<?php endforeach; ?>

<script>
// Jembatan modal BAP: sesudah form_modal membuka (form.reset mengembalikan
// pilihan kondisi ke nilai tersimpan), sinkronkan ulang widget select-cari.
function bukaModalBap(id, data) {
  window.bukaFormModal(id, data);
  var m = document.getElementById(id);
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
</script>

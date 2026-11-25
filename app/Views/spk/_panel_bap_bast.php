<?php /* Partial spk/detail: panel BAP & BAST — pengisian lengkap di halaman ini (desain ulang 26 Nov 2026). Buat BAP/BAST memakai endpoint Dokumen dengan balik=spk; Ubah BAP/BAST lewat modal helper form_modal (simpan AJAX ke Dokumen::bapSimpan/bastSimpan). Aturan: satu BAP hanya satu BAST. */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-bap-bast" id="panel-bap-bast" open>
  <summary>BAP &amp; BAST — Pemeriksaan dan Serah Terima</summary>

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
          <button type="button" class="tombol kecil" onclick="bukaModalBap('modal-bap-<?= (int) $b['id'] ?>', <?= esc(json_encode(['id' => (int) $b['id'], 'nomor_urut' => (int) $b['nomor_urut'], 'tanggal' => (string) ($b['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') ?>)">Ubah BAP</button>
          <a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-bap/' . $b['id']) ?>" target="_blank">Cetak BAP</a>
        </span>
      </div>
      <?php foreach ($bast_per_bap[(int) $b['id']] ?? [] as $bs): ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:10px;padding-top:10px;border-top:1px dashed var(--ta-garis)">
        <span aria-hidden="true">→</span>
        <strong>BAST urut <?= (int) $bs['nomor_urut'] ?></strong>
        <span class="kecil-teks"><?= esc($bs['surat']) ?></span>
        <?php if (! empty($bs['tanggal'])): ?><span><?= esc(tgl($bs['tanggal'])) ?></span><?php else: ?><span style="background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:99px;font-size:11px">tanggal belum diisi</span><?php endif; ?>
        <span style="margin-left:auto">
          <button type="button" class="tombol kecil" onclick="bukaFormModal('modal-bast-<?= (int) $bs['id'] ?>', <?= esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) ($bs['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') ?>)">Ubah BAST</button>
          <a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank">Cetak BAST</a>
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
          <button type="button" class="tombol kecil" onclick="bukaFormModal('modal-bast-<?= (int) $bs['id'] ?>', <?= esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) ($bs['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') ?>)">Ubah BAST</button>
          <a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank">Cetak BAST</a>
        </span>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
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
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Nomor surat: <strong>' . esc($bs['surat']) . '</strong></p>',
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

<?php
/* Halaman Dokumen (BAP & BAST semua SPK). Form Ubah BAP/BAST memakai
   helper form_modal (simpan AJAX ke Dokumen::bapSimpan/bastSimpan) —
   pola yang sama dengan panel BAP & BAST di Detail SPK. Tombol cetak
   gaya Apple (.tombol-cetak pil berwarna per jenis dokumen); tombol
   Ubah berikon pensil. Aturan: satu BAP hanya satu BAST. */
$ikonCetak = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>';
$ikonPensil = '<span class="ikon-tombol" aria-hidden="true">' . ikonSvg('pensil') . '</span>';
?>
<div class="panel">
  <p>Aturan: BAP dibuat dari rincian SPK dan tombol Buat BAP hilang bila semua rincian sudah ter-BAP; <b>satu BAP hanya mempunyai satu BAST</b>. Form BAP &amp; BAST dibuka sebagai modal lewat tombol Ubah. Nomor surat mengikuti pola 422/&lt;JENIS&gt;/&lt;urut&gt;/&lt;tahun&gt;.</p>
  <p><a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-papan-nama') ?>" target="_blank">Cetak Papan Nama Kegiatan</a> <a class="tombol kecil abu" href="<?= base_url('invoice') ?>">Register Invoice →</a> <span class="kecil-teks">Papan nama kegiatan sesuai format Juknis, dicetak dari identitas sekolah. BAST Aset per gedung tersedia di halaman LPJ Gedung. Sesudah BAST, tagihan dicatat di Register Invoice.</span></p>
  <?php
  // Tabel utama dirender helper tabel_bantu; sel BAP & BAST yang kompleks
  // dibangun sebagai string HTML (tombol Ubah/Cetak + form Buat persis sama).
  $barisDok = [];
  foreach ($spk as $s) {
      $selBapBast = '';
      if ((int) $s['jumlah_detail'] === 0) {
          $selBapBast .= '<span class="kecil-teks" style="white-space:normal;display:inline-block;max-width:340px">SPK belum mempunyai rincian — <a href="' . base_url('spk/detail/' . $s['id']) . '">lengkapi rinciannya di Detail SPK</a> agar bisa dibuatkan BAP.</span>';
      }
      $pisah = false;
      foreach ($bap[$s['id']] ?? [] as $b) {
          $selBapBast .= ($pisah ? ' <span class="kecil-teks">|</span> ' : '')
              . 'BAP urut ' . (int) $b['nomor_urut'] . ' · ' . esc(tgl($b['tanggal'] ?? null, '(tanggal belum diisi)'))
              . ' <button type="button" class="tombol kecil abu" onclick="bukaModalBap(\'modal-bap-' . (int) $b['id'] . '\', ' . esc(json_encode(['id' => (int) $b['id'], 'nomor_urut' => (int) $b['nomor_urut'], 'tanggal' => (string) ($b['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') . ')">' . $ikonPensil . ' Ubah</button>'
              . '<a class="tombol-cetak tc-bap" href="' . base_url('dokumen/cetak-bap/' . $b['id']) . '" target="_blank" title="Cetak BAP">' . $ikonCetak . ' Cetak</a>';
          foreach ($bast_per_bap[$b['id']] ?? [] as $bs) {
              $selBapBast .= ' → BAST urut ' . (int) $bs['nomor_urut'] . ' · ' . esc(tgl($bs['tanggal'] ?? null, '(tanggal belum diisi)'))
                  . ' <button type="button" class="tombol kecil abu" onclick="bukaFormModal(\'modal-bast-' . (int) $bs['id'] . '\', ' . esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) ($bs['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') . ')">' . $ikonPensil . ' Ubah</button>'
                  . '<a class="tombol-cetak tc-bast" href="' . base_url('dokumen/cetak-bast/' . $bs['id']) . '" target="_blank" title="Cetak BAST">' . $ikonCetak . ' Cetak</a>';
          }
          if (empty($bast_per_bap[$b['id']])) {
              $selBapBast .= ' <form class="inline" method="post" action="' . base_url('dokumen/bast-buat/' . $b['id']) . '">' . csrf_field() . '<button class="kecil" type="submit">+ Buat BAST</button></form>';
          }
          $pisah = true;
      }
      foreach ($bast_umum[$s['id']] ?? [] as $bs) {
          $selBapBast .= ($pisah ? ' <span class="kecil-teks">|</span> ' : '')
              . 'BAST urut ' . (int) $bs['nomor_urut'] . ' · ' . esc(tgl($bs['tanggal'] ?? null, '(tanggal belum diisi)')) . ' <span class="kecil-teks">(belum tertaut BAP)</span>'
              . ' <button type="button" class="tombol kecil abu" onclick="bukaFormModal(\'modal-bast-' . (int) $bs['id'] . '\', ' . esc(json_encode(['id' => (int) $bs['id'], 'nomor_urut' => (int) $bs['nomor_urut'], 'tanggal' => (string) ($bs['tanggal'] ?? '')], JSON_UNESCAPED_UNICODE), 'attr') . ')">' . $ikonPensil . ' Ubah</button>'
              . '<a class="tombol-cetak tc-bast" href="' . base_url('dokumen/cetak-bast/' . $bs['id']) . '" target="_blank" title="Cetak BAST">' . $ikonCetak . ' Cetak</a>';
          $pisah = true;
      }
      if ((int) $s['jumlah_terbap'] < (int) $s['jumlah_detail']) {
          $selBapBast .= ($pisah ? ' <span class="kecil-teks">|</span> ' : '')
              . ' <form class="inline" method="post" action="' . base_url('dokumen/bap-buat/' . $s['id']) . '">' . csrf_field() . '<button class="kecil" type="submit">+ Buat BAP</button></form>';
      }
      $barisDok[] = ['sel' => [
          ['html' => '<a href="' . base_url('spk/detail/' . $s['id']) . '">' . (int) $s['nomor'] . '</a>'],
          ['html' => esc($s['paket'] ?? '—') . ' · <span class="kecil-teks">' . esc($s['penyedia'] ?? '—') . '</span>'],
          ['html' => $selBapBast, 'gaya' => 'white-space:nowrap'],
          ['html' => '<a class="tombol-cetak tc-spk" href="' . base_url('dokumen/cetak-spk/' . $s['id']) . '" target="_blank" title="Cetak SPK">' . $ikonCetak . ' Cetak</a>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari SPK / paket / penyedia…',
      'paginasi' => 10,
      'kolom' => [
          ['judul' => 'SPK'],
          ['judul' => 'Paket / Penyedia'],
          ['judul' => 'BAP & BAST'],
          ['judul' => 'Cetak SPK'],
      ],
      'baris' => $barisDok,
  ]);
  ?>
</div>

<?php foreach ($spk as $s): ?>
  <?php foreach ($bap[$s['id']] ?? [] as $b): ?>
  <?php
  $tabelItem = '<div class="bungkus-tabel"><table><tr><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th><th>Kondisi</th><th>Keterangan</th></tr>';
  foreach ($bap_items[$b['id']] ?? [] as $it) {
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
      'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Nomor surat: <strong>' . esc($b['surat'] ?? '') . '</strong> · SPK tanggal <strong>' . esc(tgl($s['tanggal'] ?? null)) . '</strong> · periode <strong>' . esc(tgl($s['tgl_mulai'] ?? null)) . ' s.d. ' . esc(tgl($s['tgl_selesai'] ?? null)) . '</strong></p>',
      'kolom' => [
          ['nama' => 'nomor_urut', 'label' => 'Nomor Urut BAP', 'jenis' => 'angka', 'wajib' => true],
          ['nama' => 'tanggal', 'label' => 'Tanggal Pemeriksaan', 'jenis' => 'tanggal'],
      ],
      'catatan_html' => $tabelItem,
      'tombol' => ['simpan_teks' => 'Simpan BAP', 'hapus' => false],
  ]) ?>
  <?php endforeach; ?>
  <?php
  $daftarBast = $bast_umum[$s['id']] ?? [];
  foreach ($bap[$s['id']] ?? [] as $b) {
      foreach ($bast_per_bap[$b['id']] ?? [] as $bs) {
          $daftarBast[] = $bs;
      }
  }
  ?>
  <?php foreach ($daftarBast as $bs): ?>
  <?php
  $infoBap = ! empty($bs['bap_id'])
      ? ' · BAP urut ' . (int) ($bs['bap_nomor_urut'] ?? 0) . ' tanggal <strong>' . esc(tgl($bs['bap_tanggal'] ?? null, '(tanggal belum diisi)')) . '</strong>'
      : ' · <em>belum tertaut BAP</em>';
  ?>
  <?= form_modal([
      'id' => 'modal-bast-' . (int) $bs['id'],
      'judul' => 'Ubah BAST',
      'judul_ubah' => 'Ubah BAST urut ' . (int) $bs['nomor_urut'] . ' — SPK Nomor ' . (int) $s['nomor'],
      'aksi' => base_url('dokumen/bast-simpan/' . $bs['id']),
      'lebar' => 560,
      'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Nomor surat: <strong>' . esc($bs['surat'] ?? '') . '</strong>' . $infoBap . ' · SPK tanggal <strong>' . esc(tgl($s['tanggal'] ?? null)) . '</strong> · periode <strong>' . esc(tgl($s['tgl_mulai'] ?? null)) . ' s.d. ' . esc(tgl($s['tgl_selesai'] ?? null)) . '</strong></p>',
      'kolom' => [
          ['nama' => 'nomor_urut', 'label' => 'Nomor Urut BAST', 'jenis' => 'angka', 'wajib' => true],
          ['nama' => 'tanggal', 'label' => 'Tanggal Serah Terima', 'jenis' => 'tanggal'],
      ],
      'tombol' => ['simpan_teks' => 'Simpan BAST', 'hapus' => false],
  ]) ?>
  <?php endforeach; ?>
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

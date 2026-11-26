<div class="panel">
  <h2>Berita Acara Revisi Pekerjaan — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Menurut Juknis, setiap perubahan pekerjaan — penambahan maupun pengurangan volume atau biaya — dibuatkan Berita Acara Revisi Pekerjaan yang ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis, dan dikonsultasikan kepada PPK melalui fasilitator. Catat revisinya di sini, lalu cetak berita acaranya.</p>
  <form method="post" action="<?= base_url('revisi-pekerjaan/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Gedung
        <select data-select-cari name="gedung_id" required>
          <option value="">— pilih gedung —</option>
          <?php foreach ($gedung as $g): ?><option value="<?= (int) $g['id'] ?>"><?= esc($g['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Pekerjaan (opsional)
        <select data-select-cari name="pekerjaan_id">
          <option value="">— umum / beberapa pekerjaan —</option>
          <?php foreach ($pekerjaan as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Tanggal Revisi <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label>Ringkasan Perubahan <input type="text" name="perubahan" placeholder="cth. Volume plesteran +12 m2, biaya +Rp1.850.000"></label>
      <label class="md:col-span-2">Uraian Revisi <textarea name="uraian" rows="3" required placeholder="Alasan perubahan, rincian pekerjaan sebelum dan sesudah revisi, serta dampaknya pada biaya dan jadwal"></textarea></label>
      <label>Jenis Revisi
        <select data-select-cari name="jenis">
          <option value="catatan">Catatan saja (tidak mengubah RAB)</option>
          <option value="volume">Ubah volume pekerjaan terpilih</option>
          <option value="ganti">Ganti pekerjaan (mis. jendela aluminium → kayu)</option>
          <option value="baru">Tambah pekerjaan baru</option>
          <option value="hapus">Hapus pekerjaan dari RAB berjalan</option>
        </select>
      </label>
      <label>Volume Baru <input type="text" name="volume_baru" inputmode="decimal" placeholder="volume sesudah revisi / volume pengganti"></label>
      <label>Nama Pekerjaan Baru / Pengganti <input type="text" name="nama_baru" placeholder="cth. Pek. Jendela Kayu Kelas II"></label>
      <label>Satuan Pengganti <input type="text" name="satuan_baru" placeholder="cth. unit, m2, ls"></label>
      <label>Harga Satuan Baru / Pengganti <input type="text" name="harga_baru" inputmode="numeric" data-uang placeholder="Rp per satuan"></label>
      <label>Analisa Pengganti
        <select data-select-cari name="kode_analisa_baru">
          <option value="">— tanpa analisa —</option>
          <?php foreach ($analisa as $a): ?><option value="<?= esc($a['kode']) ?>"><?= esc($a['kode']) ?> — <?= esc($a['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
    </div>
    <p class="kecil-teks" style="margin:8px 0 0">Revisi terstruktur tersimpan sebagai <b>draft</b> dan baru mengubah RAB berjalan setelah disahkan dari daftar di bawah. RAB awal tidak pernah berubah.</p>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Catat Revisi</button></div>
  </form>
</div>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-revisi','ids-revisi')">
  <h2>Riwayat Revisi</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-revisi" method="post" action="<?= base_url('revisi-pekerjaan/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Revisi Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-revisi"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan revisi terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('revisi-pekerjaan/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat revisi sekolah ini? Semua <?= count($revisi) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Riwayat Revisi" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Riwayat Revisi</button>
    </form>
  </div>
  <?php
  // Tabel riwayat memakai helper tabel_bantu(); form Catat Revisi di atas
  // tetap form halaman (form panjang berjenjang, bukan modal).
  $labelJenis = ['catatan' => 'Catatan', 'volume' => 'Ubah volume', 'baru' => 'Pekerjaan baru', 'ganti' => 'Ganti pekerjaan', 'hapus' => 'Hapus pekerjaan'];
  $barisRevisi = [];
  foreach ($revisi as $r) {
      $gedungPek = esc($r['gedung_nama'])
          . ($r['pekerjaan_nama'] ? '<br><span class="kecil-teks">' . esc($r['pekerjaan_nama']) . '</span>' : '')
          . (! empty($r['nama_baru']) ? '<br><span class="kecil-teks">→ ' . esc($r['nama_baru']) . '</span>' : '');
      $lencanaStatus = (($r['status'] ?? 'draft') === 'disahkan')
          ? '<span class="lencana" style="background:var(--ina-green-100);color:var(--ina-green-700)">Disahkan</span>'
          : '<span class="lencana" style="background:var(--ina-yellow-100, #fef9c3);color:var(--ina-yellow-700, #854d0e)">Draft</span>';
      $aksi = '';
      if (($r['status'] ?? 'draft') !== 'disahkan' && ($r['jenis'] ?? 'catatan') !== 'catatan') {
          $aksi .= '<form class="inline" method="post" action="' . base_url('revisi-pekerjaan/sahkan/' . $r['id']) . '" data-konfirmasi="Sahkan revisi ini? RAB berjalan akan diperbarui dan totalnya diperiksa terhadap pagu.">' . csrf_field() . '<button class="kecil" type="submit">Sahkan</button></form>';
      } elseif (($r['status'] ?? 'draft') === 'disahkan') {
          $aksi .= '<form class="inline" method="post" action="' . base_url('revisi-pekerjaan/batal-sah/' . $r['id']) . '" data-konfirmasi="Batalkan pengesahan? RAB berjalan kembali seperti sebelum revisi ini.">' . csrf_field() . '<button class="kecil abu" type="submit">Batalkan Sah</button></form>';
      }
      $aksi .= '<a class="tombol kecil abu" href="' . base_url('revisi-pekerjaan/cetak/' . $r['id']) . '" target="_blank">Cetak BA</a>'
          . '<form class="inline" method="post" action="' . base_url('revisi-pekerjaan/hapus/' . $r['id']) . '" data-konfirmasi="Hapus catatan revisi ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>';
      $barisRevisi[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $r['id'] . '" x-model="terpilih">'],
          tgl($r['tanggal']),
          ['html' => $gedungPek],
          ['html' => esc($labelJenis[$r['jenis'] ?? 'catatan'] ?? 'Catatan') . '<br>' . $lencanaStatus],
          (string) ($r['perubahan'] ?? '—'),
          (string) $r['uraian'],
          ['html' => $aksi, 'gaya' => 'white-space:nowrap'],
      ]];
  }
  if ($revisi === []) {
      $barisRevisi[] = ['sel' => [['teks' => 'Belum ada revisi pekerjaan yang dicatat.', 'colspan' => 6]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari revisi…',
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Gedung / Pekerjaan'],
          ['judul' => 'Jenis & Status'],
          ['judul' => 'Perubahan'],
          ['judul' => 'Uraian'],
          ['judul' => ''],
      ],
      'baris' => $barisRevisi,
  ]);
  ?>
</div>

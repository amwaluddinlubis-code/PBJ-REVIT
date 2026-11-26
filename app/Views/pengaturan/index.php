<?php
$rp = static fn ($n) => number_format((float) $n, 0, ',', '.');
$f = static function (string $k, string $label, string $petunjuk = '') use ($idn): string {
    return '<label>' . $label . ' <input name="' . $k . '" value="' . esc($idn[$k] ?? '') . '">'
        . ($petunjuk !== '' ? '<span class="kecil-teks">' . $petunjuk . '</span>' : '') . '</label>';
};
$anggaran = (float) ($idn['total_anggaran'] ?? 0);
?>
<form method="post" action="<?= base_url('pengaturan/simpan') ?>">
  <?= csrf_field() ?>

  <details class="panel panel-lipat" data-lipat-id="pengaturan-identitas-sekolah" open>
  <summary>Identitas Sekolah</summary>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('nama_sekolah', 'Nama Sekolah') ?>
      <?= $f('npsn', 'NPSN') ?>
      <?= $f('tahun_anggaran', 'Tahun Anggaran') ?>
      <label>Buku Terbuka (Kunci Pencatatan)
        <input value="<?= esc($buku_terbuka['label'] ?? '— semua bulan sudah ditutup —') ?>" readonly>
        <span class="kecil-teks">Buku kas ditutup per bulan Januari–Desember secara berurutan lewat menu <a href="<?= base_url('buku-kas') ?>">Buku Kas</a> (panel Tutup Buku Bulanan). Pencatatan uang — penerimaan dana, pembayaran invoice &amp; upah, transaksi kas manual, bukti setor — hanya diterima bertanggal dalam buku terbuka ini; sesudah tutup buku, pencatatan berikutnya langsung masuk bulan berikutnya.</span>
      </label>
      <?= $f('paket_pekerjaan', 'Paket Pekerjaan', 'mis. Revitalisasi 3 Gedung') ?>
      <?= $f('alamat', 'Alamat') ?>
      <?= $f('desa', 'Desa/Kelurahan') ?>
      <?= $f('kecamatan', 'Kecamatan') ?>
      <?= $f('kabupaten', 'Kabupaten') ?>
      <?= $f('provinsi', 'Provinsi') ?>
      <?= $f('kode_pos', 'Kode Pos') ?>
      <?= $f('email', 'Email Sekolah') ?>
    </div>
  </details>

  <details class="panel panel-lipat" data-lipat-id="pengaturan-pagu-anggaran-kontrol-belanja">
  <summary>Pagu Anggaran (Kontrol Belanja)</summary>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Total Anggaran / Bantuan (Rp)
        <input data-uang name="total_anggaran" value="<?= $anggaran > 0 ? $rp($anggaran) : '' ?>" placeholder="mis. 2.540.292.000">
        <span class="kecil-teks">Terisi otomatis dari Rekap RAB saat impor dokumen perencana. Isi manual bila sekolah tidak memakai impor.</span>
      </label>
      <label>Belanja terbayar saat ini (terkunci)
        <input value="Rp <?= $rp($terbayar) ?> — sisa pagu <?= $anggaran > 0 ? 'Rp ' . $rp($anggaran - $terbayar) : 'pagu belum diatur' ?>" disabled>
        <span class="kecil-teks">Pembayaran invoice &amp; upah yang menembus pagu akan ditahan aplikasi.</span>
      </label>
    </div>
  </details>

  <details class="panel panel-lipat" data-lipat-id="pengaturan-kop-surat-dokumen">
  <summary>Kop Surat Dokumen</summary>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('kop_pemerintah', 'Kop Baris 1 (Pemerintah)', 'mis. PEMERINTAH KABUPATEN MANDAILING NATAL') ?>
      <?= $f('kop_dinas', 'Kop Baris 2 (Dinas)', 'mis. DINAS PENDIDIKAN') ?>
      <?= $f('alamat_kop', 'Alamat pada Kop', 'Kosongkan untuk memakai alamat sekolah di atas secara otomatis.') ?>
    </div>
  </details>

  <details class="panel panel-lipat" data-lipat-id="pengaturan-panitia-penandatangan">
  <summary>Panitia &amp; Penandatangan</summary>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('kepsek_nama', 'Kepala Sekolah') ?>
      <?= $f('kepsek_nip', 'NIP Kepala Sekolah') ?>
      <?= $f('ketua_nama', 'Ketua Panitia P2SP') ?>
      <?= $f('sekretaris_nama', 'Sekretaris / Logistik') ?>
      <?= $f('pelaksana_nama', 'Kepala Pelaksana') ?>
    </div>
  </details>

  <div class="panel">
    <details>
      <summary style="cursor:pointer;font-weight:600">Pengaturan Lanjutan — jarang diubah (tim pemeriksa, toko survei, penyedia bawaan)</summary>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" style="margin-top:14px">
        <?= $f('pemeriksa1', 'Anggota Pemeriksa 1 (BAP)') ?>
        <?= $f('pemeriksa2', 'Anggota Pemeriksa 2 (BAP)') ?>
        <?= $f('pemeriksa3', 'Anggota Pemeriksa 3 (BAP)') ?>
        <label>Tanggal Survei Terakhir <input type="date" name="tanggal_survei" value="<?= esc($idn['tanggal_survei'] ?? '') ?>"></label>
        <?= $f('toko1', 'Nama Toko Survei 1') ?>
        <?= $f('toko2', 'Nama Toko Survei 2') ?>
        <?= $f('toko3', 'Nama Toko Survei 3') ?>
        <?= $f('toko4', 'Nama Toko Survei 4') ?>
        <?= $f('penyedia_default_nama', 'Penyedia Default (Negosiasi)') ?>
        <?= $f('penyedia_default_alamat', 'Alamat Penyedia Default') ?>
      </div>
    </details>
    <div class="aksi"><button type="submit">Simpan Semua Pengaturan</button></div>
  </div>
</form>

<?php if (($pengguna['peran'] ?? '') === 'admin'): ?>
<details class="panel panel-lipat login-version-settings" data-lipat-id="pengaturan-halaman-login">
  <summary>Halaman Login</summary>
  <div class="login-version-heading">
    <div>
      <span class="kecil-teks">Khusus administrator</span>

      <p class="kecil-teks">Pilih tampilan yang digunakan semua pengguna saat masuk. Alur login tetap sama.</p>
    </div>
    <span class="login-version-active">Aktif: <?= strtoupper(esc($loginVersion ?? 'v1')) ?></span>
  </div>
  <form method="post" action="<?= base_url('pengaturan/login-tampilan') ?>" class="login-version-options">
    <?= csrf_field() ?>
    <label class="login-version-option"><input type="radio" name="login_version" value="v1" <?= ($loginVersion ?? 'v1') === 'v1' ? 'checked' : '' ?>><span><b>Versi 1 — Portal lengkap</b><small>Tampilan login yang saat ini digunakan dengan panel informasi fitur di sisi kanan.</small></span></label>
    <label class="login-version-option"><input type="radio" name="login_version" value="v2" <?= ($loginVersion ?? 'v1') === 'v2' ? 'checked' : '' ?>><span><b>Versi 2 — Fokus kerja</b><small>Tampilan lebih ringkas dengan fokus pada identitas sekolah, keamanan, dan akses masuk.</small></span></label>
    <div class="aksi"><button type="submit">Simpan Tampilan Login</button></div>
  </form>
</details>
<?php endif; ?>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-penyedia','ids-penyedia')">
  <h2>Master Penyedia</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <button type="button" class="tombol" onclick="bukaFormModal('modal-penyedia')">+ Tambah Penyedia</button>
    <form id="form-bulk-penyedia" method="post" action="<?= base_url('pengaturan/penyedia-hapus-massal') ?>" data-konfirmasi-judul="Hapus Penyedia Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-penyedia"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} penyedia terpilih? Penyedia yang dipakai SPK akan dilewati.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('pengaturan/penyedia-kosongkan') ?>" data-konfirmasi="Kosongkan master penyedia sekolah ini? Semua penyedia aktif akan dihapus (hapus lunak), kecuali yang dipakai SPK." data-konfirmasi-judul="Kosongkan Master Penyedia" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Master Penyedia</button>
    </form>
  </div>
<?php
$ikonPensil = '<span class="ikon-tombol" aria-hidden="true">' . ikonSvg('pensil') . '</span>';
$barisPenyedia = [];
foreach ($penyedia as $p) {
    $barisPenyedia[] = [
        'sel' => [
            ['html' => '<input type="checkbox" data-pilih value="' . (int) $p['id'] . '" x-model="terpilih">'],
            (string) $p['nama'],
            (string) ($p['alamat'] ?? ''),
            (string) ($p['ttd_nama'] ?? ''),
            ['html' => '<button type="button" class="tombol kecil abu" data-id="' . (int) $p['id'] . '" data-nama="' . esc($p['nama'], 'attr') . '" data-alamat="' . esc($p['alamat'] ?? '', 'attr') . '" data-ttd="' . esc($p['ttd_nama'] ?? '', 'attr') . '" data-rekening="' . esc($p['no_rekening'] ?? '', 'attr') . '" data-bank="' . esc($p['nama_bank'] ?? '', 'attr') . '" onclick="bukaPenyediaUbah(this)">' . $ikonPensil . ' Ubah</button> <form class="inline" method="post" action="' . base_url('pengaturan/penyedia-hapus/' . $p['id']) . '" data-konfirmasi="Hapus penyedia ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>', 'gaya' => 'white-space:nowrap'],
        ],
    ];
}
echo tabel_bantu([
    'cari' => 'Cari penyedia…',
    'paginasi' => false,
    'kolom' => [
        ['judul_html' => '<input type="checkbox" title="Pilih semua penyedia" @change="pilihSemua($event)">', 'lebar' => '34px'],
        ['judul' => 'Nama'],
        ['judul' => 'Alamat'],
        ['judul' => 'Penandatangan'],
        ['judul' => ''],
    ],
    'baris' => $barisPenyedia,
]);
?>
</div>
<?= form_modal([
    'id' => 'modal-penyedia',
    'judul' => 'Tambah Penyedia',
    'judul_ubah' => 'Ubah Penyedia',
    'aksi' => base_url('pengaturan/penyedia-tambah'),
    'lebar' => 560,
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama Penyedia', 'jenis' => 'teks', 'wajib' => true],
        ['nama' => 'alamat', 'label' => 'Alamat', 'jenis' => 'teks'],
        ['nama' => 'ttd_nama', 'label' => 'Penandatangan', 'jenis' => 'teks'],
        ['nama' => 'no_rekening', 'label' => 'Nomor Rekening', 'jenis' => 'teks', 'petunjuk' => 'Wajib untuk pembayaran transfer bank (jurnal pembayaran menolak penyedia tanpa nomor rekening).'],
        ['nama' => 'nama_bank', 'label' => 'Nama Bank', 'jenis' => 'teks'],
    ],
    'tombol' => ['simpan_teks' => 'Tambah Penyedia', 'simpan_teks_ubah' => 'Simpan Perubahan'],
]) ?>
<script>
// Jembatan modal Penyedia: hanya mengoper data baris (atribut data-*
// tombol Ubah) ke bukaFormModal.
function bukaPenyediaUbah(btn) {
  window.bukaFormModal('modal-penyedia', {
    id: btn.dataset.id,
    nama: btn.dataset.nama || '',
    alamat: btn.dataset.alamat || '',
    ttd_nama: btn.dataset.ttd || '',
    no_rekening: btn.dataset.rekening || '',
    nama_bank: btn.dataset.bank || ''
  });
}
</script>

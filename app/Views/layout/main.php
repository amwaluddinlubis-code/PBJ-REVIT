<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul ?? 'Analisa Perencana') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/idds.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= file_exists(FCPATH . 'assets/app.css') ? filemtime(FCPATH . 'assets/app.css') : 0 ?>">
<script>
try {
  if (localStorage.getItem('ap-tema') === 'gelap') document.documentElement.classList.add('gelap');
  var _z = parseFloat(localStorage.getItem('ap-zoom') || '1');
  if (_z && _z !== 1) document.documentElement.style.zoom = _z;
} catch (e) {}
</script>
</head>
<body>
<?php
$seg = explode('/', uri_string())[0] ?? '';
$aktif = static fn (string $s): string => $seg === $s ? ' class="aktif"' : '';
$ikon = [
  'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
  'impor'  => '<path d="M12 16V4m0 0l-4 4m4-4l4 4"/><path d="M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
  'gedung' => '<path d="M4 21V8l8-5 8 5v13"/><path d="M2 21h20M9 21v-6h6v6"/>',
  'kotak'  => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
  'spk'    => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>',
  'survei' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
  'nego'   => '<path d="M8 7h13M8 12h13M8 17h13"/><circle cx="4" cy="7" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="17" r="1"/>',
  'dok'    => '<path d="M9 12h6M9 16h4"/><path d="M14 3H7v18h10V7l-3-4z"/><path d="M14 3v4h3"/>',
  'inv'    => '<path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z"/><path d="M9 8h6M9 12h6"/>',
  'jurnal' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5v14z"/><path d="M4 19.5A2.5 2.5 0 006.5 22H20v-5"/>',
  'upah'   => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.2 3.6-5 7-5s6.2 1.8 7 5"/>',
  'mat'    => '<path d="M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3z"/><path d="M4 7v10c0 1.7 3.6 3 8 3s8-1.3 8-3V7"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
  'sisa'   => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v3M12 18.2v3M2.8 12h3M18.2 12h3M5.5 5.5l2.1 2.1M16.4 16.4l2.1 2.1M18.5 5.5l-2.1 2.1M7.6 16.4l-2.1 2.1"/>',
  'rencana'=> '<circle cx="9" cy="20" r="1.6"/><circle cx="17" cy="20" r="1.6"/><path d="M3 3h2l2.4 12h10.4l2.2-9H6"/>',
  'lpj'    => '<path d="M4 19V5m0 14h16"/><path d="M7 15c3-1 4-6 7-7 2-.7 3 1 5 0"/>',
  'gear'   => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06A1.7 1.7 0 0015 19.4a1.7 1.7 0 00-1 1.55V21a2 2 0 11-4 0v-.09A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.55-1H3a2 2 0 110-4h.09A1.7 1.7 0 004.6 9a1.7 1.7 0 001-1.87l-.06-.06a2 2 0 112.83-2.83l.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001-1.55V3a2 2 0 114 0v.09c0 .68.4 1.3 1 1.51.61.22 1.3.1 1.9-.34l.06-.06a2 2 0 112.83 2.83l-.06.06a1.7 1.7 0 00-.34 1.9V9c.24.6.86 1 1.51 1H21a2 2 0 110 4h-.09c-.65 0-1.27.4-1.51 1z" stroke-linejoin="round"/>',
  'sekolah'=> '<path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-5h6v5M12 4V2.5"/>',
  'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  'keluar' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
  'bulan'  => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
  'matahari'=> '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  'panah'  => '<path d="m6 9 6 6 6-6"/>',
];
$svg = static fn (string $k): string => '<svg viewBox="0 0 24 24">' . ($ikon[$k] ?? '') . '</svg>';
?>
<div class="kerangka">
  <div class="lapik" id="lapik-samping"></div>
  <aside class="samping" id="samping">
    <div class="logo">
      <div class="baris-ap"><div class="kotak-logo">AP</div><div class="nama-logo">Analisa Perencana<small>Perencanaan · Belanja · LPJ</small></div></div>
      <form method="post" action="<?= base_url('sekolah/pilih') ?>">
        <?= csrf_field() ?>
        <label class="label-samping" for="pilihan-sekolah">Sekolah aktif</label>
        <select data-select-cari id="pilihan-sekolah" name="sekolah_id" onchange="this.form.submit()">
          <?php foreach ($semuaSekolah ?? [] as $sk): ?>
            <option value="<?= (int) $sk['id'] ?>"<?= ((int) ($sekolahAktif['id'] ?? 0) === (int) $sk['id']) ? ' selected' : '' ?>><?= esc($sk['nama_sekolah'] ?? ('Sekolah #' . $sk['id'])) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <nav>
      <a href="<?= base_url('/') ?>"<?= $seg === '' ? ' class="aktif"' : '' ?> title="Dashboard"><?= $svg('grid') ?><span class="judul-induk">Dashboard</span></a>
      <?php
      $uriPenuh = uri_string();
      $indukAktif = [
        'perencanaan' => in_array($seg, ['impor', 'analisa', 'acuan', 'validasi-harga', 'bersih', 'rencana-belanja', 'rencana-mingguan', 'biaya-non-gedung'], true),
        'gedung'      => in_array($seg, ['gedung', 'foto-progres', 'kurva-s', 'laporan-kemajuan', 'revisi-pekerjaan'], true),
        'belanja'     => in_array($seg, ['spk', 'belum-bernomor', 'survei', 'negosiasi', 'dokumen'], true),
        'material'    => in_array($seg, ['bahan', 'material', 'sisa-bahan'], true),
        'keuangan'    => in_array($seg, ['invoice', 'buku-kas', 'penerimaan-dana', 'bukti-setor', 'upah'], true),
        'laporan'     => in_array($seg, ['lpj', 'laporan'], true),
        'pengaturan'  => in_array($seg, ['pengaturan', 'sekolah', 'pengguna', 'profil', 'pemeliharaan-db', 'audit'], true),
      ];
      $bolehMenu = static fn (string $k): bool => ($aksesMenu[$k] ?? true) !== false;
      $grupAda = [
        'perencanaan' => $bolehMenu('impor') || $bolehMenu('analisa') || $bolehMenu('acuan') || $bolehMenu('validasi-harga') || $bolehMenu('bersih') || $bolehMenu('rencana-belanja') || $bolehMenu('rencana-mingguan') || $bolehMenu('biaya-non-gedung'),
        'gedung'      => $bolehMenu('gedung') || $bolehMenu('foto-progres') || $bolehMenu('kurva-s') || $bolehMenu('laporan-kemajuan') || $bolehMenu('revisi-pekerjaan'),
        'belanja'     => $bolehMenu('spk') || $bolehMenu('belum-bernomor') || $bolehMenu('survei') || $bolehMenu('negosiasi') || $bolehMenu('dokumen'),
        'material'    => $bolehMenu('bahan') || $bolehMenu('material') || $bolehMenu('sisa-bahan'),
        'keuangan'    => $bolehMenu('penerimaan-dana') || $bolehMenu('invoice') || $bolehMenu('buku-kas') || $bolehMenu('bukti-setor') || $bolehMenu('upah'),
        'laporan'     => $bolehMenu('lpj') || $bolehMenu('laporan'),
        'pengaturan'  => $bolehMenu('pengaturan') || $bolehMenu('sekolah') || (($pengguna['peran'] ?? '') === 'admin'),
      ];
      $induk = static function (string $kunci, string $ikonNama, string $label) use ($indukAktif, $svg): void {
          $buka = $indukAktif[$kunci] ?? false;
          echo '<div class="induk' . ($buka ? ' buka' : '') . '" data-induk="' . $kunci . '">'
             . '<button type="button" class="parent' . ($buka ? ' aktif' : '') . '" title="' . esc($label) . '">' . $svg($ikonNama) . '<span class="judul-induk">' . $label . '</span>'
             . '<svg class="panah" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button><div class="anak">';
      };
      $tutupInduk = static fn (): string => '</div></div>';
      ?>
      <?php if ($grupAda['perencanaan']): ?>
      <?php $induk('perencanaan', 'rencana', 'Perencanaan'); ?>
        <?php if ($bolehMenu('impor')): ?><a href="<?= base_url('impor') ?>"<?= $aktif('impor') ?>>Impor Dokumen Perencana</a><?php endif; ?>
        <?php if ($bolehMenu('analisa')): ?><a href="<?= base_url('analisa') ?>"<?= $aktif('analisa') ?>>Analisa Pekerjaan</a><?php endif; ?>
        <?php if ($bolehMenu('acuan')): ?><a href="<?= base_url('acuan') ?>"<?= $aktif('acuan') ?>>Acuan AHSP Nasional</a><?php endif; ?>
        <?php if ($bolehMenu('validasi-harga')): ?><a href="<?= base_url('validasi-harga') ?>"<?= $aktif('validasi-harga') ?>>Validasi Harga Satuan</a><?php endif; ?>
        <?php if ($bolehMenu('bersih')): ?><a href="<?= base_url('bersih') ?>"<?= $aktif('bersih') ?>>Pembersihan Hasil Impor</a><?php endif; ?>
        <?php if ($bolehMenu('rencana-belanja')): ?><a href="<?= base_url('rencana-belanja') ?>"<?= $aktif('rencana-belanja') ?>>Rencana Belanja</a><?php endif; ?>
        <?php if ($bolehMenu('rencana-mingguan')): ?><a href="<?= base_url('rencana-mingguan') ?>"<?= $aktif('rencana-mingguan') ?>>Rencana Mingguan</a><?php endif; ?>
        <?php if ($bolehMenu('biaya-non-gedung')): ?><a href="<?= base_url('biaya-non-gedung') ?>"<?= $aktif('biaya-non-gedung') ?>>Biaya Non-Gedung</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['gedung']): ?>
      <?php $induk('gedung', 'gedung', 'Pembangunan Gedung'); ?>
        <?php if ($bolehMenu('gedung')): ?><a href="<?= base_url('gedung') ?>"<?= $aktif('gedung') ?>>Kelola Gedung</a><?php endif; ?>
        <?php if ($bolehMenu('gedung')): ?>
        <?php foreach ($semuaGedung ?? [] as $g): ?>
          <a href="<?= base_url('gedung/' . $g['id']) ?>"<?= ($seg === 'gedung' && (explode('/', $uriPenuh)[1] ?? '') === (string) $g['id']) ? ' class="aktif"' : '' ?>><?= esc($g['nama']) ?><?php if (isset($g['persen_fisik'])): ?><span class="hitung"><?= (int) round((float) $g['persen_fisik']) ?>%</span><?php endif; ?></a>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($bolehMenu('foto-progres')): ?><a href="<?= base_url('foto-progres') ?>"<?= $aktif('foto-progres') ?>>Foto Progres</a><?php endif; ?>
        <?php if ($bolehMenu('kurva-s')): ?><a href="<?= base_url('kurva-s') ?>"<?= $aktif('kurva-s') ?>>Kurva S</a><?php endif; ?>
        <?php if ($bolehMenu('laporan-kemajuan')): ?><a href="<?= base_url('laporan-kemajuan') ?>"<?= $aktif('laporan-kemajuan') ?>>Laporan Kemajuan</a><?php endif; ?>
        <?php if ($bolehMenu('revisi-pekerjaan')): ?><a href="<?= base_url('revisi-pekerjaan') ?>"<?= $aktif('revisi-pekerjaan') ?>>BA Revisi Pekerjaan</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['belanja']): ?>
      <?php $induk('belanja', 'spk', 'Belanja'); ?>
        <?php if ($bolehMenu('spk')): ?><a href="<?= base_url('spk') ?>"<?= $aktif('spk') ?>>Register SPK</a><?php endif; ?>
        <?php if ($bolehMenu('belum-bernomor')): ?><a href="<?= base_url('belum-bernomor') ?>"<?= $aktif('belum-bernomor') ?>>Belum Bernomor SPK</a><?php endif; ?>
        <?php if ($bolehMenu('survei')): ?><a href="<?= base_url('survei') ?>"<?= $aktif('survei') ?>>Survei Harga</a><?php endif; ?>
        <?php if ($bolehMenu('negosiasi')): ?><a href="<?= base_url('negosiasi') ?>"<?= $aktif('negosiasi') ?>>Negosiasi</a><?php endif; ?>
        <?php if ($bolehMenu('dokumen')): ?><a href="<?= base_url('dokumen') ?>"<?= $aktif('dokumen') ?>>BAP &amp; BAST / Cetak</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['material']): ?>
      <?php $induk('material', 'kotak', 'Material'); ?>
        <?php if ($bolehMenu('bahan')): ?><a href="<?= base_url('bahan') ?>"<?= $aktif('bahan') ?>>Master Bahan &amp; Upah</a><?php endif; ?>
        <?php if ($bolehMenu('material')): ?><a href="<?= base_url('material') ?>"<?= $aktif('material') ?>>Buku Material</a><?php endif; ?>
        <?php if ($bolehMenu('sisa-bahan')): ?><a href="<?= base_url('sisa-bahan') ?>"<?= $aktif('sisa-bahan') ?>>Sisa Bahan</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['keuangan']): ?>
      <?php $induk('keuangan', 'jurnal', 'Keuangan'); ?>
        <?php if ($bolehMenu('penerimaan-dana')): ?><a href="<?= base_url('penerimaan-dana') ?>"<?= $aktif('penerimaan-dana') ?>>Penerimaan Dana</a><?php endif; ?>
        <?php if ($bolehMenu('invoice')): ?><a href="<?= base_url('invoice') ?>"<?= $uriPenuh === 'invoice' ? ' class="aktif"' : '' ?>>Invoice</a><?php endif; ?>
        <?php if ($bolehMenu('invoice')): ?><a href="<?= base_url('invoice/jurnal') ?>"<?= $uriPenuh === 'invoice/jurnal' ? ' class="aktif"' : '' ?>>Jurnal Pembayaran</a><?php endif; ?>
        <?php if ($bolehMenu('buku-kas')): ?><a href="<?= base_url('buku-kas') ?>"<?= $aktif('buku-kas') ?>>Buku Kas (BKU)</a><?php endif; ?>
        <?php if ($bolehMenu('bukti-setor')): ?><a href="<?= base_url('bukti-setor') ?>"<?= $aktif('bukti-setor') ?>>Bukti Setor &amp; Pajak</a><?php endif; ?>
        <?php if ($bolehMenu('upah')): ?><a href="<?= base_url('upah') ?>"<?= $aktif('upah') ?>>Pembayaran Upah</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['laporan']): ?>
      <?php $induk('laporan', 'lpj', 'Laporan'); ?>
        <?php if ($bolehMenu('lpj')): ?><a href="<?= base_url('lpj') ?>"<?= $aktif('lpj') ?>>LPJ &amp; Realisasi</a><?php endif; ?>
        <?php if ($bolehMenu('lpj')): ?><a href="<?= base_url('lpj/rab-gabungan') ?>"<?= $aktif('lpj') ?>>RAB Gabungan</a><?php endif; ?>
        <?php if ($bolehMenu('laporan')): ?><a href="<?= base_url('laporan') ?>"<?= $aktif('laporan') ?>>Laporan &amp; Ekspor</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['pengaturan']): ?>
      <?php $induk('pengaturan', 'gear', 'Pengaturan'); ?>
        <?php if ($bolehMenu('pengaturan')): ?><a href="<?= base_url('pengaturan') ?>"<?= $aktif('pengaturan') ?>>Identitas &amp; Pengaturan</a><?php endif; ?>
        <?php if ($bolehMenu('sekolah')): ?><a href="<?= base_url('sekolah') ?>"<?= $aktif('sekolah') ?>>Kelola Sekolah</a><?php endif; ?>
        <?php if (($pengguna['peran'] ?? '') === 'admin'): ?>
        <a href="<?= base_url('pengguna') ?>"<?= $aktif('pengguna') ?>>Manajemen User</a>
        <a href="<?= base_url('pemeliharaan-db') ?>"<?= $aktif('pemeliharaan-db') ?>>Pemeliharaan Database</a>
        <a href="<?= base_url('audit') ?>"<?= $aktif('audit') ?>>Jejak Audit</a>
        <?php if ($bolehMenu('pengaturan')): ?><a href="<?= base_url('pengaturan/akses-menu') ?>"<?= $uriPenuh === 'pengaturan/akses-menu' ? ' class="aktif"' : '' ?>>Akses Menu</a><?php endif; ?>
        <?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
    </nav>
    <div class="profil">
      <div class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></div>
      <div class="siapa"><?= esc($pengguna['nama'] ?? '') ?><small><?= esc(ucfirst($pengguna['peran'] ?? '')) ?> · <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></small></div>
    </div>
  </aside>
  <div class="utama">
    <div class="topbar">
      <button type="button" class="tombol-samping" id="tombol-samping" aria-controls="samping" aria-label="Tampilkan / sembunyikan menu samping" aria-expanded="false" title="Menu samping">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <form class="cari" method="get" action="<?= base_url('bahan') ?>">
        <span aria-hidden="true">⌕</span>
        <input type="text" name="q" aria-label="Cari bahan" placeholder="Cari bahan…" value="<?= esc($q ?? '') ?>">
      </form>
      <div class="topbar-kanan">
        <span class="cip-top"><?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></span>
        <div class="grup-font" role="group" aria-label="Ukuran huruf">
          <button type="button" id="font-out" title="Perkecil huruf">A−</button>
          <span id="font-label">100%</span>
          <button type="button" id="font-in" title="Perbesar huruf">A+</button>
        </div>
        <button type="button" class="tombol-ikon-top" id="tombol-tema" title="Tema gelap / terang" aria-label="Ganti tema">
          <span class="ikon-bulan"><?= $svg('bulan') ?></span><span class="ikon-matahari"><?= $svg('matahari') ?></span>
        </button>
        <div class="user-menu">
          <button type="button" class="tombol-user" id="tombol-user" aria-haspopup="true" aria-expanded="false">
            <span class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></span>
            <span class="user-ringkas"><b><?= esc($pengguna['nama'] ?? '') ?></b><small><?= esc(ucfirst($pengguna['peran'] ?? '')) ?></small></span>
            <svg class="panah" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="dropdown-user" id="dropdown-user">
            <div class="dd-kepala">
              <span class="ava besar"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></span>
              <div><b><?= esc($pengguna['nama'] ?? '') ?></b><small>@<?= esc($pengguna['username'] ?? '') ?> · <?= esc(ucfirst($pengguna['peran'] ?? '')) ?></small></div>
            </div>
            <a href="<?= base_url('profil') ?>"><?= $svg('user') ?>Profil Saya</a>
            <?php if (($pengguna['peran'] ?? '') === 'admin'): ?>
            <a href="<?= base_url('pengguna') ?>"><?= $svg('users') ?>Manajemen User</a>
            <?php endif; ?>
            <div class="dd-garis"></div>
            <a href="<?= base_url('logout') ?>" class="dd-keluar"><?= $svg('keluar') ?>Keluar</a>
          </div>
        </div>
      </div>
    </div>
    <main class="isi">
      <div class="atas"><?= esc($judul ?? '') ?></div>
      <div id="wadah-toast" aria-live="polite">
        <?php if (session()->getFlashdata('sukses')): ?>
          <div class="toast sukses" role="status"><span class="toast-ikon">✓</span><div class="toast-isi"><strong>Berhasil</strong><p><?= esc(session()->getFlashdata('sukses')) ?></p></div><button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('galat')): ?>
          <div class="toast galat" role="alert"><span class="toast-ikon">!</span><div class="toast-isi"><strong>Gagal</strong><p><?= esc(session()->getFlashdata('galat')) ?></p></div><button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button></div>
        <?php endif; ?>
      </div>
      <?= $konten ?? '' ?>
    </main>
  </div>
</div>
<script defer src="<?= base_url('assets/alpine.min.js') ?>?v=<?= file_exists(FCPATH . 'assets/alpine.min.js') ? filemtime(FCPATH . 'assets/alpine.min.js') : 0 ?>"></script>
<script src="<?= base_url('assets/paginasi.js') ?>?v=<?= file_exists(FCPATH . 'assets/paginasi.js') ? filemtime(FCPATH . 'assets/paginasi.js') : 0 ?>"></script>
<script src="<?= base_url('assets/bulk.js') ?>?v=<?= file_exists(FCPATH . 'assets/bulk.js') ? filemtime(FCPATH . 'assets/bulk.js') : 0 ?>"></script>
<script src="<?= base_url('assets/uang.js') ?>?v=<?= file_exists(FCPATH . 'assets/uang.js') ? filemtime(FCPATH . 'assets/uang.js') : 0 ?>"></script>
<script src="<?= base_url('assets/select-cari.js') ?>?v=<?= file_exists(FCPATH . 'assets/select-cari.js') ? filemtime(FCPATH . 'assets/select-cari.js') : 0 ?>"></script>
<script>
// Toggle menu samping gaya TailAdmin — pilihan tersimpan di peramban.
// Desktop: tombol sembunyikan/tunjukkan sidebar (sebagaimana awalnya).
// Mobile: tombol membuka drawer dari kiri; tap lapik atau Escape menutupnya.
(function () {
  const kerangka = document.querySelector('.kerangka');
  const tombol = document.getElementById('tombol-samping');
  const lapik = document.getElementById('lapik-samping');
  if (!kerangka || !tombol) return;
  const mobile = () => window.matchMedia('(max-width: 760px)').matches;
  const aria = () => {
    const buka = mobile() ? kerangka.classList.contains('samping-buka')
      : !kerangka.classList.contains('samping-tutup');
    tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
  };
  if (localStorage.getItem('ap-samping') === 'tutup') kerangka.classList.add('samping-tutup');
  if (mobile() && localStorage.getItem('ap-samping') === 'buka') kerangka.classList.add('samping-buka');
  if (lapik) lapik.addEventListener('click', function () {
    kerangka.classList.toggle('samping-buka');
    if (!kerangka.classList.contains('samping-buka')) tombol.focus();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') kerangka.classList.remove('samping-buka');
  });
  tombol.addEventListener('click', function () {
    if (mobile()) {
      const buka = kerangka.classList.toggle('samping-buka');
      localStorage.setItem('ap-samping', buka ? 'buka' : 'tutup');
      if (buka) {
        const awal = kerangka.querySelector('.samping a, .samping .parent');
        setTimeout(function () { if (awal) awal.focus(); }, 0);
      }
    } else {
      const tutup = kerangka.classList.toggle('samping-tutup');
      localStorage.setItem('ap-samping', tutup ? 'tutup' : 'buka');
    }
    aria();
  });
  // Saat terlipat jadi rel ikon, klik ikon grup membuka sidebar lagi.
  kerangka.querySelectorAll('.samping .parent').forEach(function (t) {
    t.addEventListener('click', function () {
      if (!mobile() && kerangka.classList.contains('samping-tutup')) {
        kerangka.classList.remove('samping-tutup');
        localStorage.setItem('ap-samping', 'buka');
        aria();
      }
    });
  });
  aria();
})();
// Menu parent > child pada sidebar + dropdown user + tema gelap + ukuran huruf.
(function () {
  document.querySelectorAll('.induk').forEach(function (induk) {
    const kunci = 'ap-induk-' + induk.dataset.induk;
    const simpan = localStorage.getItem(kunci);
    if (simpan === 'buka') induk.classList.add('buka');
    if (simpan === 'tutup') induk.classList.remove('buka');
    induk.querySelector('.parent').addEventListener('click', function () {
      const buka = induk.classList.toggle('buka');
      localStorage.setItem(kunci, buka ? 'buka' : 'tutup');
    });
  });
  const menuUser = document.querySelector('.user-menu');
  const tombolUser = document.getElementById('tombol-user');
  if (menuUser && tombolUser) {
    const aturAria = () => tombolUser.setAttribute('aria-expanded', menuUser.classList.contains('buka') ? 'true' : 'false');
    tombolUser.addEventListener('click', function (e) {
      e.stopPropagation();
      menuUser.classList.toggle('buka');
      aturAria();
    });
    document.addEventListener('click', function (e) {
      if (!menuUser.contains(e.target)) { menuUser.classList.remove('buka'); aturAria(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { menuUser.classList.remove('buka'); aturAria(); }
    });
    aturAria();
  }
  const tombolTema = document.getElementById('tombol-tema');
  if (tombolTema) {
    tombolTema.addEventListener('click', function () {
      const gelap = document.documentElement.classList.toggle('gelap');
      localStorage.setItem('ap-tema', gelap ? 'gelap' : 'terang');
    });
  }
  const langkah = [0.8, 0.9, 1, 1.1, 1.2, 1.3];
  let zoom = parseFloat(localStorage.getItem('ap-zoom') || '1');
  if (!langkah.includes(zoom)) zoom = 1;
  const label = document.getElementById('font-label');
  const terapkan = function () {
    document.documentElement.style.zoom = zoom === 1 ? '' : zoom;
    localStorage.setItem('ap-zoom', String(zoom));
    if (label) label.textContent = Math.round(zoom * 100) + '%';
  };
  const btnIn = document.getElementById('font-in');
  const btnOut = document.getElementById('font-out');
  if (btnIn) btnIn.addEventListener('click', function () {
    const i = langkah.indexOf(zoom);
    if (i < langkah.length - 1) { zoom = langkah[i + 1]; terapkan(); }
  });
  if (btnOut) btnOut.addEventListener('click', function () {
    const i = langkah.indexOf(zoom);
    if (i > 0) { zoom = langkah[i - 1]; terapkan(); }
  });
  terapkan();
})();
</script>
<div id="modal-konfirmasi" class="mk-lapik" hidden>
  <div class="mk-kartu" role="alertdialog" aria-modal="true" aria-labelledby="mk-judul" aria-describedby="mk-pesan">
    <div class="mk-ikon" id="mk-ikon">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
    </div>
    <h3 id="mk-judul">Konfirmasi Hapus</h3>
    <p id="mk-pesan"></p>
    <div class="mk-aksi">
      <button type="button" id="mk-batal" class="mk-tombol batal">Batal</button>
      <button type="button" id="mk-ya" class="mk-tombol bahaya">Ya, Hapus</button>
    </div>
  </div>
</div>
<script>
// Notifikasi toast + modal konfirmasi gaya TailAdmin.
// Semua form ber-atribut data-konfirmasi dicegat: tampilkan modal dulu,
// form hanya terkirim setelah pengguna menekan tombol setuju.
(function () {
  // ---------- Toast ----------
  const wadah = document.getElementById('wadah-toast');
  function pasangToast(el) {
    const tutup = function () {
      el.classList.add('toast-keluar');
      setTimeout(() => el.remove(), 260);
    };
    const btn = el.querySelector('.toast-tutup');
    if (btn) btn.addEventListener('click', tutup);
    const teksToast = el.querySelector('.toast-isi p');
    if (!teksToast || teksToast.textContent.trim().length <= 160) setTimeout(tutup, 5200);
    requestAnimationFrame(() => el.classList.add('toast-masuk'));
  }
  if (wadah) wadah.querySelectorAll('.toast').forEach(pasangToast);
  window.tampilToast = function (pesan, jenis) {
    if (!wadah) return;
    const el = document.createElement('div');
    el.className = 'toast ' + (jenis === 'galat' ? 'galat' : 'sukses');
    el.setAttribute('role', 'status');
    el.innerHTML = '<span class="toast-ikon">' + (jenis === 'galat' ? '!' : '✓') + '</span>' +
      '<div class="toast-isi"><strong>' + (jenis === 'galat' ? 'Gagal' : 'Berhasil') + '</strong><p></p></div>' +
      '<button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button>';
    el.querySelector('p').textContent = pesan;
    wadah.appendChild(el);
    pasangToast(el);
  };

  // ---------- Modal konfirmasi ----------
  const lapik = document.getElementById('modal-konfirmasi');
  if (!lapik) return;
  const kartu = lapik.querySelector('.mk-kartu');
  const judulEl = document.getElementById('mk-judul');
  const pesanEl = document.getElementById('mk-pesan');
  const ikonEl = document.getElementById('mk-ikon');
  const ikonBawaan = ikonEl.innerHTML;
  const ikonImpor = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 8l5-5 5 5"/><path d="M12 3v12"/></svg>';
  const btnYa = document.getElementById('mk-ya');
  const btnBatal = document.getElementById('mk-batal');
  let formAntrian = null;

  function tutupModal() {
    lapik.hidden = true;
    formAntrian = null;
    kartu.classList.remove('lebar');
  }
  function bukaModal(form) {
    formAntrian = form;
    const aksi = form.getAttribute('action') || '';
    const bahaya = aksi.includes('hapus');
    judulEl.textContent = form.dataset.konfirmasiJudul || (bahaya ? 'Konfirmasi Hapus' : 'Konfirmasi');
    if (form.dataset.konfirmasiHtml) {
      pesanEl.innerHTML = form.dataset.konfirmasiHtml;
      kartu.classList.add('lebar');
      ikonEl.innerHTML = ikonImpor;
    } else {
      pesanEl.textContent = form.dataset.konfirmasi || 'Lanjutkan operasi ini?';
      kartu.classList.remove('lebar');
      ikonEl.innerHTML = ikonBawaan;
    }
    btnYa.textContent = form.dataset.konfirmasiTombol || (bahaya ? 'Ya, Hapus' : 'Ya, Lanjutkan');
    btnYa.className = 'mk-tombol ' + (bahaya ? 'bahaya' : 'utama');
    ikonEl.className = 'mk-ikon ' + (bahaya ? 'bahaya' : 'utama');
    lapik.hidden = false;
    btnBatal.focus();
  }
  btnBatal.addEventListener('click', tutupModal);
  lapik.addEventListener('click', function (e) { if (e.target === lapik) tutupModal(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !lapik.hidden) tutupModal();
  });
  btnYa.addEventListener('click', function () {
    const form = formAntrian;
    tutupModal();
    if (form) {
      form.dataset.terkonfirmasi = '1';
      form.submit();
    }
  });
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.dataset && (form.dataset.konfirmasi || form.dataset.konfirmasiHtml) && !form.dataset.terkonfirmasi) {
      e.preventDefault();
      bukaModal(form);
    }
  });
})();
</script>
<script>
// Panel berlipat (details.panel-lipat): ingat keadaan buka/tutup per panel.
document.querySelectorAll('details.panel-lipat[data-lipat-id]').forEach(function (d) {
  const kunci = 'ap-lipat-' + d.getAttribute('data-lipat-id');
  try {
    const simpan = localStorage.getItem(kunci);
    if (simpan !== null) d.open = simpan === '1';
  } catch (e) {}
  d.addEventListener('toggle', function () {
    try { localStorage.setItem(kunci, d.open ? '1' : '0'); } catch (e) {}
  });
});
</script>
</body>
</html>

<?php
/**
 * Partial layout: kolom utama (topbar, judul halaman, wadah toast,
 * dan konten halaman). Menutup </main>, </div> utama, </div> kerangka
 * yang dibuka partial _sidebar.
 */
$svg = static fn (string $k): string => ikonSvg($k);

// Jejak halaman (breadcrumb): diturunkan dari URI dengan peta label yang
// sama seperti menu sidebar; judul halaman menjadi remah terakhir.
// Grup menu tidak punya halaman sendiri sehingga tampil tanpa tautan.
$segJejak = array_values(array_filter(explode('/', uri_string()), static fn ($s) => $s !== ''));
$jejak    = [];
if ($segJejak !== []) {
    $petaJejak = [
        'impor'             => ['Perencanaan', 'Impor Dokumen Perencana'],
        'analisa'           => ['Perencanaan', 'Analisa Pekerjaan'],
        'acuan'             => ['Perencanaan', 'Acuan AHSP'],
        'validasi-harga'    => ['Perencanaan', 'Validasi Harga Satuan'],
        'bersih'            => ['Perencanaan', 'Pembersihan Hasil Impor'],
        'rencana-belanja'   => ['Perencanaan', 'Rencana Belanja'],
        'rencana-mingguan'  => ['Perencanaan', 'Rencana Mingguan'],
        'simulasi'          => ['Perencanaan', 'Simulasi Mingguan'],
        'biaya-non-gedung'  => ['Perencanaan', 'Biaya Non-Gedung'],
        'gedung'            => ['Pembangunan Gedung', 'Kelola Gedung'],
        'foto-progres'      => ['Pembangunan Gedung', 'Foto Progres'],
        'kurva-s'           => ['Pembangunan Gedung', 'Kurva S'],
        'laporan-kemajuan'  => ['Pembangunan Gedung', 'Laporan Kemajuan'],
        'revisi-pekerjaan'  => ['Pembangunan Gedung', 'BA Revisi Pekerjaan'],
        'spk'               => ['Belanja', 'Register SPK'],
        'belum-bernomor'    => ['Belanja', 'Belum Bernomor SPK'],
        'survei'            => ['Belanja', 'Survei Harga'],
        'negosiasi'         => ['Belanja', 'Negosiasi'],
        'dokumen'           => ['Belanja', 'BAP & BAST / Cetak'],
        'bahan'             => ['Material', 'Master Bahan & Upah'],
        'material'          => ['Material', 'Buku Material'],
        'sisa-bahan'        => ['Material', 'Sisa Bahan'],
        'penerimaan-dana'   => ['Keuangan', 'Penerimaan Dana'],
        'invoice'           => ['Keuangan', 'Invoice'],
        'buku-kas'          => ['Keuangan', 'Buku Kas (BKU)'],
        'bukti-setor'       => ['Keuangan', 'Bukti Setor & Pajak'],
        'upah'              => ['Keuangan', 'Pembayaran Upah'],
        'lpj'               => ['Laporan', 'LPJ & Realisasi'],
        'laporan'           => ['Laporan', 'Laporan & Ekspor'],
        'template-laporan'  => ['Laporan', 'Template Laporan'],
        'pengaturan'        => ['Pengaturan', 'Identitas & Pengaturan'],
        'sekolah'           => ['Pengaturan', 'Kelola Sekolah'],
        'pengguna'          => ['Pengaturan', 'Manajemen User'],
        'pemeliharaan-db'   => ['Pengaturan', 'Pemeliharaan Database'],
        'audit'             => ['Pengaturan', 'Jejak Audit'],
        'profil'            => [null, 'Profil Saya'],
        'panduan'           => [null, 'Panduan Penggunaan'],
    ];
    $petaSubJejak = [
        'invoice/jurnal'        => 'Jurnal Pembayaran',
        'lpj/rab-gabungan'      => 'RAB Gabungan',
        'pengaturan/akses-menu' => 'Akses Menu',
        'gedung/boq'            => 'BOQ',
    ];
    $judulBersih = trim(str_replace(' — Aplikasi PBJ Sekolah', '', (string) ($judul ?? '')));
    if ($judulBersih === 'Aplikasi PBJ Sekolah') {
        $judulBersih = ''; // judul bawaan tampil() — bukan nama halaman
    }
    $jejak[]     = ['label' => 'Dashboard', 'url' => base_url('/')];
    $seg1        = $segJejak[0];
    if (isset($petaJejak[$seg1])) {
        [$grupJ, $labelJ] = $petaJejak[$seg1];
        if ($grupJ !== null) {
            $jejak[] = ['label' => $grupJ, 'url' => null];
        }
        $jejak[] = ['label' => $labelJ, 'url' => count($segJejak) > 1 ? base_url($seg1) : null];
        $kunciSub = $seg1 . '/' . ($segJejak[1] ?? '');
        if (count($segJejak) > 1 && isset($petaSubJejak[$kunciSub])) {
            $jejak[] = ['label' => $petaSubJejak[$kunciSub], 'url' => count($segJejak) > 2 ? base_url($kunciSub) : null];
        }
    }
    $labelTerakhir = $jejak[count($jejak) - 1]['label'] ?? '';
    if ($judulBersih !== '' && $judulBersih !== $labelTerakhir) {
        $jejak[] = ['label' => $judulBersih, 'url' => null];
    }
    // Remah terakhir selalu halaman saat ini (tanpa tautan).
    $jejak[count($jejak) - 1]['url'] = null;
}
?>
  <div class="utama">
    <div class="topbar">
      <button type="button" class="tombol-samping" id="tombol-samping" aria-controls="samping" aria-label="Tampilkan / sembunyikan menu samping" aria-expanded="false" title="Menu samping">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <form class="cari" method="get" action="<?= base_url('bahan') ?>">
        <span aria-hidden="true">⌕</span>
        <input type="text" name="q" aria-label="Cari bahan" placeholder="Cari bahan di master…" value="<?= esc($q ?? '') ?>">
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
      <?php if ($jejak !== []): ?>
      <nav class="remah" aria-label="Jejak halaman">
        <?php foreach ($jejak as $i => $remah): ?>
          <?php if ($i > 0): ?><span aria-hidden="true">›</span><?php endif; ?>
          <?php if ($remah['url'] !== null): ?><a href="<?= esc($remah['url']) ?>"><?= esc($remah['label']) ?></a><?php elseif ($i === count($jejak) - 1): ?><span aria-current="page"><?= esc($remah['label']) ?></span><?php else: ?><span><?= esc($remah['label']) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
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

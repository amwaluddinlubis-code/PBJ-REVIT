<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/idds.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
<script>
try {
  if (localStorage.getItem('ap-tema') === 'gelap') document.documentElement.classList.add('gelap');
  var _z = parseFloat(localStorage.getItem('ap-zoom') || '1');
  if (_z && _z !== 1) document.documentElement.style.zoom = _z;
} catch (e) {}
</script>
</head>
<body class="login">
  <div class="login-grid">
    <!-- Kolom formulir -->
    <div class="login-form-side">
      <div class="login-form-wrap">
        <div class="login-brand">
          <div class="kotak-logo">AP</div>
          <div class="nama-logo">Analisa Perencana<small>Perencanaan · Belanja · LPJ</small></div>
        </div>

        <h1>Masuk ke akun Anda</h1>
        <p class="sub">Kelola perencanaan pembangunan, belanja, dan LPJ sekolah dalam satu aplikasi.</p>

        <?php if (session()->getFlashdata('galat')): ?>
          <div class="pesan galat"><?= esc(session()->getFlashdata('galat')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('sukses')): ?>
          <div class="pesan sukses"><?= esc(session()->getFlashdata('sukses')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('login') ?>">
          <?= csrf_field() ?>
          <label for="username">Username</label>
          <div class="login-field">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
          </div>

          <label for="sandi">Kata Sandi</label>
          <div class="login-field">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="sandi" name="sandi" placeholder="Masukkan kata sandi" required autocomplete="current-password">
            <button type="button" class="login-eye" id="lihat-sandi" aria-label="Tampilkan kata sandi">
              <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>

          <button type="submit" class="login-submit">
            Masuk
            <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </form>

        <p class="login-foot">Aplikasi Panitia Pembangunan Satuan Pendidikan (P2SP)<br>Revitalisasi Sekolah — Tahun Anggaran berjalan</p>
      </div>
    </div>

    <!-- Kolom merek / panel kanan -->
    <div class="login-brand-side">
      <div class="login-brand-inner">
        <div class="login-brand-logo">
          <div class="kotak-logo besar">AP</div>
          <div>
            <div class="login-brand-nama">Analisa Perencana</div>
            <div class="login-brand-sub">Dokumen perencana menjadi rencana belanja &amp; LPJ</div>
          </div>
        </div>
        <h2>Dari BOQ perencana,<br>sampai LPJ per gedung.</h2>
        <p class="login-brand-teks">Unggah workbook RAB perencana — aplikasi menghitung kebutuhan bahan dan upah dari analisa AHSP, menyusun rencana belanja, menjaga pagu anggaran, dan menyusun LPJ untuk setiap gedung.</p>
        <ul class="login-fitur">
          <li>
            <span class="login-fitur-ikon"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg></span>
            <div><b>Impor Dokumen Perencana</b><small>BOQ, analisa AHSP, dan daftar harga terbaca otomatis per gedung.</small></div>
          </li>
          <li>
            <span class="login-fitur-ikon"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span>
            <div><b>Kebutuhan Bahan &amp; Upah Otomatis</b><small>Volume BOQ × koefisien analisa — per gedung maupun gabungan sekolah.</small></div>
          </li>
          <li>
            <span class="login-fitur-ikon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></span>
            <div><b>Kontrol Pagu Anggaran</b><small>Total belanja tidak dapat melewati total anggaran bantuan.</small></div>
          </li>
          <li>
            <span class="login-fitur-ikon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg></span>
            <div><b>LPJ per Gedung &amp; Sekolah</b><small>Realisasi fisik dan belanja tertelusur sampai dokumen cetak resmi.</small></div>
          </li>
        </ul>
      </div>
    </div>
  </div>

<script>
document.getElementById('lihat-sandi').addEventListener('click', function () {
  const s = document.getElementById('sandi');
  s.type = s.type === 'password' ? 'text' : 'password';
  this.classList.toggle('aktif', s.type === 'text');
  s.focus();
});
</script>
</body>
</html>

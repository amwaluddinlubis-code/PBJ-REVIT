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
try { if (localStorage.getItem('ap-tema') === 'gelap') document.documentElement.classList.add('gelap'); } catch (e) {}
</script>
</head>
<body class="login login-v2">
  <main class="login-v2-shell">
    <section class="login-v2-intro" aria-label="Informasi aplikasi">
      <div class="login-v2-mark"><span>AP</span><i></i></div>
      <p class="login-v2-eyebrow">Portal Pengelola Sekolah</p>
      <h1>Rencana yang rapi.<br><em>Pelaksanaan yang terlacak.</em></h1>
      <p class="login-v2-lead">Satu ruang kerja untuk menyusun kebutuhan, mengendalikan belanja, memantau progres, dan menutup LPJ revitalisasi sekolah.</p>
      <div class="login-v2-proof"><span>01</span><div><b>Perencanaan sampai LPJ</b><small>BOQ, SPK, pembayaran, pajak, dan progres tetap terhubung.</small></div></div>
      <div class="login-v2-proof"><span>02</span><div><b>Berbasis sekolah aktif</b><small>Setiap pencatatan mengikuti sekolah yang sedang dikelola.</small></div></div>
      <p class="login-v2-footer">Analisa Perencana · P2SP · Revitalisasi Sekolah</p>
    </section>
    <section class="login-v2-card" aria-labelledby="login-v2-title">
      <div class="login-v2-card-top"><span class="login-v2-status"><i></i> Sistem siap digunakan</span><span class="login-v2-year">TA berjalan</span></div>
      <div class="login-v2-heading"><p class="login-v2-kicker">Masuk ke ruang kerja</p><h2 id="login-v2-title">Selamat datang kembali</h2><p>Gunakan akun Anda untuk melanjutkan pekerjaan hari ini.</p></div>
      <?php if (session()->getFlashdata('galat')): ?><div class="pesan galat" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('sukses')): ?><div class="pesan sukses" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div><?php endif; ?>
      <form method="post" action="<?= base_url('login') ?>" class="login-v2-form">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <div class="login-v2-input"><span aria-hidden="true">@</span><input type="text" id="username" name="username" placeholder="nama pengguna" required autofocus autocomplete="username"></div>
        <label for="sandi">Kata sandi</label>
        <div class="login-v2-input"><span aria-hidden="true">••</span><input type="password" id="sandi" name="sandi" placeholder="masukkan kata sandi" required autocomplete="current-password"><button type="button" id="lihat-sandi" aria-label="Tampilkan kata sandi">Lihat</button></div>
        <button type="submit" class="login-v2-submit"><span>Masuk ke aplikasi</span><b aria-hidden="true">↗</b></button>
      </form>
      <p class="login-v2-note">Akses ini dilindungi. Jangan bagikan akun kepada pihak lain.</p>
    </section>
  </main>
<script>
document.getElementById('lihat-sandi').addEventListener('click', function () { const s = document.getElementById('sandi'); const tampil = s.type === 'password'; s.type = tampil ? 'text' : 'password'; this.textContent = tampil ? 'Sembunyikan' : 'Lihat'; s.focus(); });
</script>
</body>
</html>

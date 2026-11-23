<?php
/**
 * Partial layout: tag aset JS bersama (dengan cache-busting filemtime)
 * dan skrip perilaku layout (toggle sidebar, menu induk, dropdown user,
 * tema, zoom huruf, konversi tanggal dd/mm/yyyy).
 */
?>
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
    document.documentElement.style.setProperty('--ap-zoom-balik', String(1 / zoom));
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

  // Semua isian tanggal tampil dd/mm/yyyy untuk pengguna, sedangkan nilai
  // yang dikirim & disimpan tetap format baku yyyy-mm-dd: input type=date
  // diganti teks dd/mm/yyyy, lalu dikonversi balik saat formulir dikirim.
  const isoKeDmy = function (v) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
    return m ? m[3] + '/' + m[2] + '/' + m[1] : '';
  };
  const dmyKeIso = function (v) {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec((v || '').trim());
    if (!m) return '';
    const d = new Date(+m[3], +m[2] - 1, +m[1]);
    if (d.getFullYear() !== +m[3] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[1]) return '';
    return m[3] + '-' + m[2] + '-' + m[1];
  };
  document.querySelectorAll('input[type="date"]').forEach(function (inp) {
    const awal = inp.value;
    inp.type = 'text';
    inp.classList.add('isian-tanggal');
    inp.setAttribute('inputmode', 'numeric');
    inp.setAttribute('placeholder', 'dd/mm/yyyy');
    inp.setAttribute('autocomplete', 'off');
    inp.value = isoKeDmy(awal);
    inp.addEventListener('input', function () {
      const a = inp.value.replace(/\D/g, '').slice(0, 8);
      inp.value = a.length > 4 ? a.slice(0, 2) + '/' + a.slice(2, 4) + '/' + a.slice(4)
        : a.length > 2 ? a.slice(0, 2) + '/' + a.slice(2) : a;
    });
  });
  document.addEventListener('submit', function (e) {
    e.target.querySelectorAll('input.isian-tanggal').forEach(function (inp) {
      if (!inp.readOnly && !inp.disabled) inp.value = dmyKeIso(inp.value);
    });
  }, true);
})();
</script>

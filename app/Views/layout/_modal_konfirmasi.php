<?php
/**
 * Partial layout: modal konfirmasi bersama (markup + skrip toast modal konfirmasi bersama (markup + skrip toast &
 * modal) + skrip panel berlipat. Semua form ber-atribut data-konfirmasi
 * dicegat skrip modal ini.
 */
?>
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

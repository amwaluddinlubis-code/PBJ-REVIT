<?php /* Partial analisa/detail: skrip halaman (buka/isi modal komponen). */ ?><script>
(function () {
  var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
  var angka = function (s) {
    s = String(s || '').trim();
    if (!s) return 0;
    if (s.indexOf(',') >= 0) s = s.replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
  };
  var uang = function (s) { return parseFloat(String(s || '').replace(/[^0-9]/g, '')) || 0; };
  var pemicu = null;
  function buka(id, btn) {
    pemicu = btn || null;
    var m = document.getElementById(id);
    m.hidden = false;
    var f = m.querySelector('input:not([type=hidden]), select');
    if (f) f.focus();
  }
  function tutup(m) { m.hidden = true; if (pemicu) pemicu.focus(); }
  document.querySelectorAll('.mk-lapik').forEach(function (m) {
    m.addEventListener('click', function (e) { if (e.target === m) tutup(m); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelectorAll('.mk-lapik:not([hidden])').forEach(tutup);
  });
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-tutup-modal]');
    if (t) { tutup(t.closest('.mk-lapik')); return; }
    var u = e.target.closest('[data-ubah-komp]');
    if (u) {
      document.getElementById('fu-id').value = u.dataset.id;
      document.getElementById('fu-nama').textContent = u.dataset.nama;
      document.getElementById('fu-jenis').textContent = u.dataset.jenis;
      document.getElementById('fu-koef').value = u.dataset.koefisien;
      var pa = document.getElementById('fu-acuan');
      if (u.dataset.koefAcuan) { document.getElementById('fu-koef-acuan').textContent = u.dataset.koefAcuan + ' ' + (u.dataset.satuan || ''); pa.hidden = false; } else { pa.hidden = true; }
      document.getElementById('fu-satuan').value = u.dataset.satuan;
      var tarif = document.getElementById('fu-tarif');
      tarif.value = u.dataset.tarif;
      if (window.inisiasiUang) window.inisiasiUang(document.getElementById('modal-ubah-komp'));
      nilaiUbah();
      buka('modal-ubah-komp', u);
      return;
    }
    if (e.target.closest('#btn-tambah-komp')) buka('modal-tambah-komp', e.target.closest('#btn-tambah-komp'));
  });
  function nilaiUbah() {
    document.getElementById('nilai-ubah').textContent = rp(angka(document.getElementById('fu-koef').value) * uang(document.getElementById('fu-tarif').value));
  }
  ['fu-koef', 'fu-tarif'].forEach(function (i) { document.getElementById(i).addEventListener('input', nilaiUbah); });
  function nilaiTambah() {
    document.getElementById('nilai-tambah').textContent = rp(angka(document.getElementById('ft-koef').value) * uang(document.getElementById('ft-tarif').value));
  }
  ['ft-koef', 'ft-tarif'].forEach(function (i) { document.getElementById(i).addEventListener('input', nilaiTambah); });

  async function segarkan() {
    var html = await (await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
    var doc = new DOMParser().parseFromString(html, 'text/html');
    ['wadah-komponen', 'ringkas-analisa'].forEach(function (id) {
      var baru = doc.getElementById(id), lama = document.getElementById(id);
      if (baru && lama) lama.innerHTML = baru.innerHTML;
    });
    var w = document.getElementById('wadah-komponen');
    if (window.inisiasiPaginasi) window.inisiasiPaginasi(w);
    if (window.inisiasiUang) window.inisiasiUang(w);
  }
  async function kirim(form, modalId) {
    try {
      var r = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      var data = await r.json();
      if (data.ok) {
        tutup(document.getElementById(modalId));
        if (window.tampilToast) window.tampilToast(data.pesan, 'sukses');
        await segarkan();
      } else if (window.tampilToast) {
        window.tampilToast(data.pesan || 'Gagal menyimpan.', 'galat');
      }
    } catch (err) {
      if (window.tampilToast) window.tampilToast('Gagal terhubung ke server.', 'galat');
    }
  }
  document.getElementById('form-ubah-komp').addEventListener('submit', function (e) { e.preventDefault(); kirim(e.target, 'modal-ubah-komp'); });
  document.getElementById('form-tambah-komp').addEventListener('submit', function (e) { e.preventDefault(); kirim(e.target, 'modal-tambah-komp'); });

  var cocok = location.hash.match(/^#komponen-(\d+)$/);
  if (cocok) {
    var b = document.querySelector('[data-ubah-komp][data-id="' + cocok[1] + '"]');
    if (b) b.click();
  }
})();
</script>

// Helper FORM MODAL CRUD — perilaku untuk modal yang dirender
// form_modal() (app/Common.php). bukaFormModal(id) membuka mode Tambah;
// bukaFormModal(id, data) mode Ubah (data = nilai field menurut nama,
// termasuk 'id'). Simpan dikirim via fetch (AJAX) dan server membalas
// JSON {ok, pesan} (pola balasForm); sesudah berhasil halaman dimuat
// ulang. Hapus berjalan lewat form data-konfirmasi standar aplikasi
// (modal konfirmasi #mk-ya), bukan modal baru.
(function () {
  var tooltipEl = null;
  var tooltipTimer = null;
  function sembunyiTooltip() {
    if (tooltipEl) { tooltipEl.remove(); tooltipEl = null; }
    if (tooltipTimer) { clearTimeout(tooltipTimer); tooltipTimer = null; }
  }
  function tampilTooltip(inp) {
    sembunyiTooltip();
    var kotak = inp.closest('.fm-kotak-field') || inp;
    var r = kotak.getBoundingClientRect();
    tooltipEl = document.createElement('div');
    tooltipEl.className = 'fm-tooltip';
    tooltipEl.textContent = (inp.dataset.label || 'Kolom ini') + ' wajib diisi.';
    document.body.appendChild(tooltipEl);
    var tinggi = tooltipEl.offsetHeight;
    var diBawah = r.top < tinggi + 56;
    tooltipEl.classList.toggle('bawah', diBawah);
    tooltipEl.style.left = Math.max(8, r.left + 4) + 'px';
    tooltipEl.style.top = (diBawah ? r.bottom + 8 : r.top - tinggi - 8) + 'px';
    tooltipTimer = setTimeout(sembunyiTooltip, 4000);
  }

  window.tutupFormModal = function (id) {
    var el = document.getElementById(id);
    if (el) el.hidden = true;
  };

  window.bukaFormModal = function (id, data) {
    var el = document.getElementById(id);
    if (!el) return;
    var form = el.querySelector('[data-fm-form]');
    var galat = el.querySelector('[data-fm-galat]');
    var judul = el.querySelector('[data-fm-judul]');
    var btnSimpan = el.querySelector('[data-fm-simpan]');
    var btnHapus = el.querySelector('[data-fm-hapus]');
    form.reset();
    form.querySelectorAll('.fm-salah').forEach(function (el) { el.classList.remove('fm-salah'); });
    sembunyiTooltip();
    if (galat) { galat.hidden = true; galat.textContent = ''; }
    var ubah = !!(data && data.id);
    if (data) {
      Object.keys(data).forEach(function (k) {
        var inp = form.querySelector('[name="' + k + '"]');
        if (!inp) return;
        if (inp.type === 'checkbox') inp.checked = !!data[k];
        else if (inp.classList.contains('isian-tanggal') && /^\d{4}-\d{2}-\d{2}$/.test(String(data[k] == null ? '' : data[k]))) {
          // Isian tanggal aplikasi tampil dd/mm/yyyy (kelas isian-tanggal,
          // dikonversi balik ke ISO saat submit oleh skrip layout) — data
          // dari server berformat ISO, jadi tampilkan sebagai dd/mm/yyyy.
          var p = String(data[k]).split('-');
          inp.value = p[2] + '/' + p[1] + '/' + p[0];
        }
        else inp.value = data[k] == null ? '' : data[k];
        inp.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
    if (window.inisiasiUang) window.inisiasiUang(form);
    if (judul) judul.textContent = ubah ? (el.dataset.judulUbah || '') : (el.dataset.judulTambah || '');
    if (btnSimpan) btnSimpan.textContent = ubah ? (btnSimpan.dataset.teksUbah || 'Simpan Perubahan') : (btnSimpan.dataset.teksTambah || 'Simpan');
    if (btnHapus) btnHapus.hidden = !ubah;
    form.dataset.fmId = ubah ? String(data.id) : '';
    el.hidden = false;
    var pertama = form.querySelector('input:not([type=hidden]), select, textarea');
    if (pertama) pertama.focus();
  };

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-form-modal]')) { t.hidden = true; return; }
    var tutup = t.closest ? t.closest('[data-fm-tutup]') : null;
    if (tutup) {
      var el1 = tutup.closest('[data-form-modal]');
      if (el1) el1.hidden = true;
      return;
    }
    var hapus = t.closest ? t.closest('[data-fm-hapus]') : null;
    if (hapus) {
      var el2 = hapus.closest('[data-form-modal]');
      var form2 = el2.querySelector('[data-fm-form]');
      var idBaris = form2.dataset.fmId || '';
      var pola = el2.dataset.aksiHapus || '';
      if (!idBaris || !pola) return;
      var f = document.createElement('form');
      f.method = 'post';
      f.action = pola.replace('__ID__', idBaris).replace('{id}', idBaris);
      f.setAttribute('data-konfirmasi', el2.dataset.konfirmasiHapus || 'Hapus baris ini?');
      var csrf = form2.querySelector('input[name="csrf_test_name"]');
      if (csrf) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = csrf.name; h.value = csrf.value;
        f.appendChild(h);
      }
      document.body.appendChild(f);
      el2.hidden = true;
      f.requestSubmit();
    }
  });

  document.addEventListener('input', function (e) {
    var inp = e.target;
    if (inp.classList && inp.classList.contains('fm-salah') && String(inp.value || '').trim()) {
      inp.classList.remove('fm-salah');
      sembunyiTooltip();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('[data-form-modal]:not([hidden])').forEach(function (el) { el.hidden = true; });
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('[data-fm-form]')) return;
    e.preventDefault();
    var el = form.closest('[data-form-modal]');
    var galat = el.querySelector('[data-fm-galat]');
    // Validasi wajib via tooltip (form bernovalidate; gelembung bawaan
    // peramban tidak dipakai).
    var tidakValid = null;
    form.querySelectorAll('[required]').forEach(function (inp) {
      var kosong = !String(inp.value || '').trim();
      inp.classList.toggle('fm-salah', kosong);
      if (kosong && !tidakValid) tidakValid = inp;
    });
    if (tidakValid) {
      tampilTooltip(tidakValid);
      tidakValid.focus();
      return;
    }
    sembunyiTooltip();
    form.querySelectorAll('input[data-uang]').forEach(function (inp) {
      inp.value = String(inp.value).replace(/[^0-9]/g, '');
    });
    var btn = el.querySelector('[data-fm-simpan]');
    if (btn) btn.disabled = true;
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      var ct = r.headers.get('content-type') || '';
      if (ct.indexOf('application/json') >= 0) return r.json();
      return { ok: true, pesan: '' };
    }).then(function (j) {
      if (btn) btn.disabled = false;
      if (j && j.ok) {
        el.hidden = true;
        if (window.tampilToast && j.pesan) window.tampilToast(j.pesan, 'sukses');
        var segar = el.dataset.fmSegar;
        if (segar) {
          // Segarkan sebagian: ganti isi elemen target dari muatan segar
          // halaman ini, tanpa memuat ulang seluruh halaman.
          fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
              var doc = new DOMParser().parseFromString(html, 'text/html');
              segar.split(',').forEach(function (pemilih) {
                pemilih = pemilih.trim();
                if (!pemilih) return;
                var baru = doc.querySelector(pemilih);
                var lama = document.querySelector(pemilih);
                if (!baru || !lama) return;
                var terbuka = lama.tagName === 'DETAILS' ? lama.open : null;
                lama.innerHTML = baru.innerHTML;
                if (terbuka !== null) lama.open = terbuka;
                if (window.inisiasiPaginasi) window.inisiasiPaginasi(lama);
                if (window.inisiasiUang) window.inisiasiUang(lama);
              });
            })
            .catch(function () { location.reload(); });
        } else {
          setTimeout(function () { location.reload(); }, j.pesan ? 500 : 0);
        }
      } else {
        if (window.inisiasiUang) window.inisiasiUang(form);
        if (galat) { galat.textContent = (j && j.pesan) || 'Gagal menyimpan.'; galat.hidden = false; }
      }
    }).catch(function () {
      if (btn) btn.disabled = false;
      if (window.inisiasiUang) window.inisiasiUang(form);
      if (galat) { galat.textContent = 'Gagal menghubungi server.'; galat.hidden = false; }
    });
  });
})();

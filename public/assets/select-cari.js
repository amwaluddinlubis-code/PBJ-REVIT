// Dropdown pilihan yang dapat dicari — meniru Preline Advanced Select
// (varian "Searchable dropdown"): tombol penampil pilihan, panel berisi
// kotak pencarian di atas daftar opsi, penyaringan sambil mengetik,
// navigasi papan tik (↑/↓/Enter/Esc), dan tanda centang pada opsi terpilih.
//
// Setiap <select data-select-cari> diperkaya di tempat: select asli tetap
// di formulir (tersembunyi secara visual) sehingga nama/nilai yang terkirim
// dan peristiwa 'change' tidak berubah. Panggil ulang
// window.inisiasiSelectCari(akar) untuk select yang dimuat belakangan.
window.inisiasiSelectCari = function (akar) {
  (akar || document).querySelectorAll('select[data-select-cari]').forEach(function (sel) {
    if (sel.dataset.scJadi) return;
    sel.dataset.scJadi = '1';

    var bungkus = document.createElement('div');
    bungkus.className = 'sc';
    bungkus.style.marginTop = window.getComputedStyle(sel).marginTop;
    sel.parentNode.insertBefore(bungkus, sel);
    bungkus.appendChild(sel);
    sel.classList.add('sc-asli');
    sel.style.marginTop = '0';

    var tombol = document.createElement('button');
    tombol.type = 'button';
    tombol.className = 'sc-tombol';
    tombol.setAttribute('aria-haspopup', 'listbox');
    tombol.setAttribute('aria-expanded', 'false');
    if (sel.disabled) tombol.disabled = true;
    tombol.innerHTML = '<span class="sc-label"></span>'
      + '<svg class="sc-panah" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    bungkus.appendChild(tombol);

    var panel = document.createElement('div');
    panel.className = 'sc-panel';
    panel.hidden = true;
    panel.innerHTML = '<div class="sc-cari">'
      + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>'
      + '<input type="text" autocomplete="off" placeholder="Cari…">'
      + '</div><div class="sc-daftar" role="listbox"></div>'
      + '<div class="sc-kosong" hidden>Tidak ditemukan</div>';
    bungkus.appendChild(panel);

    var masukan = panel.querySelector('.sc-cari input');
    var daftar = panel.querySelector('.sc-daftar');
    var kosong = panel.querySelector('.sc-kosong');
    var label = tombol.querySelector('.sc-label');
    var placeholder = sel.getAttribute('data-placeholder') || '— pilih —';

    // Bangun daftar opsi dari select asli. Dibangun ulang otomatis bila
    // opsi berubah dari luar (MutationObserver) — mis. select pekerjaan
    // yang isinya bergantung pada select gedung.
    var item = [];
    function bangunItem() {
      daftar.innerHTML = '';
      item = [];
      Array.prototype.forEach.call(sel.options, function (op) {
        var li = document.createElement('div');
        li.className = 'sc-opsi';
        li.setAttribute('role', 'option');
        li.dataset.value = op.value;
        li.innerHTML = '<span class="sc-opsi-teks"></span>'
          + '<svg class="sc-centang" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        li.querySelector('.sc-opsi-teks').textContent = op.textContent;
        li.addEventListener('mousedown', function (e) { e.preventDefault(); pilih(li); });
        daftar.appendChild(li);
        item.push(li);
      });
    }
    bangunItem();
    new MutationObserver(function () { bangunItem(); segarkan(); }).observe(sel, { childList: true });
    sel._scPerbarui = function () { bangunItem(); segarkan(); };

    function segarkan() {
      var op = sel.options[sel.selectedIndex];
      var ada = op && op.value !== '';
      // Tanpa data-placeholder, label opsi kosong milik select dipakai apa adanya.
      label.textContent = ada || !sel.hasAttribute('data-placeholder') ? (op ? op.textContent : placeholder) : placeholder;
      label.classList.toggle('sc-kosong-teks', !ada);
      item.forEach(function (li) { li.classList.toggle('terpilih', li.dataset.value === sel.value && sel.value !== ''); });
    }

    function terlihat() {
      return item.filter(function (li) { return !li.hidden; });
    }

    function saring() {
      var kata = masukan.value.trim().toLowerCase();
      var jml = 0;
      item.forEach(function (li) {
        var cocok = kata === '' || li.textContent.toLowerCase().includes(kata);
        li.hidden = !cocok;
        if (cocok) jml++;
      });
      kosong.hidden = jml > 0;
      tandaiAktif(terlihat()[0] || null);
    }

    function tandaiAktif(li) {
      item.forEach(function (x) { x.classList.toggle('aktif', x === li); });
      if (li) li.scrollIntoView({ block: 'nearest' });
    }

    // Panel dirender sebagai lapisan fixed di <body> (portal): selalu
    // melayang di atas panel/kartu lain dan tidak pernah membuat wadah
    // apa pun menambah scroll atau memotong dropdown.
    function posisikan() {
      var r = tombol.getBoundingClientRect();
      panel.style.left = r.left + 'px';
      panel.style.width = r.width + 'px';
      if (window.innerHeight - r.bottom < 290 && r.top > 290) {
        panel.style.top = 'auto';
        panel.style.bottom = (window.innerHeight - r.top + 4) + 'px';
      } else {
        panel.style.bottom = 'auto';
        panel.style.top = (r.bottom + 4) + 'px';
      }
    }

    function buka() {
      document.querySelectorAll('.sc-panel:not([hidden])').forEach(function (p) { if (p !== panel) p.hidden = true; });
      document.body.appendChild(panel);
      posisikan();
      panel.hidden = false;
      tombol.setAttribute('aria-expanded', 'true');
      bungkus.classList.add('sc-terbuka');
      masukan.value = '';
      saring();
      segarkan();
      var terpilih = item.find(function (li) { return li.dataset.value === sel.value; });
      if (terpilih) tandaiAktif(terpilih);
      masukan.focus();
    }

    function tutup() {
      panel.hidden = true;
      tombol.setAttribute('aria-expanded', 'false');
      bungkus.classList.remove('sc-terbuka');
      if (panel.parentNode === document.body) bungkus.appendChild(panel);
    }

    window.addEventListener('scroll', function (e) {
      if (!panel.hidden && !panel.contains(e.target)) posisikan();
    }, true);
    window.addEventListener('resize', function () {
      if (!panel.hidden) posisikan();
    });

    function pilih(li) {
      if (!li) return;
      sel.value = li.dataset.value;
      segarkan();
      tutup();
      tombol.focus();
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    tombol.addEventListener('click', function () { panel.hidden ? buka() : tutup(); });
    tombol.addEventListener('keydown', function (e) {
      if (['ArrowDown', 'Enter', ' '].includes(e.key) && panel.hidden) { e.preventDefault(); buka(); }
    });
    masukan.addEventListener('input', saring);
    masukan.addEventListener('keydown', function (e) {
      var vis = terlihat();
      var idx = vis.findIndex(function (li) { return li.classList.contains('aktif'); });
      if (e.key === 'ArrowDown') { e.preventDefault(); tandaiAktif(vis[Math.min(idx + 1, vis.length - 1)] || null); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); tandaiAktif(vis[Math.max(idx - 1, 0)] || null); }
      else if (e.key === 'Enter') { e.preventDefault(); pilih(vis[idx] || vis[0] || null); }
      else if (e.key === 'Escape') { tutup(); tombol.focus(); }
    });
    document.addEventListener('mousedown', function (e) {
      if (!panel.hidden && !bungkus.contains(e.target) && !panel.contains(e.target)) tutup();
    });
    sel.addEventListener('change', segarkan);
    segarkan();
  });
};
// Segarkan tampilan satu select yang nilainya/opsi diubah lewat JavaScript.
window.perbaruiSelectCari = function (sel) {
  if (sel && typeof sel._scPerbarui === 'function') sel._scPerbarui();
};

document.addEventListener('DOMContentLoaded', function () { window.inisiasiSelectCari(document); });

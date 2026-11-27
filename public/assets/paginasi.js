// Tabel Standar v1 — perilaku bersama semua tabel daftar (paginasi.js):
// pencarian langsung (data-cari), paginasi (data-paginasi), pengurutan
// klik-header yang sadar angka Indonesia/tanggal/teks, empty state,
// baris grup (judul bab) yang mengikuti baris anaknya, baris total yang
// selalu tampil, dan baris rincian yang tersinkron dengan pemiliknya.
// window.inisiasiPaginasi(akar) dapat dipanggil ulang untuk tabel yang
// baru dimuat lewat AJAX (pencarian langsung gaya Livewire).
window.inisiasiPaginasi = function (akar) {
  (akar || document).querySelectorAll('table[data-paginasi], table[data-cari]').forEach(function (tabel, nomorTabel) {
    if (tabel.dataset.sudahPaginasi) return;
    tabel.dataset.sudahPaginasi = '1';
    var semua = Array.prototype.slice.call(tabel.querySelectorAll('tr')).filter(function (tr) { return tr.closest('table') === tabel; });
    if (semua.length < 2) return;
    var kepala = semua[0];
    // Normalisasi struktur: baris kepala masuk <thead>, sisanya <tbody>,
    // agar header bisa sticky dan strukturnya baku di semua tabel.
    if (kepala.parentNode.nodeName !== 'THEAD') {
      var theadBaru = document.createElement('thead');
      tabel.insertBefore(theadBaru, kepala.parentNode);
      theadBaru.appendChild(kepala);
    }
    var wadahBaris = semua[1].parentNode; // tbody — baris dipindah di sini, bukan ke <table>
    // Header tempel (sticky) HANYA untuk tabel yang memang tinggi dan tidak
    // berada di dalam wadah bergulir mana pun: overflow auto/hidden/scroll
    // pada tabel atau leluhurnya menjadikannya wadah gulir sendiri sehingga
    // sticky tidak pernah menempel dan kepala tabel justru menutupi baris
    // (mis. .bungkus-tabel, .tabel-envelop, div overflow-x pada Survei/
    // Negosiasi). Offset mengikuti tinggi topbar yang sebenarnya.
    var dalamWadahGulir = false;
    for (var el = tabel; el && el !== document.body; el = el.parentElement) {
      var ov = getComputedStyle(el);
      if ([ov.overflowX, ov.overflowY].some(function (v) { return v === 'auto' || v === 'scroll' || v === 'hidden'; })) { dalamWadahGulir = true; break; }
    }
    if (!dalamWadahGulir && tabel.offsetHeight > window.innerHeight) {
      tabel.classList.add('kepala-tempel');
      var topbarEl = document.querySelector('.topbar');
      if (topbarEl) tabel.style.setProperty('--top-tempel', topbarEl.offsetHeight + 'px');
    }
    var jmlKolom = kepala.cells.length;

    function isTotal(tr) { return tr.classList.contains('baris-total') || tr.classList.contains('total'); }
    function isGrup(tr) {
      return tr.classList.contains('baris-grup')
        || (tr.cells.length === 1 && kepala.cells.length > 1 && tr.cells[0].colSpan >= kepala.cells.length);
    }

    // Susun segmen: [judul grup?] + unit (baris data + rincian opsional).
    var segmen = [];
    var totalRows = [];
    var seg = { header: null, units: [] };
    segmen.push(seg);
    var unitAkhir = null;
    semua.slice(1).forEach(function (tr) {
      if (isTotal(tr)) { totalRows.push(tr); return; }
      if (tr.classList.contains('baris-detail')) {
        if (unitAkhir) unitAkhir.rincian = tr;
        return;
      }
      if (isGrup(tr)) {
        seg = { header: tr, units: [] };
        segmen.push(seg);
        unitAkhir = null;
        return;
      }
      unitAkhir = { row: tr, rincian: null };
      seg.units.push(unitAkhir);
    });
    var semuaUnit = [];
    function segarkanSemuaUnit() {
      semuaUnit = [];
      segmen.forEach(function (s) { semuaUnit = semuaUnit.concat(s.units); });
    }
    segarkanSemuaUnit();

    var adaPaginasi = tabel.hasAttribute('data-paginasi');
    var ukuran = adaPaginasi ? (parseInt(tabel.getAttribute('data-paginasi'), 10) || 10) : 0;
    var halaman = 1;
    var kata = '';
    var kataTampil = '';
    var urutKolom = -1;
    var urutNaik = true;
    var kunciStatus = 'tabel-std:' + location.pathname + ':' + nomorTabel;
    function simpanStatus() {
      try {
        sessionStorage.setItem(kunciStatus, JSON.stringify({
          kata: kataTampil, halaman: halaman, ukuran: ukuran, urutKolom: urutKolom, urutNaik: urutNaik,
        }));
      } catch (e) { /* penyimpanan tidak tersedia */ }
    }
    function bacaStatus() {
      try { return JSON.parse(sessionStorage.getItem(kunciStatus) || 'null'); } catch (e) { return null; }
    }

    // Baris empty state (dibuat sekali, ditampilkan saat saringan kosong).
    var barisKosong = document.createElement('tr');
    barisKosong.className = 'baris-kosong';
    barisKosong.style.display = 'none';
    var selKosong = document.createElement('td');
    selKosong.colSpan = Math.max(1, jmlKolom);
    selKosong.textContent = 'Tidak ada baris yang cocok dengan pencarian.';
    barisKosong.appendChild(selKosong);
    wadahBaris.appendChild(barisKosong);

    function cocok(tr) {
      if (kata === '') return true;
      if (tr.textContent.toLowerCase().includes(kata)) return true;
      // Indeks cari tambahan dari backend (atribut data-cari-teks pada
      // baris): teks yang tidak tampil di sel — mis. nama komponen/bahan
      // di balik satu baris analisa — tetap bisa ditemukan pencarian,
      // tanpa mencemari pengurutan kolom dan ekspor CSV yang membaca
      // textContent sel.
      var ekstra = tr.getAttribute('data-cari-teks');
      return ekstra !== null && ekstra.toLowerCase().includes(kata);
    }

    // Kotak pencarian langsung (bila tabel bertanda data-cari).
    // Kotak cari & paginasi ditaruh DI LUAR wadah gulir tabel bila tabelnya
    // sudah dibungkus .bungkus-tabel di markup (mis. Analisa): kalau ikut di
    // dalam, keduanya tergulir mendatar bersama tabel dan selebar tabel.
    var bungkusMarkup = tabel.parentNode && tabel.parentNode.classList.contains('bungkus-tabel')
      ? tabel.parentNode : null;
    var indukKontrol = bungkusMarkup ? bungkusMarkup.parentNode : tabel.parentNode;
    var titikSisip = bungkusMarkup || tabel;
    if (tabel.hasAttribute('data-cari')) {
      var kotak = document.createElement('div');
      kotak.className = 'paginasi cari-tabel';
      var petunjuk = tabel.getAttribute('data-cari');
      var masukan = document.createElement('input');
      masukan.type = 'search';
      masukan.placeholder = petunjuk && petunjuk !== '1' ? petunjuk : 'Cari pada tabel ini…';
      masukan.style.cssText = 'flex:1;min-width:200px';
      masukan.setAttribute('aria-label', 'Cari pada tabel');
      kotak.appendChild(masukan);
      var btnCsv = document.createElement('button');
      btnCsv.type = 'button';
      btnCsv.className = 'tombol kecil ekspor';
      btnCsv.textContent = 'Ekspor CSV';
      btnCsv.title = 'Unduh baris yang sedang tersaring sebagai CSV';
      btnCsv.addEventListener('click', eksporCsv);
      kotak.appendChild(btnCsv);
      indukKontrol.insertBefore(kotak, titikSisip);
      masukan.addEventListener('input', function () {
        kataTampil = masukan.value;
        kata = masukan.value.trim().toLowerCase();
        halaman = 1;
        simpanStatus();
        gambar();
      });
    }

    // Pengalih halaman hanya untuk tabel besar bertanda data-paginasi.
    var wadah = null, info = null, tombol = null, pemilih = null;
    if (adaPaginasi && semuaUnit.length > 10) {
      wadah = document.createElement('div');
      wadah.className = 'paginasi';
      wadah.innerHTML =
        '<span class="info"></span>' +
        '<span class="tombol-halaman"></span>' +
        // Pengecualian AGENTS.md: select Per halaman SENGAJA select biasa,
        // bukan searchable — jangan tambahkan data-select-cari di sini.
        '<label>Per halaman <select data-native-select>' +
        [10, 25, 50, 100].map(function (n) { return '<option value="' + n + '"' + (n === ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
        '</select></label>';
      indukKontrol.insertBefore(wadah, titikSisip.nextSibling);
      info = wadah.querySelector('.info');
      info.setAttribute('aria-live', 'polite');
      tombol = wadah.querySelector('.tombol-halaman');
      pemilih = wadah.querySelector('select');
      tombol.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b || b.disabled) return;
        var h = b.getAttribute('data-h');
        if (h === 'prev') halaman--;
        else if (h === 'next') halaman++;
        else halaman = parseInt(h, 10);
        simpanStatus();
        gambar();
      });
      pemilih.addEventListener('change', function () { ukuran = parseInt(pemilih.value, 10); halaman = 1; simpanStatus(); gambar(); });
    }

    // --- Pengurutan klik-header ---
    function nilaiSel(td) {
      var t = (td ? td.textContent : '').trim();
      var md = t.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (md) return { j: 't', v: new Date(+md[1], +md[2] - 1, +md[3]).getTime() };
      md = t.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})/);
      if (md) return { j: 't', v: new Date(+md[3], +md[2] - 1, +md[1]).getTime() };
      var bersih = t.replace(/Rp\s*/g, '').replace(/%/g, '').trim();
      var mn = bersih.match(/^-?[\d.,]+/);
      if (mn && mn[0].length >= bersih.split(' ')[0].length) {
        var s = mn[0];
        if (s.includes('.') && s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
        else if (s.includes(',')) s = s.replace(',', '.');
        else if (/^\d{1,3}(\.\d{3})+$/.test(s)) s = s.replace(/\./g, '');
        var n = parseFloat(s);
        if (!isNaN(n)) return { j: 'n', v: n };
      }
      return { j: 's', v: t.toLowerCase() };
    }
    function banding(a, b) {
      if (a.j === 'n' && b.j === 'n') return a.v - b.v;
      if (a.j === 't' && b.j === 't') return a.v - b.v;
      return String(a.v).localeCompare(String(b.v), 'id', { numeric: true });
    }
    function terapkanUrut(idx, naik) {
      urutKolom = idx;
      urutNaik = naik;
      segmen.forEach(function (sg) {
        sg.units.sort(function (u1, u2) {
          var c = banding(nilaiSel(u1.row.cells[idx]), nilaiSel(u2.row.cells[idx]));
          return naik ? c : -c;
        });
      });
      segarkanSemuaUnit();
        // Terapkan urutan baru ke DOM per segmen; baris total tetap di akhir.
        segmen.forEach(function (s) {
          if (s.header) wadahBaris.appendChild(s.header);
          s.units.forEach(function (u) {
            wadahBaris.appendChild(u.row);
            if (u.rincian) wadahBaris.appendChild(u.rincian);
          });
        });
        totalRows.forEach(function (tr) { wadahBaris.appendChild(tr); });
        wadahBaris.appendChild(barisKosong);
        Array.prototype.forEach.call(kepala.cells, function (lain) {
          var p = lain.querySelector('.panah-urut');
          if (p) p.remove();
        });
        Array.prototype.forEach.call(kepala.cells, function (lain) {
          lain.removeAttribute('aria-sort');
        });
        var thAktif = kepala.cells[idx];
        thAktif.setAttribute('aria-sort', naik ? 'ascending' : 'descending');
        var panah = document.createElement('span');
        panah.className = 'panah-urut';
        panah.textContent = naik ? ' ▲' : ' ▼';
        thAktif.appendChild(panah);
      }
    Array.prototype.forEach.call(kepala.cells, function (th, idx) {
      if (th.hasAttribute('data-tanpa-urut')) return;
      if (th.querySelector('input, select, textarea, button')) return;
      if ((th.textContent || '').trim() === '') return;
      th.classList.add('bisa-urut');
      th.title = 'Klik untuk mengurutkan';
      th.addEventListener('click', function () {
        terapkanUrut(idx, urutKolom === idx ? !urutNaik : true);
        halaman = 1;
        simpanStatus();
        gambar();
      });
    });

    // Ekspor CSV: baris yang sedang tersaring, urutan saat ini, semua halaman.
    function eksporCsv() {
      var lewati = {};
      Array.prototype.forEach.call(kepala.cells, function (th, i) {
        if (th.querySelector('input, select, textarea, button') || (th.textContent || '').trim() === '') lewati[i] = 1;
      });
      var hasil = [];
      var judul = [];
      Array.prototype.forEach.call(kepala.cells, function (th, i) {
        if (!lewati[i]) judul.push((th.textContent || '').replace(/\s+/g, ' ').trim());
      });
      hasil.push(judul);
      semuaUnit.filter(function (u) { return cocok(u.row); }).forEach(function (u) {
        var r = [];
        Array.prototype.forEach.call(u.row.cells, function (td, i) {
          if (!lewati[i]) r.push((td.textContent || '').replace(/\s+/g, ' ').trim());
        });
        hasil.push(r);
      });
      var csv = '\ufeff' + hasil.map(function (r) {
        return r.map(function (x) { return '"' + String(x).replace(/"/g, '""') + '"'; }).join(';');
      }).join('\r\n');
      var a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
      a.download = 'ekspor-tabel.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
    }

    function gambar() {
      var tampil = semuaUnit.filter(function (u) { return cocok(u.row); });
      var terlihat;
      if (!wadah) {
        terlihat = new Set(tampil.map(function (u) { return u.row; }));
      } else {
        var total = Math.max(1, Math.ceil(tampil.length / ukuran));
        if (halaman > total) halaman = total;
        var awal = (halaman - 1) * ukuran;
        terlihat = new Set(tampil.slice(awal, awal + ukuran).map(function (u) { return u.row; }));
        info.textContent = 'Menampilkan ' + (tampil.length ? awal + 1 : 0) + '–' + Math.min(awal + ukuran, tampil.length) + ' dari ' + tampil.length + ' baris' + (kata ? ' (tersaring)' : '');
        var html = '<button type="button" data-h="prev"' + (halaman === 1 ? ' disabled' : '') + '>&lsaquo;</button>';
        var daftar = [];
        for (var p = 1; p <= total; p++) {
          if (p === 1 || p === total || Math.abs(p - halaman) <= 2) daftar.push(p);
          else if (daftar[daftar.length - 1] !== '…') daftar.push('…');
        }
        daftar.forEach(function (p) {
          if (p === '…') html += '<span style="padding:0 4px">…</span>';
          else html += '<button type="button" data-h="' + p + '"' + (p === halaman ? ' class="aktif"' : '') + '>' + p + '</button>';
        });
        html += '<button type="button" data-h="next"' + (halaman === total ? ' disabled' : '') + '>&rsaquo;</button>';
        tombol.innerHTML = html;
      }
      semuaUnit.forEach(function (u) {
        var t = terlihat.has(u.row);
        u.row.style.display = t ? '' : 'none';
        if (u.rincian) {
          u.rincian.style.display = t && u.rincian.classList.contains('terbuka') ? '' : 'none';
        }
      });
      segmen.forEach(function (s) {
        if (!s.header) return;
        s.header.style.display = s.units.some(function (u) { return terlihat.has(u.row); }) ? '' : 'none';
      });
      totalRows.forEach(function (tr) { tr.style.display = ''; });
      barisKosong.style.display = kata !== '' && tampil.length === 0 ? '' : 'none';
    }

    // Pulihkan status tabel (kata cari, urutan, ukuran, halaman) sesi ini.
    var statusLama = bacaStatus();
    if (statusLama) {
      if (statusLama.ukuran && pemilih) {
        ukuran = parseInt(statusLama.ukuran, 10) || ukuran;
        pemilih.value = String(ukuran);
      }
      if (statusLama.urutKolom >= 0) terapkanUrut(statusLama.urutKolom, statusLama.urutNaik !== false);
      if (statusLama.kata) {
        kataTampil = String(statusLama.kata);
        kata = kataTampil.trim().toLowerCase();
        var kotakInput = indukKontrol.querySelector('.cari-tabel input');
        if (kotakInput) kotakInput.value = kataTampil;
      }
      halaman = parseInt(statusLama.halaman, 10) || 1;
    }

    // Tabel yang lebih lebar dari panelnya dibungkus wadah gulir mendatar
    // (.bungkus-tabel) agar kolom paling kanan tetap dapat dicapai. Wadah
    // dibuat SETELAH kotak cari & paginasi disisipkan supaya keduanya tidak
    // ikut tergulir. Konsekuensi yang diterima: di dalam wadah bergulir
    // kepala tabel tempel tidak berlaku, jadi kelas kepala-tempel dicabut.
    // Pengamat ukuran menangani tabel di dalam <details> yang baru dibuka
    // (saat inisialisasi lebarnya masih 0).
    var indukTabel = tabel.parentElement;
    var wadahGulir = null;
    function sudahBisaDigulir() {
      for (var el = indukTabel; el && el !== document.body; el = el.parentElement) {
        var ox = getComputedStyle(el).overflowX;
        if (ox === 'auto' || ox === 'scroll') return true;
      }
      return false;
    }
    function bungkusGulir() {
      if (wadahGulir || !indukTabel || sudahBisaDigulir()) return;
      var gaya = getComputedStyle(indukTabel);
      var tersedia = indukTabel.clientWidth - parseFloat(gaya.paddingLeft) - parseFloat(gaya.paddingRight);
      if (!(tersedia > 0) || tabel.scrollWidth <= tersedia + 1) return;
      wadahGulir = document.createElement('div');
      wadahGulir.className = 'bungkus-tabel';
      indukTabel.insertBefore(wadahGulir, tabel);
      wadahGulir.appendChild(tabel);
      tabel.classList.remove('kepala-tempel');
      tabel.style.removeProperty('--top-tempel');
    }
    bungkusGulir();
    if (window.ResizeObserver) {
      new ResizeObserver(bungkusGulir).observe(tabel);
    }

    gambar();
  });
};
document.addEventListener('DOMContentLoaded', function () { window.inisiasiPaginasi(document); });

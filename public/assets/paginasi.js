// Tabel Standar v1 — perilaku bersama semua tabel daftar (paginasi.js):
// pencarian langsung (data-cari), paginasi (data-paginasi), pengurutan
// klik-header yang sadar angka Indonesia/tanggal/teks, empty state,
// baris grup (judul bab) yang mengikuti baris anaknya, baris total yang
// selalu tampil, dan baris rincian yang tersinkron dengan pemiliknya.
// window.inisiasiPaginasi(akar) dapat dipanggil ulang untuk tabel yang
// baru dimuat lewat AJAX (pencarian langsung gaya Livewire).
window.inisiasiPaginasi = function (akar) {
  (akar || document).querySelectorAll('table[data-paginasi], table[data-cari]').forEach(function (tabel) {
    if (tabel.dataset.sudahPaginasi) return;
    tabel.dataset.sudahPaginasi = '1';
    var semua = Array.prototype.slice.call(tabel.querySelectorAll('tr')).filter(function (tr) { return tr.closest('table') === tabel; });
    if (semua.length < 2) return;
    var kepala = semua[0];
    var wadahBaris = semua[1].parentNode; // tbody (implisit) — baris dipindah di sini, bukan ke <table>
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
    segmen.forEach(function (s) { semuaUnit = semuaUnit.concat(s.units); });

    var adaPaginasi = tabel.hasAttribute('data-paginasi');
    var ukuran = adaPaginasi ? (parseInt(tabel.getAttribute('data-paginasi'), 10) || 10) : 0;
    var halaman = 1;
    var kata = '';
    var urutKolom = -1;
    var urutNaik = true;

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
      return kata === '' || tr.textContent.toLowerCase().includes(kata);
    }

    // Kotak pencarian langsung (bila tabel bertanda data-cari).
    if (tabel.hasAttribute('data-cari')) {
      var kotak = document.createElement('div');
      kotak.className = 'paginasi cari-tabel';
      var petunjuk = tabel.getAttribute('data-cari');
      var masukan = document.createElement('input');
      masukan.type = 'search';
      masukan.placeholder = petunjuk && petunjuk !== '1' ? petunjuk : 'Cari pada tabel ini…';
      masukan.style.cssText = 'flex:1;min-width:200px';
      kotak.appendChild(masukan);
      tabel.parentNode.insertBefore(kotak, tabel);
      masukan.addEventListener('input', function () {
        kata = masukan.value.trim().toLowerCase();
        halaman = 1;
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
        '<label>Per halaman <select>' +
        [10, 25, 50, 100].map(function (n) { return '<option value="' + n + '"' + (n === ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
        '</select></label>';
      tabel.parentNode.insertBefore(wadah, tabel.nextSibling);
      info = wadah.querySelector('.info');
      tombol = wadah.querySelector('.tombol-halaman');
      pemilih = wadah.querySelector('select');
      tombol.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b || b.disabled) return;
        var h = b.getAttribute('data-h');
        if (h === 'prev') halaman--;
        else if (h === 'next') halaman++;
        else halaman = parseInt(h, 10);
        gambar();
      });
      pemilih.addEventListener('change', function () { ukuran = parseInt(pemilih.value, 10); halaman = 1; gambar(); });
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
    Array.prototype.forEach.call(kepala.cells, function (th, idx) {
      if (th.hasAttribute('data-tanpa-urut')) return;
      if (th.querySelector('input, select, textarea, button')) return;
      if ((th.textContent || '').trim() === '') return;
      th.classList.add('bisa-urut');
      th.title = 'Klik untuk mengurutkan';
      th.addEventListener('click', function () {
        if (urutKolom === idx) urutNaik = !urutNaik;
        else { urutKolom = idx; urutNaik = true; }
        segmen.forEach(function (s) {
          s.units.sort(function (u1, u2) {
            var c = banding(nilaiSel(u1.row.cells[idx]), nilaiSel(u2.row.cells[idx]));
            return urutNaik ? c : -c;
          });
        });
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
        var panah = document.createElement('span');
        panah.className = 'panah-urut';
        panah.textContent = urutNaik ? ' ▲' : ' ▼';
        th.appendChild(panah);
        halaman = 1;
        gambar();
      });
    });

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

    gambar();
  });
};
document.addEventListener('DOMContentLoaded', function () { window.inisiasiPaginasi(document); });

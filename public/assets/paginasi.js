// Paginasi + pencarian langsung tabel sisi klien.
// - Tabel bertanda data-paginasi="ukuran awal" mendapat pengalih halaman.
// - Tabel bertanda data-cari mendapat kotak pencarian langsung di atasnya:
//   ketik untuk menyaring baris seketika (teks seluruh baris dicocokkan).
// Baris pertama dianggap header; baris .baris-total selalu tampil dan tidak dihitung.
// window.inisiasiPaginasi(akar) dapat dipanggil ulang untuk tabel yang baru
// dimuat lewat AJAX (pencarian langsung gaya Livewire).
window.inisiasiPaginasi = function (akar) {
  (akar || document).querySelectorAll('table[data-paginasi], table[data-cari]').forEach(function (tabel) {
    if (tabel.dataset.sudahPaginasi) return;
    tabel.dataset.sudahPaginasi = '1';
    var semua = Array.prototype.slice.call(tabel.querySelectorAll('tr'));
    if (semua.length < 2) return;
    var data = semua.slice(1).filter(function (tr) { return !tr.classList.contains('baris-total'); });
    var adaPaginasi = tabel.hasAttribute('data-paginasi');
    var ukuran = adaPaginasi ? (parseInt(tabel.getAttribute('data-paginasi'), 10) || 10) : 0;
    var halaman = 1;
    var kata = '';

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
    if (adaPaginasi && data.length > 10) {
      wadah = document.createElement('div');
      wadah.className = 'paginasi';
      wadah.innerHTML =
        '<span class="info"></span>' +
        '<span class="tombol-halaman"></span>' +
        '<label>Per halaman <select data-select-cari>' +
        [10, 25, 50, 100].map(function (n) { return '<option value="' + n + '"' + (n === ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
        '</select></label>';
      tabel.parentNode.insertBefore(wadah, tabel.nextSibling);
      if (window.inisiasiSelectCari) window.inisiasiSelectCari(wadah);
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

    function gambar() {
      var tampil = data.filter(cocok);
      if (!wadah) {
        data.forEach(function (tr) { tr.style.display = cocok(tr) ? '' : 'none'; });
        return;
      }
      var total = Math.max(1, Math.ceil(tampil.length / ukuran));
      if (halaman > total) halaman = total;
      var awal = (halaman - 1) * ukuran;
      var terlihat = new Set(tampil.slice(awal, awal + ukuran));
      data.forEach(function (tr) { tr.style.display = terlihat.has(tr) ? '' : 'none'; });
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

    gambar();
  });
};
document.addEventListener('DOMContentLoaded', function () { window.inisiasiPaginasi(document); });

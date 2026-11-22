// Paginasi tabel sisi klien — untuk tabel bertanda data-paginasi="ukuran awal".
// Baris pertama dianggap header; baris .baris-total selalu tampil dan tidak dihitung.
// window.inisiasiPaginasi(akar) dapat dipanggil ulang untuk tabel yang baru
// dimuat lewat AJAX (pencarian langsung gaya Livewire).
window.inisiasiPaginasi = function (akar) {
  (akar || document).querySelectorAll('table[data-paginasi]').forEach(function (tabel) {
    if (tabel.dataset.sudahPaginasi) return;
    tabel.dataset.sudahPaginasi = '1';
    var semua = Array.prototype.slice.call(tabel.querySelectorAll('tr'));
    if (semua.length < 2) return;
    var data = semua.slice(1).filter(function (tr) { return !tr.classList.contains('baris-total'); });
    var ukuran = parseInt(tabel.getAttribute('data-paginasi'), 10) || 10;
    if (data.length <= 10) return;
    var halaman = 1;

    var wadah = document.createElement('div');
    wadah.className = 'paginasi';
    wadah.innerHTML =
      '<span class="info"></span>' +
      '<span class="tombol-halaman"></span>' +
      '<label>Per halaman <select>' +
      [10, 25, 50, 100].map(function (n) { return '<option value="' + n + '"' + (n === ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
      '</select></label>';
    tabel.parentNode.insertBefore(wadah, tabel.nextSibling);
    var info = wadah.querySelector('.info');
    var tombol = wadah.querySelector('.tombol-halaman');
    var pemilih = wadah.querySelector('select');

    function jumlahHalaman() { return Math.max(1, Math.ceil(data.length / ukuran)); }

    function gambar() {
      var total = jumlahHalaman();
      if (halaman > total) halaman = total;
      var awal = (halaman - 1) * ukuran;
      data.forEach(function (tr, i) { tr.style.display = (i >= awal && i < awal + ukuran) ? '' : 'none'; });
      info.textContent = 'Menampilkan ' + (data.length ? awal + 1 : 0) + '–' + Math.min(awal + ukuran, data.length) + ' dari ' + data.length + ' baris';
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
    gambar();
  });
};
document.addEventListener('DOMContentLoaded', function () { window.inisiasiPaginasi(document); });

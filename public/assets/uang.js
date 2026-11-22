// Pola field uang (atribut data-uang pada input harga/rupiah):
// nilai ditampilkan dengan pemisah ribuan Indonesia (1.500.000) saat
// mengetik dan saat halaman dimuat; sebelum formulir terkirim, nilai
// dikembalikan ke angka polos agar semua pola parsing server menerima
// angka yang sama. Hanya rupiah bulat (tanpa sen).
window.formatUang = function (nilai) {
  var angka = String(nilai == null ? '' : nilai).replace(/[^\d]/g, '');
  if (angka === '') return '';
  angka = angka.replace(/^0+(?=\d)/, '');
  return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};
window.inisiasiUang = function (akar) {
  (akar || document).querySelectorAll('input[data-uang]').forEach(function (inp) {
    if (inp.value) inp.value = window.formatUang(inp.value);
  });
};
document.addEventListener('input', function (e) {
  var inp = e.target;
  if (!inp.matches || !inp.matches('input[data-uang]')) return;
  var sebelum = inp.value;
  var pos = inp.selectionStart || sebelum.length;
  var digitSebelum = sebelum.slice(0, pos).replace(/[^\d]/g, '').length;
  inp.value = window.formatUang(sebelum);
  var idx = 0, hitung = 0;
  while (idx < inp.value.length && hitung < digitSebelum) {
    if (/\d/.test(inp.value[idx])) hitung++;
    idx++;
  }
  try { inp.setSelectionRange(idx, idx); } catch (err) { /* abaikan */ }
});
document.addEventListener('submit', function (e) {
  if (!e.target.querySelectorAll) return;
  e.target.querySelectorAll('input[data-uang]').forEach(function (inp) {
    inp.value = inp.value.replace(/[^\d]/g, '');
  });
}, true);
document.addEventListener('DOMContentLoaded', function () { window.inisiasiUang(document); });

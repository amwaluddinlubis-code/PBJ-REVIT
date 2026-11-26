<?php /* Partial spk/detail: skrip halaman. */ ?><script>
document.getElementById('pilihBahan').addEventListener('change', function () {
  var opt = this.options[this.selectedIndex];
  var h = opt.getAttribute('data-harga');
  document.getElementById('hargaIsi').placeholder = h ? ('acuan: ' + Number(h).toLocaleString('id-ID')) : 'otomatis';
  // Qty bawaan = sisa kebutuhan bahan untuk gedung SPK (boleh diubah).
  var sisa = opt.getAttribute('data-sisa');
  var qty = document.getElementById('qtyIsi');
  if (qty && sisa !== null && sisa !== '') qty.value = sisa;
});
</script>

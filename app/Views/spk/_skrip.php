<?php /* Partial spk/detail: skrip halaman. */ ?><script>
document.getElementById('pilihBahan').addEventListener('change', function () {
  var h = this.options[this.selectedIndex].getAttribute('data-harga');
  document.getElementById('hargaIsi').placeholder = h ? ('acuan: ' + Number(h).toLocaleString('id-ID')) : 'otomatis';
});
</script>

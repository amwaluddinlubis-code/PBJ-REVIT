<?php /* Partial analisa/detail: skrip jembatan modal helper form_modal() — isi otomatis bahan master, pratinjau nilai langsung, keterangan baris Ubah, dan tautan dalam #komponen-{id}. Buka/tutup/kirim/segarkan ditangani form-modal.js. */ ?><script>
(function () {
  var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
  var angka = function (s) {
    s = String(s || '').trim();
    if (!s) return 0;
    if (s.indexOf(',') >= 0) s = s.replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
  };
  var uang = function (s) { return parseFloat(String(s || '').replace(/[^0-9]/g, '')) || 0; };
  var modalTambah = document.getElementById('modal-tambah-komp');
  var modalUbah = document.getElementById('modal-ubah-komp');
  function field(modal, nama) { return modal ? modal.querySelector('[name="' + nama + '"]') : null; }
  function nilaiTambah() {
    document.getElementById('nilai-tambah').textContent = rp(angka(field(modalTambah, 'koefisien').value) * uang(field(modalTambah, 'tarif').value));
  }
  function nilaiUbah() {
    document.getElementById('nilai-ubah').textContent = rp(angka(field(modalUbah, 'koefisien').value) * uang(field(modalUbah, 'tarif').value));
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('#btn-tambah-komp')) {
      window.bukaFormModal('modal-tambah-komp');
      return;
    }
    var u = e.target.closest('[data-ubah-komp]');
    if (!u) return;
    document.getElementById('fu-jenis').textContent = u.dataset.jenis;
    document.getElementById('fu-nama').textContent = u.dataset.nama;
    var pa = document.getElementById('fu-acuan');
    if (u.dataset.koefAcuan) {
      document.getElementById('fu-koef-acuan').textContent = u.dataset.koefAcuan + ' ' + (u.dataset.satuan || '');
      pa.hidden = false;
    } else {
      pa.hidden = true;
    }
    modalUbah.dataset.konfirmasiHapus = 'Hapus komponen ' + u.dataset.nama + ' dari analisa ini?';
    window.bukaFormModal('modal-ubah-komp', {
      id: u.dataset.id, komponen_id: u.dataset.id,
      koefisien: u.dataset.koefisien, satuan: u.dataset.satuan, tarif: u.dataset.tarif,
    });
    nilaiUbah();
  });
  // Isi otomatis: memilih bahan master mengisi nama, satuan, tarif acuan.
  var selBahan = field(modalTambah, 'bahan_id');
  if (selBahan) selBahan.addEventListener('change', function () {
    var op = selBahan.options[selBahan.selectedIndex];
    if (!op || !selBahan.value) return;
    var jenis = field(modalTambah, 'jenis');
    if (jenis && jenis.value !== 'bahan') {
      jenis.value = 'bahan';
      jenis.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (op.dataset.nama) field(modalTambah, 'nama').value = op.dataset.nama;
    if (op.dataset.satuan) field(modalTambah, 'satuan').value = op.dataset.satuan;
    if (op.dataset.tarif) {
      field(modalTambah, 'tarif').value = op.dataset.tarif;
      if (window.inisiasiUang) window.inisiasiUang(modalTambah);
      nilaiTambah();
    }
  });
  ['koefisien', 'tarif'].forEach(function (n) {
    field(modalTambah, n).addEventListener('input', nilaiTambah);
    field(modalUbah, n).addEventListener('input', nilaiUbah);
  });
  // Tautan dalam dari panel peringatan: #komponen-{id} membuka modal Ubah.
  // Ditunda sampai 'load' agar bukaFormModal (form-modal.js dari layout)
  // pasti sudah terdefinisi.
  function bukaDariHash() {
    var cocok = location.hash.match(/^#komponen-(\d+)$/);
    if (!cocok) return;
    var b = document.querySelector('[data-ubah-komp][data-id="' + cocok[1] + '"]');
    if (b) b.click();
  }
  if (document.readyState === 'complete') bukaDariHash();
  else window.addEventListener('load', bukaDariHash);
})();
</script>

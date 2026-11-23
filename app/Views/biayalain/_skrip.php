<?php /* Partial biayalain/index: skrip halaman (modal, combobox kelompok, format uang modal). */ ?><script>
var modalBiaya = document.getElementById('modal-biaya');
var pemicuModal = null;

function bukaModalTambah(btn) {
  pemicuModal = btn || null;
  document.getElementById('form-biaya-modal').reset();
  document.getElementById('f-id-modal').value = '';
  document.getElementById('judul-modal').textContent = 'Tambah Baris Biaya';
  document.getElementById('tombol-simpan-modal').textContent = 'Simpan Baris';
  modalBiaya.hidden = false;
  const el = document.getElementById('f-kelompok-modal');
  if (el) el.focus();
}

function isiForm(d, btn) {
  pemicuModal = btn || null;
  document.getElementById('f-id-modal').value = d.id;
  document.getElementById('f-kelompok-modal').value = d.kelompok;
  document.getElementById('f-uraian-modal').value = d.uraian;
  document.getElementById('f-volume-modal').value = d.volume;
  document.getElementById('f-satuan-modal').value = d.satuan;
  document.getElementById('f-periode-modal').value = d.periode;
  document.getElementById('f-satuan-periode-modal').value = d.satuan_periode;
  document.getElementById('f-harga-modal').value = d.harga;
  document.getElementById('f-keterangan-modal').value = d.keterangan;
  document.getElementById('f-pajak-modal').value = d.pajak || '';
  document.getElementById('judul-modal').textContent = 'Ubah Baris Biaya';
  document.getElementById('tombol-simpan-modal').textContent = 'Simpan Perubahan';
  modalBiaya.hidden = false;
}

function tutupModal() {
  modalBiaya.hidden = true;
  document.getElementById('form-biaya-modal').reset();
  document.getElementById('f-id-modal').value = '';
  document.getElementById('judul-modal').textContent = 'Tambah Baris Biaya';
  document.getElementById('tombol-simpan-modal').textContent = 'Simpan Baris';
  if (pemicuModal) {
    pemicuModal.focus();
    pemicuModal = null;
  }
}

modalBiaya.addEventListener('click', function (e) {
  if (e.target === modalBiaya) tutupModal();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && !modalBiaya.hidden) tutupModal();
});

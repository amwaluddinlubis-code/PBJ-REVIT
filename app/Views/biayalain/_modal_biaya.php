<?php /* Partial biayalain/index: modal Tambah/Ubah kini dirender helper form_modal() (app/Common.php) — cukup konfigurasi; perilaku di public/assets/form-modal.js. */ ?>
<?= form_modal([
    'id' => 'modal-biaya',
    'judul' => 'Tambah Baris Biaya',
    'judul_ubah' => 'Ubah Baris Biaya',
    'aksi' => base_url('biaya-non-gedung/simpan'),
    'aksi_hapus' => base_url('biaya-non-gedung/hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus baris biaya ini?',
    'tersembunyi' => ['sumber'],
    'info_html' => '<p class="kecil-teks" id="info-sumber-impor" hidden style="margin:0 0 12px;padding:8px 10px;border:1px solid var(--ta-border);border-radius:8px">Baris ini <strong>berasal dari impor</strong> dokumen perencana — impor ulang akan menggantinya dengan data dokumen terbaru.</p>',
    'kolom' => [
        ['nama' => 'kelompok', 'label' => 'Kelompok', 'wajib' => true, 'placeholder' => 'cth. Biaya Pengelolaan',
            'petunjuk' => 'cth. Perencanaan & Pengawasan, Biaya Pengelolaan',
            'saran' => array_values(array_unique(array_merge($saran_kelompok ?? [], array_keys($kelompok ?? []))))],
        ['nama' => 'uraian', 'label' => 'Uraian', 'wajib' => true, 'placeholder' => 'cth. Honor P2SP — Ketua'],
        ['nama' => 'volume', 'label' => 'Volume', 'wajib' => true, 'placeholder' => '1', 'petunjuk' => 'Jumlah satuan pekerjaan'],
        ['nama' => 'satuan', 'label' => 'Satuan Volume', 'placeholder' => 'orang / set / unit'],
        ['nama' => 'periode', 'label' => 'Periode', 'placeholder' => '4', 'petunjuk' => 'Kosongkan bila tidak ada periode'],
        ['nama' => 'satuan_periode', 'label' => 'Satuan Periode', 'placeholder' => 'bln / minggu / kali'],
        ['nama' => 'harga_satuan', 'label' => 'Harga Satuan (Rp)', 'jenis' => 'uang', 'wajib' => true, 'placeholder' => '750000'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'placeholder' => 'cth. 1,65% dari nilai konstruksi'],
        ['nama' => 'pajak', 'label' => 'Pajak Dipotong Saat Pembayaran', 'jenis' => 'pilihan', 'opsi' => $opsi_pajak ?? []],
    ],
    'catatan' => 'Jumlah dihitung otomatis: volume × periode (bila ada) × harga satuan. Potongan pajak mengikuti Juknis Revitalisasi SMP 2026: PPh 23 sebesar 2% untuk jasa/sewa, PPh 4 ayat 2 sebesar 3,5% (ber-SKA) atau 6% (tanpa SKA) untuk perencana/pengawas, dan PPh 21 untuk honor menurut golongan penerima.',
    'tombol' => ['simpan_teks' => 'Simpan Baris', 'simpan_teks_ubah' => 'Simpan Perubahan'],
]) ?>
<script>
// Penanda sumber impor di modal Ubah: tampilkan keterangan bila baris
// yang diubah berasal dari impor dokumen perencana.
(function () {
  var modal = document.getElementById('modal-biaya');
  var info = document.getElementById('info-sumber-impor');
  if (!modal || !info) return;
  function segarkan() {
    var sumber = modal.querySelector('[name="sumber"]');
    info.hidden = !(sumber && sumber.value === 'impor');
  }
  new MutationObserver(segarkan).observe(modal, { attributes: true, attributeFilter: ['hidden'] });
  segarkan();
})();
</script>

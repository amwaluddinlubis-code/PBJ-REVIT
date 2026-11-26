<?php /* Partial spk/detail: modal Ubah SPK (kepala SPK lengkap) — helper form_modal(); simpan ke Spk::perbarui (AJAX JSON). */ ?>
<?php
$opsiPenyedia = ['' => '— Masyarakat / nama manual di bawah —'];
foreach ($penyedia_list ?? [] as $p) {
    $opsiPenyedia[(int) $p['id']] = ['label' => (string) $p['nama'], 'data' => ['ttd' => (string) ($p['ttd_nama'] ?? '')]];
}
$opsiGedung = ['' => '— Gabungan / belum ditentukan —'];
foreach ($gedung_list ?? [] as $g) {
    $opsiGedung[(int) $g['id']] = (string) $g['nama'];
}
?>
<?= form_modal([
    'id' => 'modal-ubah-spk',
    'judul' => 'Ubah SPK',
    'judul_ubah' => 'Ubah SPK',
    'aksi' => base_url('spk/perbarui/' . $s['id']),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'nomor', 'label' => 'Nomor SPK', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'status', 'label' => 'Status', 'jenis' => 'pilihan', 'opsi' => ['Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Batal' => 'Batal']],
        ['nama' => 'paket', 'label' => 'Nama Paket', 'penuh' => true],
        ['nama' => 'penyedia_id', 'label' => 'Penyedia (dari daftar)', 'jenis' => 'pilihan', 'opsi' => $opsiPenyedia],
        ['nama' => 'penerima_manual', 'label' => 'Nama Penerima Manual (masyarakat)', 'petunjuk' => 'Diisi bila penyedianya perorangan/masyarakat, bukan penyedia terdaftar.'],
        ['nama' => 'penyedia_baru', 'label' => 'Penyedia Baru (bila belum ada di daftar)', 'petunjuk' => 'Diisi hanya untuk membuat penyedia baru; otomatis menjadi penyedia SPK ini.'],
        ['nama' => 'ttd_penyedia', 'label' => 'Nama Penanda Tangan Penyedia'],
        ['nama' => 'tanggal', 'label' => 'Tanggal SPK', 'jenis' => 'tanggal'],
        ['nama' => 'tgl_mulai', 'label' => 'Tanggal Mulai', 'jenis' => 'tanggal'],
        ['nama' => 'tgl_selesai', 'label' => 'Tanggal Selesai', 'jenis' => 'tanggal'],
        ['nama' => 'gedung_id', 'label' => 'Gedung Tujuan', 'jenis' => 'pilihan', 'opsi' => $opsiGedung],
    ],
    'tombol' => ['simpan_teks' => 'Simpan Perubahan', 'hapus' => false],
]) ?>
<script>
// Warisan modal Ubah SPK: penyedia → penandatangan master; tanggal SPK →
// tanggal mulai bila masih kosong.
document.addEventListener('change', function (e) {
  if (!e.target || !e.target.closest || !e.target.closest('#modal-ubah-spk')) return;
  var form = e.target.closest('form');
  if (e.target.name === 'penyedia_id') {
    var opt = e.target.options[e.target.selectedIndex];
    var ttd = opt ? opt.getAttribute('data-ttd') : '';
    var inp = form.querySelector('[name="ttd_penyedia"]');
    if (inp && ttd && !inp.value) {
      inp.value = ttd;
      inp.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }
  if (e.target.name === 'tanggal') {
    var mulai = form.querySelector('[name="tgl_mulai"]');
    if (mulai && e.target.value && !mulai.value) {
      mulai.value = e.target.value;
      mulai.dispatchEvent(new Event('input', { bubbles: true }));
      mulai.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }
});
</script>

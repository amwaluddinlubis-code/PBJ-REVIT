<?php /* Kelola Sekolah: tabel helper tabel_bantu + modal form_modal
   (partial sekolah/_modal_form). Baris tebal = sekolah aktif. */ ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaFormModal('modal-sekolah')">+ Tambah Sekolah</button></div>
<div class="panel">
  <p>Setiap sekolah mempunyai data sendiri yang terpisah penuh: bahan/RAB, penyedia, SPK, survei, invoice, dan buku material. Yang tampil di seluruh aplikasi adalah <strong>sekolah aktif</strong> — ganti lewat daftar ini atau pengalih di menu samping.</p>
<?php
$barisSekolah = [];
foreach ($daftar as $s) {
    $aktif = (int) ($sekolahAktif['id'] ?? 0) === (int) $s['id'];
    $aksi = (! $aktif ? '<a href="' . base_url('sekolah/pilih/' . $s['id']) . '">Jadikan Aktif</a> · ' : '')
        . '<form class="inline" method="post" action="' . base_url('sekolah/hapus/' . $s['id']) . '" data-konfirmasi="Hapus sekolah kosong ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>';
    $barisSekolah[] = [
        'atribut' => $aktif ? 'style="font-weight:bold"' : '',
        'sel' => [
            (string) ($s['nama_sekolah'] ?? ''),
            (string) ($s['npsn'] ?? '—'),
            (string) ($s['kecamatan'] ?? '—'),
            (string) ($s['tahun_anggaran'] ?? '—'),
            ['teks' => (string) (int) $s['jumlah_bahan'], 'angka' => true],
            ['teks' => (string) (int) $s['jumlah_spk'], 'angka' => true],
            $aktif ? 'Aktif sekarang' : '',
            ['html' => $aksi],
        ],
    ];
}
echo tabel_bantu([
    'cari' => 'Cari sekolah…',
    'paginasi' => 10,
    'kolom' => [
        ['judul' => 'Sekolah'],
        ['judul' => 'NPSN'],
        ['judul' => 'Kecamatan'],
        ['judul' => 'Tahun Anggaran'],
        ['judul' => 'Bahan', 'angka' => true],
        ['judul' => 'SPK', 'angka' => true],
        ['judul' => 'Status'],
        ['judul' => ''],
    ],
    'baris' => $barisSekolah,
]);
?>
</div>
<?= $this->include('sekolah/_modal_form') ?>

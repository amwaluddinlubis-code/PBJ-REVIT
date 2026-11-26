<?php /* Register SPK: tabel helper tabel_bantu + modal form_modal
   (partial spk/_modal_form). */ ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaSpkBaru()">+ SPK Baru</button></div>
<?php if (! empty($cadangan)): ?>
<div class="kartu-kpi ringkas" style="margin:0 0 12px">
  <div class="kpi"><div class="label">Total Anggaran</div><div class="nilai"><?= rp($cadangan['anggaran']) ?></div></div>
  <div class="kpi"><div class="label">Sudah Terbayar</div><div class="nilai"><?= rp($cadangan['terbayar']) ?></div></div>
  <div class="kpi"><div class="label">Pencadangan SPK Aktif</div><div class="nilai"><?= rp($cadangan['komitmen']) ?></div></div>
  <div class="kpi"><div class="label">Pagu Tersedia</div><div class="nilai"><?= rp($cadangan['tersedia']) ?></div></div>
</div>
<?php endif; ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-spk','ids-spk')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-spk" method="post" action="<?= base_url('spk/hapus-massal') ?>" data-konfirmasi-judul="Hapus SPK Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-spk"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} SPK terpilih? Rincian barangnya menjadi belum bernomor.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('spk/kosongkan') ?>" data-konfirmasi="Kosongkan register SPK sekolah ini? Semua <?= count($spk) ?> SPK aktif akan dihapus (hapus lunak) dan rincian barangnya menjadi belum bernomor." data-konfirmasi-judul="Kosongkan Register SPK" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Register SPK</button>
    </form>
  </div>
<?php
$barisSpk = [];
foreach ($spk as $s) {
    $barisSpk[] = ['sel' => [
        ['html' => '<input type="checkbox" data-pilih value="' . (int) $s['id'] . '" x-model="terpilih">'],
        ['html' => '<a href="' . base_url('spk/detail/' . $s['id']) . '">' . (int) $s['nomor'] . '</a>'],
        (string) tgl($s['tanggal'] ?? null),
        (string) ($s['penyedia'] ?? '—'),
        (string) ($s['paket'] ?? '—'),
        (string) (tgl($s['tgl_mulai'] ?? null) . ' s.d. ' . tgl($s['tgl_selesai'] ?? null)),
        ['teks' => (string) (int) $s['jumlah'], 'angka' => true],
        ['teks' => rp($s['nilai']), 'angka' => true],
        (string) $s['status'],
        ['html' => '<button type="button" class="tombol kecil abu" onclick="bukaSpkUbah(' . (int) $s['id'] . ')">Ubah</button> <a class="tombol kecil" href="' . base_url('spk/detail/' . $s['id']) . '">Detail</a>'],
    ]];
}
echo tabel_bantu([
    'cari' => 'Cari SPK (nomor, penyedia, paket)…',
    'paginasi' => 10,
    'kolom' => [
        ['judul_html' => '<input type="checkbox" title="Pilih semua SPK" @change="pilihSemua($event)">', 'lebar' => '34px'],
        ['judul' => 'No.'],
        ['judul' => 'Tanggal'],
        ['judul' => 'Penyedia'],
        ['judul' => 'Paket'],
        ['judul' => 'Periode'],
        ['judul' => 'Jml Bahan', 'angka' => true],
        ['judul' => 'Nilai SPK', 'angka' => true],
        ['judul' => 'Status'],
        ['judul' => ''],
    ],
    'baris' => $barisSpk,
]);
?>
</div>
<?= $this->include('spk/_modal_form') ?>

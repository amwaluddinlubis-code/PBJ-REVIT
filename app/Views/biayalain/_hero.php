<?php /* Partial biayalain/index: panel hero KPI Biaya Non-Gedung (di dalam akar bulk). */ ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>
<?php
$selisih = (float) ($verifikasi['selisih'] ?? 0);
$totalKeseluruhan = (float) ($verifikasi['total_semua'] ?? 0);
$pagu = (float) ($verifikasi['pagu'] ?? 0);
$warnaSelisih = $selisih < 0 ? 'negatif' : ($selisih > 0 ? 'positif' : '');
?>  <!-- KPI / Hero Section -->
  <div class="panel biaya-hero">
    <div class="kpi-grid">
      <div class="kpi-kartu">
        <div class="kpi-label">Total Biaya Non-Gedung</div>
        <div class="kpi-nilai"><?= rp($total) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Total Keseluruhan (BOQ + Non-Gedung)</div>
        <div class="kpi-nilai"><?= rp($totalKeseluruhan) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Pagu Anggaran</div>
        <div class="kpi-nilai"><?= rp($pagu) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Selisih (Pagu − Total)</div>
        <div class="kpi-nilai <?= $warnaSelisih ?>"><?= rp($selisih) ?></div>
      </div>
    </div>
    <p class="desc">Biaya tingkat sekolah di luar BOQ. Baris impor diperbarui saat impor ulang; baris manual tetap tersimpan.</p>
  </div>

  <!-- Aksi Bulk -->
  <div class="biaya-aksi">
      <button type="button" class="tombol-buka-form" onclick="bukaFormModal('modal-biaya')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Baris Biaya
      </button>
      <form id="form-spk-biaya" method="post" action="<?= base_url('biaya-non-gedung/buat-spk') ?>" data-konfirmasi-judul="Buat SPK dari Baris Biaya Terpilih" data-konfirmasi-tombol="Ya, Buat SPK" style="display:inline">
        <?= csrf_field() ?>
        <span id="ids-spk-biaya"></span>
        <button type="button" class="tombol kecil" :disabled="terpilih.length === 0" @click="kirim('Buat satu SPK (tanpa gedung) dari {n} baris biaya terpilih? Baris yang sudah tertaut ke SPK aktif akan dilewati.', 'form-spk-biaya', 'ids-spk-biaya')">
          Buat SPK dari Terpilih (<span x-text="terpilih.length">0</span>)
        </button>
      </form>
      <form id="form-bulk-biaya" method="post" action="<?= base_url('biaya-non-gedung/hapus-massal') ?>" data-konfirmasi-judul="Hapus Baris Biaya Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
        <?= csrf_field() ?>
        <span id="ids-biaya"></span>
        <button type="button" class="tombol kecil bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} baris biaya terpilih?')">
          Hapus Terpilih (<span x-text="terpilih.length">0</span>)
        </button>
      </form>
      <form method="post" action="<?= base_url('biaya-non-gedung/kosongkan') ?>" data-konfirmasi="Kosongkan Biaya Non-Gedung sekolah ini? Semua baris aktif — termasuk baris manual — akan dihapus (hapus lunak). Baris sumber impor dapat diisi kembali lewat impor ulang dokumen perencana." data-konfirmasi-judul="Kosongkan Biaya Non-Gedung" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="tombol kecil bahaya">Kosongkan Semua</button>
      </form>
  </div>

  <!-- Empty State -->

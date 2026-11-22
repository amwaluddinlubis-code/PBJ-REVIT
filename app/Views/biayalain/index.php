<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>

<?php
$selisih = (float) ($verifikasi['selisih'] ?? 0);
$totalKeseluruhan = (float) ($verifikasi['total_semua'] ?? 0);
$pagu = (float) ($verifikasi['pagu'] ?? 0);
$warnaSelisih = $selisih < 0 ? 'negatif' : ($selisih > 0 ? 'positif' : '');
?>

<div data-bulk-akar x-data="komponenBulk('form-bulk-biaya','ids-biaya')">

  <!-- KPI / Hero Section -->
  <div class="panel biaya-hero">
    <div class="kpi-grid">
      <div class="kpi-kartu">
        <div class="kpi-label">Total Biaya Non-Gedung</div>
        <div class="kpi-nilai"><?= $rp($total) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Total Keseluruhan (BOQ + Non-Gedung)</div>
        <div class="kpi-nilai"><?= $rp($totalKeseluruhan) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Pagu Anggaran</div>
        <div class="kpi-nilai"><?= $rp($pagu) ?></div>
      </div>
      <div class="kpi-kartu">
        <div class="kpi-label">Selisih (Pagu − Total)</div>
        <div class="kpi-nilai <?= $warnaSelisih ?>"><?= $rp($selisih) ?></div>
      </div>
    </div>
    <p class="desc">Biaya tingkat sekolah di luar BOQ. Baris impor diperbarui saat impor ulang; baris manual tetap tersimpan.</p>
  </div>

  <!-- Aksi Bulk -->
  <div class="biaya-aksi">
      <button type="button" class="tombol-buka-form" onclick="bukaModalTambah(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Baris Biaya
      </button>
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
  <?php if ($kelompok === []): ?>
  <div class="panel biaya-kosong">
    <div class="kosong-ikon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#0049b8" stroke-width="1.6"><path d="M3 3v18h18V3H3z"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5z"/></svg>
    </div>
    <h3 style="margin:0 0 6px;font-size:13px;font-weight:600">Belum Ada Biaya Non-Gedung</h3>
    <p class="petunjuk-field">Impor dokumen perencana yang memuat sheet <i>Prcn dan pgws</i> / <i>ADMINISTRASI</i>, atau tambahkan baris manual lewat tombol di atas.</p>
    <div style="display:flex;justify-content:center;gap:10px">
      <a class="tombol abu" href="<?= base_url('impor') ?>">Kelola Impor Dokumen</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabel Data per Kelompok -->
  <?php foreach ($kelompok as $namaKelompok => $barisList): ?>
  <details class="panel biaya-data"<?= count($barisList) <= 10 ? ' open' : '' ?>>
    <summary class="tabel-kepala">
      <h2 style="margin:0;font-size:13px;font-weight:600"><?= esc($namaKelompok) ?></h2>
      <span class="petunjuk-field"><?= count($barisList) ?> baris</span>
      <svg class="lipat-panah" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="tabel-envelop">
    <table data-cari="Cari baris biaya…" data-paginasi="25" class="tabel-data">
      <thead>
        <tr>
          <th style="width:34px"><input type="checkbox" title="Pilih semua baris biaya" @change="pilihSemua($event)"></th>
          <th>Uraian</th>
          <th class="angka">Volume</th>
          <th class="angka">Periode</th>
          <th class="angka">Harga Satuan</th>
          <th class="angka">Jumlah</th>
          <th class="angka">Potongan Pajak</th>
          <th class="angka">Bersih Diterima</th>
          <th style="width:110px">Sumber</th>
          <th style="width:140px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $sub = 0; $subPot = 0; foreach ($barisList as $r): $sub += (float) $r['jumlah']; $subPot += (float) $r['potongan']; ?>
      <tr>
        <td><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"></td>
        <td>
          <div class="baris-uraian">
            <b><?= esc($r['uraian']) ?></b><?php if (! empty($r['keterangan'])): ?><br><span class="kecil-teks"><?= esc($r['keterangan']) ?></span><?php endif; ?>
          </div>
        </td>
        <td class="angka"><?= $angka($r['volume']) ?> <span class="kecil-teks" style="color:var(--ta-teks-3)"><?= esc($r['satuan'] ?? '') ?></span></td>
        <td class="angka"><?php if ($r['periode'] !== null): ?><?= $angka($r['periode']) ?> <span class="kecil-teks" style="color:var(--ta-teks-3)"><?= esc($r['satuan_periode'] ?? '') ?></span><?php else: ?>—<?php endif; ?></td>
        <td class="angka"><?= $rp($r['harga_satuan']) ?></td>
        <td class="angka"><b><?= $rp($r['jumlah']) ?></b></td>
        <td class="angka"><?php if ((float) $r['potongan'] > 0): ?><?= $rp($r['potongan']) ?><br><span class="kecil-teks"><?= esc($r['label_pajak']) ?></span><?php else: ?>—<?php endif; ?></td>
        <td class="angka"><?= $rp($r['bersih']) ?></td>
        <td><span class="lencana <?= $r['sumber'] === 'impor' ? 'biru' : 'abu' ?>"><?= $r['sumber'] === 'impor' ? 'Impor' : 'Manual' ?></span></td>
        <td>
          <div class="aksi-baris">
            <button type="button" class="tombol kecil ubah-baris" onclick="isiForm(<?= htmlspecialchars(json_encode([
                'id' => (int) $r['id'], 'kelompok' => $r['kelompok'], 'uraian' => $r['uraian'],
                'volume' => $angka($r['volume']), 'satuan' => $r['satuan'] ?? '',
                'periode' => $r['periode'] !== null ? $angka($r['periode']) : '', 'satuan_periode' => $r['satuan_periode'] ?? '',
                'harga' => (string) (int) round((float) $r['harga_satuan']), 'keterangan' => $r['keterangan'] ?? '',
                'pajak' => $r['pajak'] ?? '',
            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>, this)">Ubah</button>
            <form method="post" action="<?= base_url('biaya-non-gedung/hapus/' . $r['id']) ?>" style="display:inline" data-konfirmasi="Hapus baris biaya &quot;<?= esc($r['uraian']) ?>&quot;?">
              <?= csrf_field() ?>
              <button type="submit" class="tombol kecil bahaya">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="subtotal-baris">
        <td colspan="5"><b>Subtotal <?= esc($namaKelompok) ?></b></td>
        <td class="angka"><b><?= $rp($sub) ?></b></td>
        <td class="angka"><b><?= $rp($subPot) ?></b></td>
        <td class="angka"><b><?= $rp($sub - $subPot) ?></b></td>
        <td colspan="2"></td>
      </tr>
      </tbody>
    </table>
    </div>
  </details>
  <?php endforeach; ?>

</div>

<!-- Modal Form Tambah / Ubah -->
<div class="biaya-modal-lapik" id="modal-biaya" hidden role="dialog" aria-modal="true" aria-labelledby="judul-modal">
  <div class="biaya-modal-kotak biaya-form">
    <div class="biaya-modal-kepala">
      <h3 id="judul-modal">Tambah Baris Biaya</h3>
      <button type="button" class="tombol abu kecil" onclick="tutupModal()" style="padding:5px 10px">Tutup</button>
    </div>
    <div class="biaya-modal-isi">
      <form method="post" action="<?= base_url('biaya-non-gedung/simpan') ?>" id="form-biaya-modal">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="f-id-modal" value="">

        <div class="form-grid">
          <div class="form-field">
            <label>Kelompok</label>
            <input type="text" name="kelompok" id="f-kelompok-modal" list="saran-kelompok-modal" required placeholder="cth. Biaya Pengelolaan">
            <datalist id="saran-kelompok-modal">
              <?php foreach ($saran_kelompok as $s): ?><option value="<?= esc($s) ?>"><?php endforeach; ?>
              <?php foreach (array_keys($kelompok) as $k): ?><?php if (! in_array($k, $saran_kelompok, true)): ?><option value="<?= esc($k) ?>"><?php endif; ?><?php endforeach; ?>
            </datalist>
            <p class="petunjuk-field">cth. Perencanaan & Pengawasan, Biaya Pengelolaan</p>
          </div>

          <div class="form-field">
            <label>Uraian</label>
            <input type="text" name="uraian" id="f-uraian-modal" required placeholder="cth. Honor P2SP — Ketua">
          </div>

          <div class="form-field">
            <label>Volume</label>
            <input type="text" name="volume" id="f-volume-modal" inputmode="decimal" required placeholder="1">
            <p class="petunjuk-field">Jumlah satuan pekerjaan</p>
          </div>

          <div class="form-field">
            <label>Satuan Volume</label>
            <input type="text" name="satuan" id="f-satuan-modal" placeholder="orang / set / unit">
          </div>

          <div class="form-field">
            <label>Periode</label>
            <input type="text" name="periode" id="f-periode-modal" inputmode="decimal" placeholder="4">
            <p class="petunjuk-field">Kosongkan bila tidak ada periode</p>
          </div>

          <div class="form-field">
            <label>Satuan Periode</label>
            <input type="text" name="satuan_periode" id="f-satuan-periode-modal" placeholder="bln / minggu / kali">
          </div>

          <div class="form-field">
            <label>Harga Satuan (Rp)</label>
            <input type="text" name="harga_satuan" id="f-harga-modal" inputmode="numeric" data-uang required placeholder="750000">
          </div>

          <div class="form-field">
            <label>Keterangan</label>
            <input type="text" name="keterangan" id="f-keterangan-modal" placeholder="cth. 1,65% dari nilai konstruksi">
          </div>

          <div class="form-field">
            <label>Pajak Dipotong Saat Pembayaran</label>
            <select data-select-cari name="pajak" id="f-pajak-modal">
              <?php foreach ($opsi_pajak as $kunci => $label): ?><option value="<?= esc($kunci) ?>"><?= esc($label) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <p class="petunjuk-form">Jumlah dihitung otomatis: volume × periode (bila ada) × harga satuan. Potongan pajak mengikuti Juknis Revitalisasi SMP 2026: PPh 23 sebesar 2% untuk jasa/sewa, PPh 4 ayat 2 sebesar 3,5% (ber-SKA) atau 6% (tanpa SKA) untuk perencana/pengawas, dan PPh 21 untuk honor menurut golongan penerima.</p>
      </form>
    </div>
    <div class="biaya-modal-kaki">
      <button type="button" class="tombol abu" onclick="tutupModal()">Batal</button>
      <button type="submit" form="form-biaya-modal" id="tombol-simpan-modal" class="tombol" style="min-width:140px">Simpan Baris</button>
    </div>
  </div>
</div>

<script>
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
</script>
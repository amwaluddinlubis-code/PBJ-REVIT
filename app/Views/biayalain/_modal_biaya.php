<?php /* Partial biayalain/index: modal form Tambah / Ubah baris biaya. */ ?><!-- Modal Form Tambah / Ubah -->
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

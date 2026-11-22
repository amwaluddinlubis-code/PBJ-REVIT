<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>

<div class="panel">
  <h2>Biaya Non-Gedung — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Biaya tingkat sekolah di luar BOQ gedung: <b>perencanaan &amp; pengawasan</b> dan <b>biaya pengelolaan (operasional P2SP)</b>. Baris bersumber <b>impor</b> dibaca otomatis dari sheet <i>Prcn dan pgws</i> dan <i>ADMINISTRASI</i> dokumen perencana dan diganti segar setiap impor ulang; baris <b>manual</b> tambahan Anda tidak ikut terhapus. Total biaya ini ikut dihitung dalam verifikasi anggaran (total BOQ gedung aktif + biaya non-gedung dibandingkan pagu).</p>
  <p class="kecil-teks" style="margin-bottom:0">Total biaya non-gedung: <b><?= $rp($total) ?></b> · Total keseluruhan (BOQ + non-gedung): <b><?= $rp($verifikasi['total_semua']) ?></b> · Pagu: <b><?= $rp($verifikasi['pagu']) ?></b> · Selisih: <b><?= $rp($verifikasi['selisih']) ?></b></p>
</div>

<div data-bulk-akar x-data="komponenBulk('form-bulk-biaya','ids-biaya')">
<div class="panel">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <form id="form-bulk-biaya" method="post" action="<?= base_url('biaya-non-gedung/hapus-massal') ?>" data-konfirmasi-judul="Hapus Baris Biaya Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-biaya"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} baris biaya terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('biaya-non-gedung/kosongkan') ?>" data-konfirmasi="Kosongkan Biaya Non-Gedung sekolah ini? Semua baris aktif — termasuk baris manual — akan dihapus (hapus lunak). Baris sumber impor dapat diisi kembali lewat impor ulang dokumen perencana." data-konfirmasi-judul="Kosongkan Biaya Non-Gedung" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Biaya Non-Gedung</button>
    </form>
  </div>
</div>

<div class="panel">
  <h2 id="judul-form">Tambah Baris Biaya</h2>
  <form method="post" action="<?= base_url('biaya-non-gedung/simpan') ?>" id="form-biaya">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="f-id" value="">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
      <label>Kelompok
        <input type="text" name="kelompok" id="f-kelompok" list="saran-kelompok" required placeholder="cth. Biaya Pengelolaan">
        <datalist id="saran-kelompok">
          <?php foreach ($saran_kelompok as $s): ?><option value="<?= esc($s) ?>"><?php endforeach; ?>
          <?php foreach (array_keys($kelompok) as $k): ?><?php if (! in_array($k, $saran_kelompok, true)): ?><option value="<?= esc($k) ?>"><?php endif; ?><?php endforeach; ?>
        </datalist>
      </label>
      <label class="md:col-span-2">Uraian
        <input type="text" name="uraian" id="f-uraian" required placeholder="cth. Honor P2SP — Ketua">
      </label>
      <label>Volume
        <input type="text" name="volume" id="f-volume" inputmode="decimal" required placeholder="1">
      </label>
      <label>Satuan Volume
        <input type="text" name="satuan" id="f-satuan" placeholder="orang / set / unit">
      </label>
      <label>Periode (kosongkan bila tidak ada)
        <input type="text" name="periode" id="f-periode" inputmode="decimal" placeholder="4">
      </label>
      <label>Satuan Periode
        <input type="text" name="satuan_periode" id="f-satuan-periode" placeholder="bln / minggu / kali">
      </label>
      <label>Harga Satuan (Rp)
        <input type="text" name="harga_satuan" id="f-harga" inputmode="numeric" required placeholder="750000">
      </label>
      <label>Keterangan
        <input type="text" name="keterangan" id="f-keterangan" placeholder="cth. 1,65% dari nilai konstruksi">
      </label>
      <label>Pajak Dipotong Saat Pembayaran
        <select name="pajak" id="f-pajak">
          <?php foreach ($opsi_pajak as $kunci => $label): ?><option value="<?= esc($kunci) ?>"><?= esc($label) ?></option><?php endforeach; ?>
        </select>
      </label>
    </div>
    <p class="kecil-teks">Jumlah dihitung otomatis: volume × periode (bila ada) × harga satuan. Potongan pajak mengikuti Juknis Revitalisasi SMP 2026: PPh 23 sebesar 2% untuk jasa/sewa, PPh 4 ayat 2 sebesar 3,5% (ber-SKA) atau 6% (tanpa SKA) untuk perencana/pengawas, dan PPh 21 untuk honor menurut golongan penerima.</p>
    <div class="aksi" style="justify-content:flex-start;gap:10px">
      <button type="submit" id="tombol-simpan">Simpan Baris</button>
      <button type="button" class="tombol abu" id="tombol-batal" style="display:none">Batal Ubah</button>
    </div>
  </form>
</div>

<?php if ($kelompok === []): ?>
<div class="panel">
  <p style="margin:0">Belum ada biaya non-gedung. Impor dokumen perencana yang memuat sheet <i>Prcn dan pgws</i> / <i>ADMINISTRASI</i>, atau tambahkan baris manual lewat formulir di atas.</p>
</div>
<?php endif; ?>

<?php foreach ($kelompok as $namaKelompok => $barisList): ?>
<div class="panel">
  <h2><?= esc($namaKelompok) ?></h2>
  <table data-cari="Cari baris biaya…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua baris biaya" @change="pilihSemua($event)"></th><th>Uraian</th><th class="angka">Volume</th><th class="angka">Periode</th><th class="angka">Harga Satuan</th><th class="angka">Jumlah</th><th class="angka">Potongan Pajak</th><th class="angka">Bersih Diterima</th><th>Sumber</th><th>Aksi</th></tr>
    <?php $sub = 0; $subPot = 0; foreach ($barisList as $r): $sub += (float) $r['jumlah']; $subPot += (float) $r['potongan']; ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"></td>
      <td><b><?= esc($r['uraian']) ?></b><?php if (! empty($r['keterangan'])): ?><br><span class="kecil-teks"><?= esc($r['keterangan']) ?></span><?php endif; ?></td>
      <td class="angka"><?= $angka($r['volume']) ?> <?= esc($r['satuan'] ?? '') ?></td>
      <td class="angka"><?php if ($r['periode'] !== null): ?><?= $angka($r['periode']) ?> <?= esc($r['satuan_periode'] ?? '') ?><?php else: ?>—<?php endif; ?></td>
      <td class="angka"><?= $rp($r['harga_satuan']) ?></td>
      <td class="angka"><b><?= $rp($r['jumlah']) ?></b></td>
      <td class="angka"><?php if ((float) $r['potongan'] > 0): ?><?= $rp($r['potongan']) ?><br><span class="kecil-teks"><?= esc($r['label_pajak']) ?></span><?php else: ?>—<?php endif; ?></td>
      <td class="angka"><?= $rp($r['bersih']) ?></td>
      <td><span class="kecil-teks"><?= $r['sumber'] === 'impor' ? 'Impor' : 'Manual' ?></span></td>
      <td>
        <button type="button" class="tombol kecil" onclick="isiForm(<?= htmlspecialchars(json_encode([
            'id' => (int) $r['id'], 'kelompok' => $r['kelompok'], 'uraian' => $r['uraian'],
            'volume' => $angka($r['volume']), 'satuan' => $r['satuan'] ?? '',
            'periode' => $r['periode'] !== null ? $angka($r['periode']) : '', 'satuan_periode' => $r['satuan_periode'] ?? '',
            'harga' => (string) (int) round((float) $r['harga_satuan']), 'keterangan' => $r['keterangan'] ?? '',
            'pajak' => $r['pajak'] ?? '',
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">Ubah</button>
        <form method="post" action="<?= base_url('biaya-non-gedung/hapus/' . $r['id']) ?>" style="display:inline" data-konfirmasi="Hapus baris biaya &quot;<?= esc($r['uraian']) ?>&quot;?">
          <?= csrf_field() ?>
          <button type="submit" class="tombol kecil bahaya">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="5"><b>Subtotal <?= esc($namaKelompok) ?></b></td><td class="angka"><b><?= $rp($sub) ?></b></td><td class="angka"><b><?= $rp($subPot) ?></b></td><td class="angka"><b><?= $rp($sub - $subPot) ?></b></td><td colspan="2"></td></tr>
  </table>
</div>
<?php endforeach; ?>
</div>

<script>
function isiForm(d) {
  document.getElementById('f-id').value = d.id;
  document.getElementById('f-kelompok').value = d.kelompok;
  document.getElementById('f-uraian').value = d.uraian;
  document.getElementById('f-volume').value = d.volume;
  document.getElementById('f-satuan').value = d.satuan;
  document.getElementById('f-periode').value = d.periode;
  document.getElementById('f-satuan-periode').value = d.satuan_periode;
  document.getElementById('f-harga').value = d.harga;
  document.getElementById('f-keterangan').value = d.keterangan;
  document.getElementById('f-pajak').value = d.pajak || '';
  document.getElementById('judul-form').textContent = 'Ubah Baris Biaya';
  document.getElementById('tombol-simpan').textContent = 'Simpan Perubahan';
  document.getElementById('tombol-batal').style.display = '';
  document.getElementById('form-biaya').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
document.getElementById('tombol-batal').addEventListener('click', function () {
  document.getElementById('form-biaya').reset();
  document.getElementById('f-id').value = '';
  document.getElementById('judul-form').textContent = 'Tambah Baris Biaya';
  document.getElementById('tombol-simpan').textContent = 'Simpan Baris';
  this.style.display = 'none';
});
</script>

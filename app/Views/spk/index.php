<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaSpkBaru()">+ SPK Baru</button></div>
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
<table data-paginasi="10" data-cari="Cari SPK (nomor, penyedia, paket)…">
  <tr><th style="width:34px"><input type="checkbox" title="Pilih semua SPK" @change="pilihSemua($event)"></th><th>No.</th><th>Tanggal</th><th>Penyedia</th><th>Paket</th><th>Periode</th><th class="angka">Jml Bahan</th><th class="angka">Nilai SPK</th><th>Status</th><th></th></tr>
  <?php foreach ($spk as $s): ?>
  <tr>
    <td><input type="checkbox" data-pilih value="<?= (int) $s['id'] ?>" x-model="terpilih"></td>
    <td><a href="<?= base_url('spk/detail/' . $s['id']) ?>"><?= (int) $s['nomor'] ?></a></td>
    <td><?= esc(tgl($s['tanggal'] ?? null)) ?></td>
    <td><?= esc($s['penyedia'] ?? '—') ?></td>
    <td><?= esc($s['paket'] ?? '—') ?></td>
    <td><?= esc(tgl($s['tgl_mulai'] ?? null)) ?> s.d. <?= esc(tgl($s['tgl_selesai'] ?? null)) ?></td>
    <td class="angka"><?= (int) $s['jumlah'] ?></td>
    <td class="angka"><?= rp($s['nilai']) ?></td>
    <td><?= esc($s['status']) ?></td>
    <td><button type="button" class="tombol kecil abu" onclick="bukaSpkUbah(<?= (int) $s['id'] ?>)">Ubah</button> <a class="tombol kecil" href="<?= base_url('spk/detail/' . $s['id']) ?>">Detail</a></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?= $this->include('spk/_modal_form') ?>

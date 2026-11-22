<div class="panel">
  <h2>Dokumentasi Foto Progres — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Juknis mewajibkan dokumentasi foto kondisi <b>sebelum</b> pekerjaan, <b>selama tahap-tahap pekerjaan</b> (mingguan), dan di <b>akhir</b> pekerjaan. Foto menjadi bagian laporan kemajuan, laporan akhir, dan LPJ. Saat ini tersimpan: sebelum <?= (int) $hitung['sebelum'] ?> foto · proses <?= (int) $hitung['proses'] ?> foto · akhir <?= (int) $hitung['akhir'] ?> foto.</p>
  <form method="post" action="<?= base_url('foto-progres/unggah') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
      <label>Gedung
        <select data-select-cari name="gedung_id">
          <option value="">— Umum / semua paket —</option>
          <?php foreach ($gedung as $g): ?><option value="<?= (int) $g['id'] ?>"><?= esc($g['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Tahap
        <select data-select-cari name="tahap"><option value="sebelum">Sebelum pekerjaan</option><option value="proses" selected>Selama pekerjaan (proses)</option><option value="akhir">Akhir pekerjaan</option></select>
      </label>
      <label>Tanggal Foto <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label class="md:col-span-2">Keterangan <input type="text" name="keterangan" placeholder="cth. Pemasangan rangka atap minggu ke-6"></label>
      <label>Berkas Foto (JPG/PNG/WebP, maks 5 MB) <input type="file" name="foto" accept="image/*" required></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Unggah Foto</button></div>
  </form>
</div>

<div class="panel" data-bulk-akar x-data="{ ...komponenBulk('form-bulk-foto','ids-foto'), kata: '' }">
  <h2>Galeri Foto Progres</h2>
  <?php if ($foto === []): ?>
    <p>Belum ada foto terdokumentasi.</p>
  <?php else: ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px">
    <input type="search" placeholder="Saring foto (tahap, gedung, keterangan)…" x-model="kata" style="flex:1;min-width:200px">
    <form id="form-bulk-foto" method="post" action="<?= base_url('foto-progres/hapus-massal') ?>" data-konfirmasi-judul="Hapus Foto Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-foto"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} foto terpilih beserta berkasnya?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('foto-progres/kosongkan') ?>" data-konfirmasi="Kosongkan galeri foto progres sekolah ini? Semua <?= count($foto) ?> foto akan dihapus beserta berkasnya." data-konfirmasi-judul="Kosongkan Galeri Foto" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Galeri</button>
    </form>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px">
    <?php foreach ($foto as $f): ?>
    <figure style="margin:0;border:1px solid var(--ta-garis,#ddd);border-radius:8px;overflow:hidden" data-teks="<?= esc(mb_strtolower($f['tahap'] . ' ' . ($f['gedung_nama'] ?? '') . ' ' . ($f['keterangan'] ?? '') . ' ' . $f['tanggal'])) ?>" x-show="kata === '' || $el.dataset.teks.includes(kata.toLowerCase())">
      <a href="<?= base_url($f['berkas']) ?>" target="_blank"><img src="<?= base_url($f['berkas']) ?>" alt="<?= esc($f['keterangan'] ?? 'Foto progres') ?>" style="width:100%;height:150px;object-fit:cover;display:block"></a>
      <figcaption style="padding:8px 10px">
        <div><b><?= esc(ucfirst($f['tahap'])) ?></b> · <?= esc($f['tanggal']) ?></div>
        <div class="kecil-teks"><?= esc($f['gedung_nama'] ?? 'Umum') ?><?= $f['keterangan'] ? ' — ' . esc($f['keterangan']) : '' ?></div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
          <label class="kecil-teks"><input type="checkbox" data-pilih value="<?= (int) $f['id'] ?>" x-model="terpilih"> Pilih</label>
          <form method="post" action="<?= base_url('foto-progres/hapus/' . $f['id']) ?>" data-konfirmasi="Hapus foto ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form>
        </div>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

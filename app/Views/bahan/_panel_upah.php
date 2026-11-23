<?php /* Partial bahan/index: panel Master Upah (tabel tarif, bulk, kosongkan, rapikan) + sub-partial duplikat & gabung manual. */ ?>
<details class="panel panel-lipat" data-lipat-id="master-upah" x-data="{
  kataUpah: '',
  terpilihUpah: [],
  pilihSemuaUpah(ev) {
    const ids = [...document.querySelectorAll('[data-pilih-upah]')].map((el) => el.value);
    if (ev.target.checked) this.terpilihUpah = [...new Set([...this.terpilihUpah, ...ids])];
    else this.terpilihUpah = this.terpilihUpah.filter((id) => !ids.includes(id));
  },
  kirimBulkUpah() {
    const form = document.getElementById('form-bulk-upah');
    const wadahIds = document.getElementById('ids-upah');
    wadahIds.innerHTML = '';
    this.terpilihUpah.forEach((id) => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
      wadahIds.appendChild(inp);
    });
    form.dataset.konfirmasi = 'Hapus ' + this.terpilihUpah.length + ' jenis tenaga terpilih dari Master Upah? Pembayaran lama tidak berubah.';
    form.requestSubmit();
  }
}">
  <summary>Master Upah — Referensi Tarif Tenaga Kerja <span class="kecil-teks">(<?= (int) $totalUpah ?> jenis)</span></summary>
  <p style="margin-top:0;color:var(--ta-teks-2)">Satu jenis tenaga = satu tarif acuan. Master ini dipakai modul Upah saat mencatat pembayaran dan menjadi acuan komponen upah pada analisa. Menambah jenis dengan nama yang sama akan ditolak.</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <input type="search" placeholder="Saring jenis tenaga…" x-model="kataUpah" style="flex:1;min-width:200px">
    <form id="form-bulk-upah" method="post" action="<?= base_url('bahan/upah-hapus-massal') ?>" data-konfirmasi-judul="Hapus Jenis Tenaga Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-upah"></span>
      <button type="button" class="bahaya" :disabled="terpilihUpah.length === 0" @click="kirimBulkUpah()">Hapus Terpilih (<span x-text="terpilihUpah.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('bahan/upah-kosongkan') ?>" data-konfirmasi="Kosongkan Master Upah sekolah ini? Semua <?= (int) $totalUpah ?> jenis tenaga aktif akan dihapus (hapus lunak). Pembayaran upah yang sudah tercatat tidak berubah." data-konfirmasi-judul="Kosongkan Master Upah" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Master Upah</button>
    </form>
    <form method="post" action="<?= base_url('bahan/upah-rapikan') ?>" data-konfirmasi="Rapikan Master Upah menjadi 2 jenis saja? Semua varian Tukang (Batu, Kayu, Cat, Las, Listrik, Besi, dst.) digabung menjadi 'Tukang'; komponen Mandor & Kepala Tukang dihilangkan dari analisa dan semua Pekerja menjadi 'Pekerja' — nama & tautan komponen upah di semua analisa ikut diseragamkan, master lainnya dihapus lunak. Komponen bernama lain (mis. Peralatan) tidak diubah." data-konfirmasi-judul="Rapikan Master Upah — 2 Jenis" data-konfirmasi-tombol="Ya, Rapikan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit">Rapikan ke 2 Jenis (Pekerja &amp; Tukang)</button>
    </form>
  </div>
  <table data-cari="Cari jenis tenaga…">
    <tr>
      <th style="width:34px"><input type="checkbox" title="Pilih semua jenis tenaga" @change="pilihSemuaUpah($event)"></th>
      <th>Jenis Tenaga</th><th>Satuan</th><th class="angka">Tarif</th><th style="width:300px">Ubah Tarif</th><th></th>
    </tr>
    <?php foreach ($upah as $u): ?>
    <tr data-jenis="<?= esc(mb_strtolower($u['jenis'])) ?>" x-show="kataUpah === '' || $el.dataset.jenis.includes(kataUpah.toLowerCase())">
      <td><input type="checkbox" data-pilih-upah value="<?= (int) $u['id'] ?>" x-model="terpilihUpah"></td>
      <td><?= esc($u['jenis']) ?></td>
      <td><?= esc($u['satuan'] ?? 'OH') ?></td>
      <td class="angka"><?= rp($u['tarif']) ?></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('bahan/upah-perbarui/' . $u['id']) ?>"><?= csrf_field() ?><input type="text" inputmode="numeric" data-uang name="tarif" value="<?= (int) $u['tarif'] ?>" size="10"> <button class="kecil" type="submit">Simpan</button></form>
      </td>
      <td><form class="inline" method="post" action="<?= base_url('bahan/upah-hapus/' . $u['id']) ?>" data-konfirmasi="Hapus jenis tenaga ini dari Master Upah? Pembayaran lama tidak berubah."><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($upah === []): ?><tr><td colspan="6">Master upah masih kosong.</td></tr><?php endif; ?>
  </table>
  <form method="post" action="<?= base_url('bahan/upah-simpan') ?>" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:end">
    <?= csrf_field() ?>
    <label>Jenis tenaga baru <input type="text" name="jenis" required placeholder="mis. Tukang Batu"></label>
    <label>Satuan <input type="text" name="satuan" value="OH" size="5"></label>
    <label>Tarif (Rp) <input type="text" inputmode="numeric" data-uang name="tarif" required placeholder="mis. 93578"></label>
    <button type="submit" class="kecil">+ Tambah ke Master Upah</button>
  </form>

<?= $this->include('bahan/_upah_duplikat') ?>

<?= $this->include('bahan/_upah_gabung') ?>
</details>

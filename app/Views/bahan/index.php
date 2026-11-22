<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>

<?php if (! empty($bahanTanpaHarga) || ! empty($upahTanpaTarif)): ?>
<div class="panel" style="border:1px solid #f59e0b;background:#fffbeb">
  <h2 style="margin-top:0">Kelengkapan Harga &amp; Tarif</h2>
  <p class="kecil-teks" style="margin:0 0 10px">Bahan tanpa harga acuan terhitung <strong>Rp 0</strong> di kebutuhan &amp; rencana belanja; jenis upah tanpa tarif terhitung Rp 0 di kebutuhan upah.
  Isi yang kosong di bawah ini lalu klik <strong>Simpan Semua Isian</strong> — yang sudah terisi tidak muncul lagi di panel ini.</p>
  <form method="post" action="<?= base_url('bahan/kelengkapan') ?>">
    <?= csrf_field() ?>
    <?php if (! empty($bahanTanpaHarga)): ?>
    <p style="margin:8px 0 4px"><strong><?= count($bahanTanpaHarga) ?> bahan belum punya harga acuan</strong></p>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari bahan…">
      <tr><th>Kode</th><th>Bahan</th><th>Satuan</th><th style="width:190px">Harga Acuan (Rp)</th></tr>
      <?php foreach ($bahanTanpaHarga as $b): ?>
      <tr>
        <td><?= esc($b['kode']) ?></td>
        <td class="potong" title="<?= esc($b['nama']) ?>"><?= esc($b['nama']) ?></td>
        <td><?= esc($b['satuan']) ?></td>
        <td><input type="text" inputmode="numeric" data-uang name="harga[<?= (int) $b['id'] ?>]" min="1" placeholder="isi harga…" style="width:170px"></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
    <?php if (! empty($upahTanpaTarif)): ?>
    <p style="margin:12px 0 4px"><strong><?= count($upahTanpaTarif) ?> jenis upah belum punya tarif</strong></p>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari jenis tenaga…">
      <tr><th>Jenis Tenaga</th><th style="width:190px">Tarif per Hari (Rp)</th></tr>
      <?php foreach ($upahTanpaTarif as $u): ?>
      <tr>
        <td><?= esc($u['jenis']) ?></td>
        <td><input type="text" inputmode="numeric" data-uang name="tarif[<?= (int) $u['id'] ?>]" min="1" placeholder="isi tarif…" style="width:170px"></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
    <p style="margin:12px 0 0"><button type="submit" class="tombol">Simpan Semua Isian</button></p>
  </form>
</div>
<?php endif; ?>

<div x-data="{
  kata: '<?= esc($q, 'js') ?>',
  tab: '<?= esc($tab ?? 'gedung', 'js') ?>',
  terpilih: [],
  memuat: false,
  sinkron: null,
  async cari() {
    this.memuat = true;
    try {
      const r = await fetch('<?= base_url('bahan/tabel') ?>?q=' + encodeURIComponent(this.kata) + '&tab=' + this.tab, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const wadah = document.getElementById('wadah-tabel-bahan');
      wadah.innerHTML = await r.text();
      if (window.inisiasiPaginasi) window.inisiasiPaginasi(wadah);
      history.replaceState(null, '', '?tab=' + this.tab + (this.kata ? '&q=' + encodeURIComponent(this.kata) : ''));
    } finally { this.memuat = false; }
  },
  async pratinjauSinkron() {
    this.memuat = true;
    try {
      const r = await fetch('<?= base_url('bahan/sinkron-pratinjau') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const d = await r.json();
      if (!d.berubah || d.berubah.length === 0) {
        window.tampilToast('Semua tarif komponen analisa sudah sama dengan harga bahan — tidak ada tarif yang berubah.', 'sukses');
        return;
      }
      this.sinkron = d;
      document.getElementById('modal-sinkron').hidden = false;
    } finally { this.memuat = false; }
  },
  tutupSinkron() {
    document.getElementById('modal-sinkron').hidden = true;
  },
  formBahanId: 0,
  galatBahan: '',
  async bukaTambah() {
    this.galatBahan = '';
    this.formBahanId = 0;
    const f = document.getElementById('form-bahan');
    f.reset();
    try {
      const r = await fetch('<?= base_url('bahan/kode-baru') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const d = await r.json();
      f.kode.value = d.kode || '';
    } catch (e) { /* kode bisa diisi manual */ }
    document.getElementById('modal-bahan').hidden = false;
    setTimeout(() => f.nama.focus(), 30);
  },
  bukaUbah(ds) {
    this.galatBahan = '';
    this.formBahanId = parseInt(ds.id || '0', 10);
    const f = document.getElementById('form-bahan');
    f.reset();
    f.kode.value = ds.kode || '';
    f.nama.value = ds.nama || '';
    f.satuan.value = ds.satuan || '';
    f.harga_rab.value = window.formatUang ? window.formatUang(ds.harga || '') : (ds.harga || '');
    f.qty_rkb.value = ds.qrkb || 0;
    f.qty_adm.value = ds.qadm || 0;
    f.qty_toilet.value = ds.qtoilet || 0;
    document.getElementById('modal-bahan').hidden = false;
    setTimeout(() => f.nama.focus(), 30);
  },
  tutupBahan() {
    document.getElementById('modal-bahan').hidden = true;
  },
  async simpanBahan() {
    const f = document.getElementById('form-bahan');
    const fd = new FormData(f);
    fd.set('harga_rab', String(fd.get('harga_rab') || '').replace(/[^\d]/g, ''));
    const url = this.formBahanId > 0
      ? '<?= base_url('bahan/perbarui') ?>/' + this.formBahanId
      : '<?= base_url('bahan/simpan') ?>';
    this.memuat = true;
    try {
      const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const d = await r.json();
      if (d.ok) {
        this.tutupBahan();
        window.tampilToast(d.pesan, 'sukses');
        this.terpilih = [];
        await this.cari();
      } else {
        this.galatBahan = d.pesan || 'Gagal menyimpan bahan.';
      }
    } catch (e) {
      this.galatBahan = 'Koneksi gagal — coba lagi.';
    } finally { this.memuat = false; }
  },
  pilihSemua(ev) {
    const ids = [...document.querySelectorAll('#wadah-tabel-bahan [data-pilih-bahan]')].map((el) => el.value);
    if (ev.target.checked) this.terpilih = [...new Set([...this.terpilih, ...ids])];
    else this.terpilih = this.terpilih.filter((id) => !ids.includes(id));
  },
  kirimBulk() {
    const form = document.getElementById('form-bulk-bahan');
    const wadahIds = document.getElementById('ids-bahan');
    wadahIds.innerHTML = '';
    this.terpilih.forEach((id) => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
      wadahIds.appendChild(inp);
    });
    form.dataset.konfirmasi = 'Hapus ' + this.terpilih.length + ' bahan terpilih dari master? Bahan yang sudah dipakai di SPK Detail akan dilewati dan dipertahankan.';
    form.requestSubmit();
  }
}">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px">
    <button type="button" class="tombol" @click="bukaTambah()">+ Tambah Bahan</button>
    <input type="search" placeholder="Cari bahan — ketik, daftar menyaring langsung…" x-model="kata" @input.debounce.300ms="cari()" style="flex:1;min-width:220px">
    <button type="button" @click="pratinjauSinkron()">Sinkronkan Harga ke Analisa</button>
    <span x-show="memuat" class="kecil-teks">Memuat…</span>
    <form id="form-bulk-bahan" method="post" action="<?= base_url('bahan/hapus-massal') ?>" data-konfirmasi-judul="Hapus Bahan Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-bahan"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirimBulk()">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('bahan/kosongkan') ?>" data-konfirmasi="Kosongkan Master Bahan sekolah ini? Semua <?= (int) $totalBahan ?> bahan aktif akan dihapus (hapus lunak). Bahan yang sudah dipakai di SPK Detail dipertahankan dan akan dilaporkan." data-konfirmasi-judul="Kosongkan Master Bahan" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Master Bahan</button>
    </form>
  </div>
  <div class="tab" style="margin-bottom:0">
    <a href="#" @click.prevent="tab = 'gedung'; terpilih = []; cari()" :class="tab === 'gedung' ? 'aktif' : ''">Dipakai Gedung (<?= (int) ($jmlTerpakai ?? 0) ?>)</a>
    <a href="#" @click.prevent="tab = 'luar'; terpilih = []; cari()" :class="tab === 'luar' ? 'aktif' : ''">Tidak Dipakai Gedung (<?= (int) (($totalBahan ?? 0) - ($jmlTerpakai ?? 0)) ?>)</a>
  </div>
  <div class="panel" id="wadah-tabel-bahan">
    <?= view('bahan/_tabel', ['bahan' => $bahan]) ?>
  </div>

  <div id="modal-sinkron" class="mk-lapik" hidden>
    <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="ms-judul" style="max-width:760px;width:94vw">
      <h3 id="ms-judul" style="margin-top:0">Sinkronkan Harga Bahan ke Analisa?</h3>
      <p class="kecil-teks" style="margin-top:0">Tarif komponen bahan di analisa akan diganti dengan harga acuan bahannya (negosiasi → penawaran → harga RAB). Nilai BOQ dashboard berubah mengikuti hasil AHSP baru. <strong>Hanya tarif yang berubah yang ditampilkan</strong> — <span x-text="sinkron ? sinkron.tetap : 0"></span> komponen yang sudah sama disembunyikan<span x-show="sinkron && sinkron.tanpa_harga > 0">, dan <span x-text="sinkron ? sinkron.tanpa_harga : 0"></span> komponen dilewati karena bahannya belum punya harga</span>.</p>
      <div style="max-height:46vh;overflow:auto;border:1px solid var(--ta-garis);border-radius:8px">
        <table class="satu-baris" style="margin:0">
          <tr><th>Analisa</th><th>Komponen / Bahan</th><th>Satuan</th><th class="angka">Tarif Lama</th><th class="angka">Tarif Baru</th></tr>
          <template x-for="b in (sinkron ? sinkron.berubah : [])" :key="b.komponen_id">
            <tr>
              <td x-text="b.kode_analisa"></td>
              <td><span x-text="b.komponen"></span><br><span class="kecil-teks" x-text="b.kode_bahan + ' · ' + b.nama_bahan"></span></td>
              <td x-text="b.satuan"></td>
              <td class="angka" x-text="'Rp ' + Math.round(b.tarif_lama).toLocaleString('id-ID')"></td>
              <td class="angka"><strong x-text="'Rp ' + Math.round(b.tarif_baru).toLocaleString('id-ID')"></strong></td>
            </tr>
          </template>
        </table>
      </div>
      <form method="post" action="<?= base_url('bahan/sinkron-analisa') ?>" style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
        <?= csrf_field() ?>
        <button type="button" @click="tutupSinkron()">Batal</button>
        <button type="submit" class="tombol">Ya, Sinkronkan <span x-text="sinkron ? sinkron.berubah.length : 0"></span> Komponen</button>
      </form>
    </div>
  </div>

  <div id="modal-bahan" class="mk-lapik" hidden @click.self="tutupBahan()" @keydown.escape.window="tutupBahan()">
    <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="mb-judul" style="max-width:640px;width:94vw">
      <h3 id="mb-judul" style="margin-top:0" x-text="formBahanId > 0 ? 'Ubah Bahan' : 'Tambah Bahan'">Tambah Bahan</h3>
      <div class="galat" x-show="galatBahan" x-text="galatBahan" style="margin-bottom:10px"></div>
      <form id="form-bahan" @submit.prevent="simpanBahan()">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
          <label>Kode <input name="kode" required></label>
          <label>Nama Bahan <input name="nama" required></label>
          <label>Satuan <input name="satuan"></label>
          <label>Harga RAB (Rp) <input type="text" inputmode="numeric" data-uang name="harga_rab" required></label>
          <label>Qty Gedung RKB <input type="number" name="qty_rkb" step="0.001" value="0"></label>
          <label>Qty Ruang ADM <input type="number" name="qty_adm" step="0.001" value="0"></label>
          <label>Qty Toilet <input type="number" name="qty_toilet" step="0.001" value="0"></label>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
          <button type="button" @click="tutupBahan()">Batal</button>
          <button type="submit" class="tombol" :disabled="memuat">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (! empty($duplikatBahan)): ?>
<div class="panel" style="border-left:4px solid var(--ta-kuning,#b58900)">
  <h2>ALERT: Bahan Sama dengan Satuan / Harga Berbeda</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Bahan berikut tercatat lebih dari satu kali dengan nama yang sama tetapi satuan atau harga yang berbeda. <strong>Perbaiki dulu</strong> — tentukan satuan &amp; harga final dan baris master utamanya — <strong>barulah gabungkan</strong> ke Master Bahan: semua referensi di SPK, analisa, survei harga, negosiasi, dan buku material dialihkan ke master itu, kuantitas RAB dijumlahkan, dan baris duplikat dihapus. Sesudah digabung, bahan ini hanya punya satu satuan dan satu harga acuan untuk semua modul.</p>
  <?php foreach ($duplikatBahan as $grup): ?>
  <form method="post" action="<?= base_url('bahan/gabung') ?>" data-konfirmasi="Gabungkan duplikat bahan ini? Referensi dari baris lain akan dialihkan ke baris utama yang dipilih.">
    <?= csrf_field() ?>
    <table>
      <tr><th style="width:36px">Utama</th><th>Kode</th><th>Nama</th><th>Satuan</th><th class="angka">Harga RAB</th><th class="angka">Qty RAB Total</th></tr>
      <?php foreach ($grup['anggota'] as $i => $b): ?>
      <tr>
        <td><input type="radio" name="utama" value="<?= (int) $b['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><?= esc($b['kode']) ?><input type="hidden" name="anggota[]" value="<?= (int) $b['id'] ?>"></td>
        <td><?= esc($b['nama']) ?></td>
        <td><?= esc($b['satuan'] ?? '') ?></td>
        <td class="angka"><?= $rp($b['harga_rab']) ?></td>
        <td class="angka"><?= number_format((float) $b['qty_rkb'] + (float) $b['qty_adm'] + (float) $b['qty_toilet'], 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Satuan final (perbaiki bila perlu) <input type="text" name="satuan_final" value="<?= esc($grup['anggota'][0]['satuan'] ?? '') ?>" size="8"></label>
      <label>Harga final (Rp) <input type="text" data-uang name="harga_final" value="<?= (int) $grup['anggota'][0]['harga_rab'] ?>"></label>
      <span class="kecil-teks">Satuan &amp; harga final ini yang menjadi acuan master setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel" x-data="{
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
  <h2>Master Upah — Referensi Tarif Tenaga Kerja</h2>
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
      <td class="angka"><?= $rp($u['tarif']) ?></td>
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

  <?php if (! empty($duplikatUpah)): ?>
  <h3 style="margin-bottom:6px">Duplikat Jenis Tenaga</h3>
  <?php foreach ($duplikatUpah as $grup): ?>
  <form method="post" action="<?= base_url('bahan/upah-gabung') ?>" data-konfirmasi="Gabungkan duplikat jenis tenaga ini mengikuti tarif baris utama?">
    <?= csrf_field() ?>
    <table>
      <tr><th style="width:36px">Utama</th><th>Jenis</th><th>Satuan</th><th class="angka">Tarif</th></tr>
      <?php foreach ($grup['anggota'] as $i => $u): ?>
      <tr>
        <td><input type="radio" name="utama" value="<?= (int) $u['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><?= esc($u['jenis']) ?><input type="hidden" name="anggota[]" value="<?= (int) $u['id'] ?>"></td>
        <td><?= esc($u['satuan'] ?? 'OH') ?></td>
        <td class="angka"><?= $rp($u['tarif']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Tarif final (Rp) <input type="text" data-uang name="tarif_final" value="<?= (int) $grup['anggota'][0]['tarif'] ?>"></label>
      <span class="kecil-teks">Tarif final ini yang menjadi acuan Master Upah setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit" class="kecil">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
  <?php endif; ?>

  <h3 style="margin:14px 0 6px">Gabungkan Jenis Upah</h3>
  <p style="margin-top:0;color:var(--ta-teks-2)">Untuk jenis yang namanya berbeda tetapi pekerjaannya sama (mis. "Kepala Tukang batu" dan "Kepala Tukang cat" dilebur ke "Kepala Tukang"): pilih jenis tujuan, centang jenis yang dilebur, lalu gabungkan. Komponen analisa dan riwayat pembayaran berjenis sumber ikut berubah menjadi jenis tujuan, dan baris sumber dihapus dari master.</p>
  <form method="post" action="<?= base_url('bahan/upah-gabung-manual') ?>" data-konfirmasi="Gabungkan jenis tercentang ke jenis tujuan? Nama jenis pada komponen analisa dan riwayat pembayaran ikut diubah.">
    <?= csrf_field() ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Jenis tujuan <?= select_cari('tujuan', array_map(static fn ($u) => ['value' => (int) $u['id'], 'label' => $u['jenis'] . ' — ' . $rp($u['tarif'])], $upah), null, ['id' => 'gabung-tujuan']) ?></label>
      <label>Tarif final tujuan (Rp) <input type="text" data-uang name="tarif_final" placeholder="kosongkan = tarif tujuan tetap"></label>
    </div>
    <div style="display:flex;gap:6px 16px;flex-wrap:wrap;margin:6px 0">
      <?php foreach ($upah as $u): ?>
      <label style="display:inline-flex;gap:5px;align-items:center"><input type="checkbox" name="sumber[]" value="<?= (int) $u['id'] ?>" data-jenis-gabung="<?= (int) $u['id'] ?>"> <?= esc($u['jenis']) ?></label>
      <?php endforeach; ?>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit" class="kecil">Gabungkan ke Jenis Tujuan</button></div>
  </form>
  <script>
  (function () {
    var sel = document.getElementById('gabung-tujuan');
    if (!sel) return;
    function sesuaikan() {
      document.querySelectorAll('[data-jenis-gabung]').forEach(function (c) {
        var sama = c.value === sel.value;
        c.disabled = sama;
        if (sama) c.checked = false;
      });
    }
    sel.addEventListener('change', sesuaikan);
    sesuaikan();
  })();
  </script>
</div>

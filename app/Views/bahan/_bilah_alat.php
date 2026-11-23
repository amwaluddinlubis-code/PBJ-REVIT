<?php /* Partial bahan/index: bilah alat (tambah, cari, sinkron, hapus massal, kosongkan), tab, dan wadah tabel. Berada DI DALAM komponen Alpine induk di index. */ ?>  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px">
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

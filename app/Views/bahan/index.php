<?= $this->include('bahan/_panel_kelengkapan') ?>

<div x-data="{
  kata: '<?= esc($q, 'js') ?>',
  tab: '<?= esc($tab ?? 'gedung', 'js') ?>',
  terpilih: [],
  memuat: false,
  sinkron: null,
  async cari() {
    this.memuat = true;
    const wadah = document.getElementById('wadah-tabel-bahan');
    wadah.setAttribute('aria-busy', 'true');
    const tulang = document.createElement('div');
    tulang.className = 'tulang';
    tulang.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < 8; i++) tulang.appendChild(document.createElement('span'));
    wadah.replaceChildren(tulang);
    try {
      const r = await fetch('<?= base_url('bahan/tabel') ?>?q=' + encodeURIComponent(this.kata) + '&tab=' + this.tab, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      wadah.innerHTML = await r.text();
      if (window.inisiasiPaginasi) window.inisiasiPaginasi(wadah);
      history.replaceState(null, '', '?tab=' + this.tab + (this.kata ? '&q=' + encodeURIComponent(this.kata) : ''));
    } finally { this.memuat = false; wadah.removeAttribute('aria-busy'); }
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
  // Jembatan modal Bahan (form_modal): hanya mengambil kode baru /
  // mengoper data baris ke bukaFormModal — buka/tutup/kirim/validasi
  // milik form-modal.js; sesudah simpan tabel disegarkan helper.
  async bukaTambah() {
    let kode = '';
    try {
      const r = await fetch('<?= base_url('bahan/kode-baru') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const d = await r.json();
      kode = d.kode || '';
    } catch (e) { /* kode bisa diisi manual */ }
    window.bukaFormModal('modal-bahan', { kode: kode });
    setTimeout(() => { const f = document.querySelector('#modal-bahan [name=nama]'); if (f) f.focus(); }, 30);
  },
  bukaUbah(ds) {
    window.bukaFormModal('modal-bahan', {
      id: parseInt(ds.id || '0', 10),
      kode: ds.kode || '',
      nama: ds.nama || '',
      satuan: ds.satuan || '',
      harga_rab: ds.harga || ''
    });
    setTimeout(() => { const f = document.querySelector('#modal-bahan [name=nama]'); if (f) f.focus(); }, 30);
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
<?= $this->include('bahan/_bilah_alat') ?>

<?= $this->include('bahan/_modal_sinkron') ?>

<?= $this->include('bahan/_modal_bahan') ?>
</div>

<?= $this->include('bahan/_panel_duplikat') ?>

<?= $this->include('bahan/_panel_gabung_manual') ?>

<?= $this->include('bahan/_panel_upah') ?>

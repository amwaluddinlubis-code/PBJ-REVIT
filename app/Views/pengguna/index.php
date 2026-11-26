<?php /* Manajemen User: tabel helper tabel_bantu + modal form_modal
   (Tambah & Ubah; simpan AJAX ke pengguna/simpan, id tersembunyi
   membedakan baru/ubah; pengguna/{id}/ubah tetap ada). Hapus tetap
   form baris berkonfirmasi (tersembunyi untuk akun sendiri). */ ?>
<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaPenggunaBaru()">+ Tambah Pengguna</button></div>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-pengguna','ids-pengguna')">
  <h2>Daftar Pengguna</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-pengguna" method="post" action="<?= base_url('pengguna/hapus-massal') ?>" data-konfirmasi-judul="Hapus Pengguna Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-pengguna"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} pengguna terpilih? Akun Anda sendiri dan admin terakhir tidak dapat dihapus dan akan dilewati.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <span class="kecil-teks">Tidak ada tombol Kosongkan di modul ini — daftar pengguna tidak boleh dikosongkan.</span>
  </div>
<?php
$ikonPensil = '<span class="ikon-tombol" aria-hidden="true">' . ikonSvg('pensil') . '</span>';
$barisPengguna = [];
foreach ($daftar as $u) {
    $aksi = '<button type="button" class="tombol kecil abu" onclick="bukaPenggunaUbah(' . (int) $u['id'] . ')">' . $ikonPensil . ' Ubah</button>';
    if ((int) $u['id'] !== $saya) {
        $aksi .= ' <form class="inline" method="post" action="' . base_url('pengguna/' . $u['id'] . '/hapus') . '" data-konfirmasi="Hapus pengguna ' . esc($u['username'], 'attr') . '?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>';
    }
    $barisPengguna[] = [
        'sel' => [
            ['html' => '<input type="checkbox" data-pilih value="' . (int) $u['id'] . '" x-model="terpilih">'],
            ['html' => '<b>' . esc($u['username']) . '</b>' . ((int) $u['id'] === $saya ? ' <span class="lencana biru">Anda</span>' : '')],
            (string) $u['nama'],
            ['html' => '<span class="lencana ' . ($u['peran'] === 'admin' ? 'biru' : 'abu') . '">' . esc(ucfirst($u['peran'])) . '</span>'],
            (string) tgl($u['created_at'] ?? null),
            ['html' => $aksi, 'gaya' => 'white-space:nowrap'],
        ],
    ];
}
echo tabel_bantu([
    'cari' => 'Cari pengguna…',
    'paginasi' => 25,
    'kolom' => [
        ['judul_html' => '<input type="checkbox" title="Pilih semua pengguna" @change="pilihSemua($event)">', 'lebar' => '34px'],
        ['judul' => 'Username'],
        ['judul' => 'Nama'],
        ['judul' => 'Peran'],
        ['judul' => 'Terdaftar'],
        ['judul' => 'Aksi'],
    ],
    'baris' => $barisPengguna,
]);
?>
</div>

<?php
$opsiPeran = [];
foreach ($pilihan_peran as $p) {
    $opsiPeran[$p] = ucfirst($p);
}
?>
<?= form_modal([
    'id' => 'modal-pengguna-baru',
    'judul' => 'Tambah Pengguna',
    'aksi' => base_url('pengguna/simpan'),
    'lebar' => 560,
    'kolom' => [
        ['nama' => 'username', 'label' => 'Username', 'jenis' => 'teks', 'wajib' => true, 'placeholder' => 'mis. sarmin'],
        ['nama' => 'nama', 'label' => 'Nama lengkap', 'jenis' => 'teks', 'wajib' => true, 'placeholder' => 'mis. Sarmin'],
        ['nama' => 'peran', 'label' => 'Peran', 'jenis' => 'pilihan', 'opsi' => $opsiPeran],
        ['nama' => 'sandi', 'label' => 'Kata sandi awal (min. 6 karakter)', 'jenis' => 'teks', 'wajib' => true, 'placeholder' => 'dibagikan ke pengguna, nanti diganti sendiri'],
    ],
    'tombol' => ['simpan_teks' => 'Tambah Pengguna'],
]) ?>
<?= form_modal([
    'id' => 'modal-pengguna-ubah',
    'judul' => 'Ubah Pengguna',
    'judul_ubah' => 'Ubah Pengguna',
    'aksi' => base_url('pengguna/simpan'),
    'lebar' => 560,
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama lengkap', 'jenis' => 'teks', 'wajib' => true],
        ['nama' => 'peran', 'label' => 'Peran', 'jenis' => 'pilihan', 'opsi' => $opsiPeran],
        ['nama' => 'sandi', 'label' => 'Kata sandi baru — kosongkan bila tidak diganti', 'jenis' => 'teks', 'placeholder' => 'min. 6 karakter'],
    ],
    'tombol' => ['simpan_teks' => 'Simpan Perubahan'],
]) ?>
<script type="application/json" id="data-pengguna"><?= json_encode(array_column($daftar ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
// Jembatan modal Pengguna: hanya mengoper data baris ke bukaFormModal dan
// menyinkronkan widget select-cari sesudahnya.
(function () {
  var dataPengguna = {};
  try { dataPengguna = JSON.parse(document.getElementById('data-pengguna').textContent || '{}'); } catch (e) {}
  function sinkronSelect(idModal) {
    var m = document.getElementById(idModal);
    if (m && window.perbaruiSelectCari) {
      m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
  }
  window.bukaPenggunaBaru = function () {
    window.bukaFormModal('modal-pengguna-baru', { peran: 'operator' });
    sinkronSelect('modal-pengguna-baru');
  };
  window.bukaPenggunaUbah = function (id) {
    var u = dataPengguna[id];
    if (!u) return;
    var m = document.getElementById('modal-pengguna-ubah');
    if (m) m.dataset.judulUbah = 'Ubah Pengguna: ' + (u.username || '');
    window.bukaFormModal('modal-pengguna-ubah', {
      id: u.id,
      nama: u.nama || '',
      peran: u.peran || 'operator',
      sandi: ''
    });
    sinkronSelect('modal-pengguna-ubah');
  };
})();
</script>

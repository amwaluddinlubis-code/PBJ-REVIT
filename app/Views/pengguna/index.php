<div class="panel">
  <h2>Tambah Pengguna</h2>
  <form method="post" action="<?= base_url('pengguna/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Username<input type="text" name="username" required placeholder="mis. sarmin"></label>
      <label>Nama lengkap<input type="text" name="nama" required placeholder="mis. Sarmin"></label>
      <label>Peran
        <select name="peran">
          <?php foreach ($pilihan_peran as $p): ?><option value="<?= esc($p) ?>"<?= $p === 'operator' ? ' selected' : '' ?>><?= esc(ucfirst($p)) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Kata sandi awal (min. 6 karakter)<input type="text" name="sandi" required placeholder="dibagikan ke pengguna, nanti diganti sendiri"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Pengguna</button></div>
    </div>
  </form>
</div>

<div class="panel">
  <h2>Daftar Pengguna</h2>
  <table data-paginasi="25">
    <tr><th>Username</th><th>Nama</th><th>Peran</th><th>Terdaftar</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $u): ?>
    <tr>
      <td><b><?= esc($u['username']) ?></b><?= (int) $u['id'] === $saya ? ' <span class="lencana biru">Anda</span>' : '' ?></td>
      <td><?= esc($u['nama']) ?></td>
      <td><span class="lencana <?= $u['peran'] === 'admin' ? 'biru' : 'abu' ?>"><?= esc(ucfirst($u['peran'])) ?></span></td>
      <td><?= esc(substr((string) ($u['created_at'] ?? ''), 0, 10)) ?></td>
      <td style="white-space:nowrap">
        <button type="button" class="kecil" onclick="isiUbah(<?= (int) $u['id'] ?>, '<?= esc($u['username'], 'js') ?>', '<?= esc($u['nama'], 'js') ?>', '<?= esc($u['peran'], 'js') ?>')">Ubah</button>
        <?php if ((int) $u['id'] !== $saya): ?>
        <form class="inline" method="post" action="<?= base_url('pengguna/' . $u['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus pengguna <?= esc($u['username'], 'js') ?>?')"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="panel" id="panel-ubah" style="display:none">
  <h2>Ubah Pengguna: <span id="ubah-username"></span></h2>
  <form method="post" id="form-ubah" action="">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama lengkap<input type="text" name="nama" id="ubah-nama" required></label>
      <label>Peran
        <select name="peran" id="ubah-peran">
          <?php foreach ($pilihan_peran as $p): ?><option value="<?= esc($p) ?>"><?= esc(ucfirst($p)) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Kata sandi baru — kosongkan bila tidak diganti<input type="text" name="sandi" placeholder="min. 6 karakter"></label>
      <div class="aksi md:col-span-2"><button type="submit">Simpan Perubahan</button></div>
    </div>
  </form>
</div>

<script>
function isiUbah(id, username, nama, peran) {
  document.getElementById('panel-ubah').style.display = 'block';
  document.getElementById('ubah-username').textContent = username;
  document.getElementById('ubah-nama').value = nama;
  document.getElementById('ubah-peran').value = peran;
  document.getElementById('form-ubah').action = '<?= base_url('pengguna') ?>/' + id + '/ubah';
  document.getElementById('panel-ubah').scrollIntoView({ behavior: 'smooth' });
}
</script>

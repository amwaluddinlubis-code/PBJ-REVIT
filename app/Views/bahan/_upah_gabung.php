<?php /* Partial bahan/_panel_upah: blok Gabungkan Jenis Upah (manual) + skrip penonaktif sumber yang sama dengan tujuan. */ ?>
<h3 style="margin:14px 0 6px">Gabungkan Jenis Upah</h3>
  <p style="margin-top:0;color:var(--ta-teks-2)">Untuk jenis yang namanya berbeda tetapi pekerjaannya sama (mis. "Kepala Tukang batu" dan "Kepala Tukang cat" dilebur ke "Kepala Tukang"): pilih jenis tujuan, centang jenis yang dilebur, lalu gabungkan. Komponen analisa dan riwayat pembayaran berjenis sumber ikut berubah menjadi jenis tujuan, dan baris sumber dihapus dari master.</p>
  <form method="post" action="<?= base_url('bahan/upah-gabung-manual') ?>" data-konfirmasi="Gabungkan jenis tercentang ke jenis tujuan? Nama jenis pada komponen analisa dan riwayat pembayaran ikut diubah.">
    <?= csrf_field() ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Jenis tujuan <?= select_cari('tujuan', array_map(static fn ($u) => ['value' => (int) $u['id'], 'label' => $u['jenis'] . ' — ' . rp($u['tarif'])], $upah), null, ['id' => 'gabung-tujuan']) ?></label>
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

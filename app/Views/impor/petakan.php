<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>

<div class="panel">
  <h2>Tinjau &amp; Petakan Impor</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Berkas <b><?= esc($stage['nama']) ?></b> akan diimpor ke sekolah <b><?= esc($nama_sekolah) ?></b> (format: <?= $stage['format'] === 'template' ? 'Template Manual' : 'Workbook Perencana' ?>). <b>Belum ada data yang ditulis</b> — periksa daftar sheet di bawah ini: pilih tujuan impor tiap sheet, atau tandai <b>Lewati</b> untuk yang tidak akan diimpor. Nama gedung dapat diubah sebelum impor.</p>
  <p class="kecil-teks" style="margin-bottom:0">Sheet <b>Data Referensi</b> (daftar harga, analisa, harsat, rekap pagu, data sekolah) adalah bahan acuan bagi sheet lain — melewatkan Analisa membuat harga pekerjaan BOQ hanya mengandalkan harga yang tertulis di sheet BOQ itu sendiri.</p>
</div>

<form method="post" action="<?= base_url('impor/eksekusi') ?>" id="form-petakan" data-berkas="<?= esc($stage['nama']) ?>" data-sekolah="<?= esc($nama_sekolah) ?>" data-konfirmasi-judul="Konfirmasi Impor" data-konfirmasi-tombol="Ya, Impor Sekarang">
  <?= csrf_field() ?>
  <div class="panel">
    <table>
      <tr><th style="width:20%">Sheet</th><th style="width:19%">Jenis Terdeteksi</th><th class="angka" style="width:12%">Isi</th><th style="width:18%">Tujuan Impor</th><th>Nama Gedung (bila tujuan Gedung)</th><th style="width:9%">Aksi</th></tr>
      <?php foreach ($stage['lembar'] as $i => $l): ?>
      <?php
        $opsi = match ($l['kategori']) {
            'referensi' => ['referensi' => 'Data Referensi', 'lewati' => 'Lewati'],
            'gedung' => ['gedung' => 'Gedung', 'non_gedung' => 'Biaya Non-Gedung', 'lewati' => 'Lewati'],
            'non_gedung' => ['non_gedung' => 'Biaya Non-Gedung', 'gedung' => 'Gedung', 'lewati' => 'Lewati'],
            default => ['lewati' => 'Lewati', 'gedung' => 'Gedung', 'non_gedung' => 'Biaya Non-Gedung'],
        };
      ?>
      <tr>
        <td><b><?= esc($l['nama']) ?></b><input type="hidden" name="sheet[<?= $i ?>]" value="<?= esc($l['nama']) ?>"></td>
        <td><?= esc($l['jenis']) ?></td>
        <td class="angka"><?php if ($l['baris'] > 0): ?><?= number_format($l['baris'], 0, ',', '.') ?> baris<?php endif; ?><?php if ($l['total'] > 0): ?><br><?= $rp($l['total']) ?><?php endif; ?></td>
        <td>
          <select data-select-cari name="tujuan[<?= $i ?>]" data-baris="<?= $i ?>">
            <?php foreach ($opsi as $nilai => $label): ?>
            <option value="<?= $nilai ?>" <?= $l['saran'] === $nilai ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="text" name="nama_gedung[<?= $i ?>]" value="<?= esc($l['nama']) ?>" data-nama="<?= $i ?>" <?= $l['saran'] === 'gedung' ? '' : 'disabled' ?>></td>
        <td><button type="button" class="btn-hapus-baris" data-hapus="<?= $i ?>" title="Hapus baris ini — sheet akan dilewati">Hapus</button></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div id="penanda-lewati"></div>
    <p class="kecil-teks" style="margin:10px 0 0">Tombol <b>Hapus</b> membuang baris dari daftar — sheet-nya dianggap <b>Dilewati</b> (tidak diimpor, data lama yang sudah ada tidak berubah). Baris yang terhapus berpindah ke <b>bagian bawah daftar</b> dan masih bisa dikembalikan sebelum impor dijalankan.</p>
    <div class="aksi" style="justify-content:flex-start;gap:10px;margin-top:14px">
      <button type="submit">Impor Sekarang</button>
      <button type="submit" class="tombol abu" formaction="<?= base_url('impor/batal') ?>">Batal — Jangan Impor</button>
    </div>
    <p class="kecil-teks" style="margin-bottom:0">Sheet bertujuan Gedung menggantikan pekerjaan gedung dengan nama/sumber yang sama dari impor sebelumnya; sheet bertujuan Biaya Non-Gedung menggantikan baris impor sebelumnya (baris manual Anda tidak terhapus).</p>
  </div>
</form>

<script>
document.querySelectorAll('select[data-baris]').forEach(function (sel) {
  sel.addEventListener('change', function () {
    var input = document.querySelector('input[data-nama="' + sel.dataset.baris + '"]');
    if (input) input.disabled = sel.value !== 'gedung';
  });
});

// Hapus baris dari daftar pemetaan: baris disembunyikan, isinya dinonaktifkan,
// dan digantikan penanda tersembunyi tujuan=lewati — sheet yang barisnya
// dihapus berarti DILEWATI saat impor. Masih bisa dikembalikan.
(function () {
  var form = document.getElementById('form-petakan');
  var wadah = document.getElementById('penanda-lewati');
  if (!form || !wadah) return;
  form.addEventListener('click', function (e) {
    var btnHapus = e.target.closest('[data-hapus]');
    var btnKembali = e.target.closest('[data-kembalikan]');
    if (btnHapus) {
      var i = btnHapus.dataset.hapus;
      var tr = btnHapus.closest('tr');
      var nama = tr.querySelector('input[type="hidden"]').value;
      var sel = tr.querySelector('select[name^="tujuan"]');
      if (sel) {
        tr.dataset.tujuanAsal = sel.value;
        sel.value = 'lewati';
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      tr.querySelectorAll('input, select').forEach(function (el) { el.disabled = true; });
      tr.style.display = 'none';
      var ph = document.createElement('tr');
      ph.className = 'baris-terhapus';
      ph.dataset.placeholder = i;
      ph.innerHTML = '<td colspan="6">Sheet <b></b> dihapus dari daftar — akan dilewati. <button type="button" class="btn-kembalikan" data-kembalikan="' + i + '">Kembalikan</button></td>';
      ph.querySelector('b').textContent = nama;
      tr.parentNode.appendChild(ph); // baris terhapus berkumpul di bagian bawah tabel
      var mk = document.createElement('span');
      mk.dataset.penanda = i;
      mk.innerHTML = '<input type="hidden" name="sheet[' + i + ']" value="">' +
        '<input type="hidden" name="tujuan[' + i + ']" value="lewati">';
      mk.querySelector('input').value = nama;
      wadah.appendChild(mk);
      if (window.tampilToast) window.tampilToast('Sheet "' + nama + '" dihapus dari daftar — akan dilewati.', 'sukses');
    }
    if (btnKembali) {
      var j = btnKembali.dataset.kembalikan;
      var penanda = wadah.querySelector('[data-penanda="' + j + '"]');
      if (penanda) penanda.remove();
      var placeholder = form.querySelector('tr[data-placeholder="' + j + '"]');
      if (placeholder) placeholder.remove();
      var tombolHapus = form.querySelector('[data-hapus="' + j + '"]');
      var baris = tombolHapus ? tombolHapus.closest('tr') : null;
      if (baris) {
        baris.style.display = '';
        var sel2 = baris.querySelector('select[name^="tujuan"]');
        if (sel2 && baris.dataset.tujuanAsal) { sel2.value = baris.dataset.tujuanAsal; sel2.dispatchEvent(new Event('change', { bubbles: true })); }
        baris.querySelectorAll('input, select').forEach(function (el) { el.disabled = false; });
        var inp = baris.querySelector('input[data-nama]');
        if (inp && sel2) inp.disabled = sel2.value !== 'gedung';
        // kembalikan ke akhir daftar aktif, sebelum baris-baris terhapus
        var phLain = form.querySelector('tr.baris-terhapus');
        if (phLain) phLain.before(baris);
        else baris.parentNode.appendChild(baris);
      }
    }
  });
})();

// Dialog konfirmasi impor: susun ringkasan pemetaan TERKINI (sesuai pilihan
// pengguna saat ini) lalu serahkan ke modal konfirmasi di layout lewat
// data-konfirmasi-html. Tombol Batal tidak ikut dicegat.
(function () {
  var form = document.getElementById('form-petakan');
  if (!form) return;
  function escHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  form.addEventListener('submit', function (e) {
    var tombol = e.submitter;
    if (tombol && (tombol.getAttribute('formaction') || '').indexOf('batal') !== -1) {
      delete form.dataset.konfirmasi;
      delete form.dataset.konfirmasiHtml;
      return;
    }
    var grup = { gedung: [], non_gedung: [], referensi: [], lewati: [] };
    var totalNilai = 0;
    form.querySelectorAll('select[name^="tujuan"]').forEach(function (sel) {
      var tr = sel.closest('tr');
      var nama = tr.querySelector('input[type="hidden"]').value;
      var isi = tr.children[2].innerText.trim().replace(/\s+/g, ' ');
      var cocok = isi.match(/Rp ([\d.]+)/);
      var nilai = cocok ? parseInt(cocok[1].replace(/\./g, ''), 10) : 0;
      if (sel.value === 'gedung') {
        var inp = tr.querySelector('input[data-nama]');
        if (inp && inp.value.trim() !== '') nama = inp.value.trim();
        totalNilai += nilai;
      } else if (sel.value === 'non_gedung') {
        totalNilai += nilai;
      }
      if (grup[sel.value]) grup[sel.value].push({ nama: nama, isi: isi });
    });
    var label = { gedung: 'Gedung', non_gedung: 'Biaya Non-Gedung', referensi: 'Data Referensi' };
    var html = '<div class="mk-rinci"><p>Berkas <b>' + escHtml(form.dataset.berkas) + '</b> akan diimpor ke sekolah <b>' + escHtml(form.dataset.sekolah) + '</b> dengan pemetaan berikut:</p>';
    ['gedung', 'non_gedung', 'referensi'].forEach(function (k) {
      if (!grup[k].length) return;
      html += '<p><b>' + label[k] + ' — ' + grup[k].length + ' sheet</b></p><ul>';
      grup[k].forEach(function (it) {
        html += '<li>' + escHtml(it.nama) + (it.isi ? ' — ' + escHtml(it.isi) : '') + '</li>';
      });
      html += '</ul>';
    });
    if (!grup.gedung.length && !grup.non_gedung.length && !grup.referensi.length) {
      html += '<p><b>Tidak ada sheet yang akan diimpor</b> — semua ditandai Lewati.</p>';
    } else {
      html += '<p>Total nilai sheet Gedung + Biaya Non-Gedung: <b>Rp ' + totalNilai.toLocaleString('id-ID') + '</b></p>';
    }
    if (grup.lewati.length) {
      html += '<p>' + grup.lewati.length + ' sheet dilewati (tidak diimpor).</p>';
    }
    html += '<p>Sheet Gedung dan Biaya Non-Gedung menggantikan data impor sebelumnya; baris manual Anda tidak ikut terhapus.</p></div>';
    form.dataset.konfirmasi = 'impor';
    form.dataset.konfirmasiHtml = html;
  });
})();
</script>

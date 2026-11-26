<?php /* Partial invoice/index: modal form Invoice (baru & ubah) memakai
   helper form_modal — simpan AJAX ke invoice/simpan (id tersembunyi
   membedakan baru/ubah; invoice/perbarui/{id} tetap ada untuk halaman
   form mandiri invoice/form.php sebagai cadangan). Hapus lewat
   aksi_hapus form_modal (konfirmasi standar). Data baris dioper
   sebagai JSON di halaman (#data-invoice). */ ?>
<?php
$opsiSpk = ['' => '— pilih SPK —'];
foreach ($spk['list'] ?? [] as $s) {
    $w = $spk['warisan'][(int) $s['id']] ?? [];
    $opsiSpk[(int) $s['id']] = [
        'label' => 'SPK ' . (int) $s['nomor'] . ' — ' . ($s['paket'] ?? '') . ' (total Rp ' . number_format((float) $s['total'], 0, ',', '.') . ')',
        'data' => [
            'total' => (int) $s['total'],
            'sisa' => (int) ($w['sisa'] ?? 0),
            'bast-tanggal' => (string) ($w['bast']['tanggal'] ?? ''),
            'status' => (string) ($s['status'] ?? 'Aktif'),
        ],
    ];
}
?>
<?= form_modal([
    'id' => 'modal-invoice',
    'judul' => 'Invoice Baru',
    'judul_ubah' => 'Ubah Invoice',
    'aksi' => base_url('invoice/simpan'),
    'aksi_hapus' => base_url('invoice/hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus invoice ini?',
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'nomor_urut', 'label' => 'Nomor Urut', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'no_toko', 'label' => 'No. Invoice Toko', 'jenis' => 'teks'],
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal', 'min' => $buku_min ?? '', 'max' => $buku_max ?? '', 'petunjuk' => 'Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'spk_id', 'label' => 'SPK', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiSpk, 'petunjuk' => 'Tanggal SPK, BAP & BAST diwarisi otomatis dari SPK'],
        ['nama' => 'nilai', 'label' => 'Nilai Invoice', 'jenis' => 'uang', 'petunjuk' => 'Otomatis = sisa nilai BAST, boleh diubah'],
        ['nama' => 'status', 'label' => 'Status', 'jenis' => 'pilihan', 'opsi' => ['Belum Bayar' => 'Belum Bayar', 'Sudah Bayar' => 'Sudah Bayar']],
        ['nama' => 'tanggal_bayar', 'label' => 'Tanggal Pembayaran', 'jenis' => 'tanggal', 'min' => $buku_min ?? '', 'max' => $buku_max ?? '',
            'petunjuk' => 'Hanya untuk status Sudah Bayar — pembayaran tercatat otomatis di Jurnal Pembayaran. Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'kanal_bayar', 'label' => 'Kanal Pembayaran', 'jenis' => 'pilihan', 'opsi' => ['tunai' => 'Tunai (Buku Kas Tunai)', 'bank' => 'Bank / Non-Tunai (Buku Bank)'], 'petunjuk' => 'Bawaan tunai — hanya pembayaran non-tunai yang masuk Buku Bank'],
    ],
    'catatan_html' => '<div id="info-warisan-invoice" class="kecil-teks" style="margin-top:10px">Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.</div>',
    'tombol' => ['simpan_teks' => 'Simpan', 'hapus_teks' => 'Hapus Invoice'],
]) ?>
<script type="application/json" id="data-invoice"><?= json_encode(array_column($inv ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="data-warisan-spk"><?= json_encode($spk['warisan'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
// Jembatan modal Invoice: mengoper data baris ke bukaFormModal,
// menyinkronkan widget select-cari, dan menampilkan warisan SPK
// (tanggal SPK + BAP/BAST terakhir beserta tanggalnya) — BAP/BAST
// tidak lagi dipilih pengguna; server mewarisinya dari SPK.
(function () {
  var dataInv = {};
  try { dataInv = JSON.parse(document.getElementById('data-invoice').textContent || '{}'); } catch (e) {}
  var warisan = {};
  try { warisan = JSON.parse(document.getElementById('data-warisan-spk').textContent || '{}'); } catch (e) {}
  function tglIndo(iso) {
    if (!iso) return '—';
    var p = String(iso).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  var sedangUbah = null;
  function rpIndo(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
  function sisaEfektif(spkId) {
    var w = warisan[spkId];
    if (!w) return 0;
    var sisa = Number(w.sisa || 0);
    if (sedangUbah && String(sedangUbah.spk_id) === String(spkId)) sisa += Number(sedangUbah.nilai || 0);
    return sisa;
  }
  function saringSpk() {
    var m = document.getElementById('modal-invoice');
    if (!m) return;
    var sel = m.querySelector('select[name="spk_id"]');
    var tglInv = m.querySelector('input[name="tanggal"]');
    if (!sel) return;
    var tgl = tglInv ? tglInv.value : '';
    Array.prototype.forEach.call(sel.options, function (opt) {
      if (!opt.value) return;
      var bastTgl = opt.getAttribute('data-bast-tanggal') || '';
      var layak = bastTgl !== '' && sisaEfektif(opt.value) > 0 && (!tgl || bastTgl <= tgl) && opt.getAttribute('data-status') !== 'Batal';
      opt.disabled = !layak && opt.value !== sel.value;
    });
    if (window.perbaruiSelectCari) window.perbaruiSelectCari(sel);
  }
  function tampilWarisan(isiNilai) {
    var m = document.getElementById('modal-invoice');
    var kotak = document.getElementById('info-warisan-invoice');
    if (!m || !kotak) return;
    var sel = m.querySelector('select[name="spk_id"]');
    var w = sel ? warisan[sel.value] : null;
    if (!w) {
      kotak.textContent = 'Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.';
      return;
    }
    var bap = w.bap ? 'BAP urut ' + w.bap.nomor_urut + ' tgl ' + tglIndo(w.bap.tanggal) + ' (' + rpIndo(w.nilai_bap) + ')' : 'BAP belum ada';
    var bast = w.bast ? 'BAST urut ' + w.bast.nomor_urut + ' tgl ' + tglIndo(w.bast.tanggal) + ' (' + rpIndo(w.nilai_bast) + ')' : 'BAST belum ada';
    kotak.textContent = 'Diwarisi dari SPK: tgl SPK ' + tglIndo(w.tanggal) + ' · ' + bap + ' · ' + bast + ' · sisa belum ter-invoice ' + rpIndo(sisaEfektif(sel.value)) + '.';
    if (isiNilai) {
      var nilai = m.querySelector('input[name="nilai"]');
      if (nilai) {
        nilai.value = sisaEfektif(sel.value);
        nilai.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }
  }
  // Tanggal Pembayaran: hanya tampil untuk status Sudah Bayar; nilai
  // bawaannya merekomendasikan tanggal jurnal terakhir (dijepit Bulan
  // Terbuka) — pembayaran tercatat otomatis saat disimpan.
  var tglJurnalTerakhir = <?= json_encode($tgl_jurnal_terakhir ?? null) ?>;
  var bukuMin = <?= json_encode($buku_min ?? '') ?>;
  var bukuMax = <?= json_encode($buku_max ?? '') ?>;
  function isiTanggal(inp, iso) {
    if (!inp || !iso) return;
    if (inp.classList.contains('isian-tanggal')) {
      var p = iso.split('-');
      inp.value = p[2] + '-' + p[1] + '-' + p[0];
    } else {
      inp.value = iso;
    }
    inp.dispatchEvent(new Event('input', { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
  }
  function rekomendasiBayar(tglInvoice) {
    var calon = tglJurnalTerakhir || '';
    if (tglInvoice && tglInvoice > calon) calon = tglInvoice;
    if (!calon) calon = <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?>;
    if (bukuMin && calon < bukuMin) calon = bukuMin;
    if (bukuMax && calon > bukuMax) calon = bukuMax;
    return calon;
  }
  function segarkanTanggalBayar() {
    var m = document.getElementById('modal-invoice');
    if (!m) return;
    var selStatus = m.querySelector('select[name="status"]');
    var inpBayar = m.querySelector('input[name="tanggal_bayar"]');
    if (!selStatus || !inpBayar) return;
    var bungkus = inpBayar.closest('.fm-field');
    var sudah = selStatus.value === 'Sudah Bayar';
    if (bungkus) bungkus.style.display = sudah ? '' : 'none';
    inpBayar.disabled = !sudah;
    var selKanal = m.querySelector('select[name="kanal_bayar"]');
    if (selKanal) {
      var bungkusKanal = selKanal.closest('.fm-field');
      if (bungkusKanal) bungkusKanal.style.display = sudah ? '' : 'none';
      selKanal.disabled = !sudah;
    }
    if (sudah && !inpBayar.value) {
      var inpTgl = m.querySelector('input[name="tanggal"]');
      var tglInv = '';
      if (inpTgl) {
        if (inpTgl.classList.contains('isian-tanggal')) {
          var p = inpTgl.value.split('-');
          tglInv = p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : '';
        } else {
          tglInv = inpTgl.value;
        }
      }
      isiTanggal(inpBayar, rekomendasiBayar(tglInv));
    }
  }
  function sinkronSelect() {
    var m = document.getElementById('modal-invoice');
    if (m && window.perbaruiSelectCari) {
      m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
    saringSpk();
    tampilWarisan(false);
    segarkanTanggalBayar();
  }
  document.addEventListener('change', function (e) {
    if (!e.target || !e.target.closest || !e.target.closest('#modal-invoice')) return;
    if (e.target.name === 'spk_id') tampilWarisan(true);
    if (e.target.name === 'tanggal') saringSpk();
    if (e.target.name === 'status') segarkanTanggalBayar();
  });
  window.bukaInvoiceBaru = function () {
    sedangUbah = null;
    window.bukaFormModal('modal-invoice', { nomor_urut: <?= (int) ($nomor_baru ?? 1) ?>, status: 'Belum Bayar', tanggal: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
    sinkronSelect();
  };
  window.bukaInvoiceUbah = function (id) {
    var i = dataInv[id];
    if (!i) return;
    sedangUbah = i;
    window.bukaFormModal('modal-invoice', {
      id: i.id,
      nomor_urut: i.nomor_urut,
      no_toko: i.no_toko || '',
      tanggal: i.tanggal || '',
      spk_id: i.spk_id || '',
      nilai: i.nilai == null ? '' : i.nilai,
      status: i.status || 'Belum Bayar'
    });
    sinkronSelect();
  };
})();
</script>

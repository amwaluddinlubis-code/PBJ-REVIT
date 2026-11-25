<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $sid = $this->sid();

        $totalRab = 0.0; // Diisi dari total Nilai Analisa panel gedung di bawah (harga AHSP berjalan).
        $totalSpk = (float) $db->query('SELECT COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id IS NOT NULL AND sekolah_id = ? AND is_deleted = 0', [$sid])->getRow()->t;
        $realisasi = 0.0;
        $petaPpn = $this->ppnSpkMap();
        foreach ($db->table('pembayaran p')->select('i.nilai, i.spk_id')->join('invoice i', 'i.id = p.invoice_id AND i.is_deleted = 0')->join('spk s', 's.id = i.spk_id AND s.is_deleted = 0')->where(['s.sekolah_id' => $sid, 'p.is_deleted' => 0])->get()->getResultArray() as $r) {
            $pjk = $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($petaPpn, (int) $r['spk_id'], (float) $r['nilai']));
            $realisasi += $pjk['dibayar'];
        }

        $tanpaSpk = $db->query('SELECT COUNT(*) j, COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id IS NULL AND sekolah_id = ? AND is_deleted = 0', [$sid])->getRow();

        $invoiceCek = $db->query(
            'SELECT i.nomor_urut, i.nilai, s.nomor AS spk_nomor, COALESCE(SUM(d.qty*d.harga),0) AS total_spk
             FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             LEFT JOIN spk_detail d ON d.spk_id = s.id AND d.is_deleted = 0
             WHERE s.sekolah_id = ? AND i.is_deleted = 0
             GROUP BY i.id HAVING i.nilai <> total_spk',
            [$sid]
        )->getResultArray();

        // Sisa bahan negatif: pemakaian SPK melebihi kebutuhan RAB. Patokan
        // qty RAB sama persis dengan kolom SISA di Master Bahan: kebutuhan
        // BOQ x koefisien analisa untuk bahan yang tertaut pekerjaan gedung
        // didanai, dan jumlah kolom warisan qty_rkb/adm/toilet untuk bahan
        // lainnya. Kolom warisan itu selalu 0 pada sekolah hasil impor
        // perencana, jadi memakainya untuk semua bahan membuat peringatan
        // ini berbunyi palsu begitu ada satu saja detail SPK.
        $petaButuh = [];
        foreach ($this->kebutuhanBahanSekolah()['tertaut'] as $b) {
            $petaButuh[(int) $b['bahan_id']] = (float) $b['qty_butuh'];
        }
        $pemakaian = $db->query(
            'SELECT b.id, b.kode, b.nama, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_warisan,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id AND d.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0
             GROUP BY b.id HAVING terpakai > 0',
            [$sid]
        )->getResultArray();
        $sisaNegatif = [];
        foreach ($pemakaian as $t) {
            // Bahan tertaut memakai kebutuhan analisa; bahan lain memakai
            // jumlah kolom warisan, sama seperti Master Bahan.
            $qtyRab = $petaButuh[(int) $t['id']] ?? (float) $t['qty_warisan'];
            if ((float) $t['terpakai'] > $qtyRab) {
                $sisaNegatif[] = [
                    'kode' => $t['kode'], 'nama' => $t['nama'],
                    'qty_rab' => $qtyRab, 'terpakai' => (float) $t['terpakai'],
                ];
            }
        }
        usort($sisaNegatif, static fn ($a, $b) => ($b['terpakai'] - $b['qty_rab']) <=> ($a['terpakai'] - $a['qty_rab']));

        $perSpk = $db->query(
            'SELECT s.nomor, s.tanggal, s.paket, s.status, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia,
                    COUNT(d.id) AS jumlah, COALESCE(SUM(d.qty*d.harga),0) AS nilai
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             LEFT JOIN spk_detail d ON d.spk_id = s.id AND d.is_deleted = 0
             WHERE s.sekolah_id = ? AND s.is_deleted = 0
             GROUP BY s.id ORDER BY s.nomor',
            [$sid]
        )->getResultArray();

        $identitas = $this->sekolahAktif();

        // Peringatan tahap 2: kemajuan fisik >= 50% tetapi dana tahap 2 belum diterima
        $paguDana = (float) ($identitas['total_anggaran'] ?? 0);
        $perTahap = $this->penerimaanPerTahap();
        $totalBoq = 0.0;
        $tertimbang = 0.0;
        foreach ($this->semuaGedung() as $g) {
            $totalBoq += (float) $g['nilai_boq'];
            $tertimbang += (float) $g['nilai_boq'] * (float) $g['persen_fisik'];
        }
        $fisikRata = $totalBoq > 0 ? $tertimbang / $totalBoq : 0.0;
        $tahap2JatuhTempo = $paguDana > 0 && $fisikRata >= 50 && $perTahap[2] <= 0;

        // Deteksi pola pemecahan pembelian (dilarang Juknis): banyak
        // invoice kecil ke penyedia yang sama dalam waktu berdekatan
        // yang bila digabung melewati ambang ketentuan.
        $pecahCek = [];
        foreach ($db->query(
            'SELECT py.nama AS penyedia, YEARWEEK(i.tanggal, 3) AS yw, COUNT(*) AS j, SUM(i.nilai) AS total
             FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             LEFT JOIN penyedia py ON py.id = s.penyedia_id
             WHERE s.sekolah_id = ? AND i.is_deleted = 0 AND i.tanggal IS NOT NULL AND i.nilai <= 50000000
             GROUP BY py.nama, yw HAVING j >= 2 AND total > 50000000',
            [$sid]
        )->getResultArray() as $r) {
            $pecahCek[] = 'Pola pemecahan terdeteksi: ' . (int) $r['j'] . ' invoice ke ' . ($r['penyedia'] ?? 'penyedia yang sama')
                . ' dalam minggu yang sama, masing-masing ≤ Rp50 juta tetapi totalnya ' . $this->rp($r['total'])
                . ' — gabungan nilai menentukan bentuk dokumen & jumlah calon penyedia; jangan memecah pembelian untuk menghindari ketentuan.';
        }
        foreach ($db->query(
            'SELECT py.nama AS penyedia, DATE_FORMAT(i.tanggal, "%Y-%m") AS bln, COUNT(*) AS j
             FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             LEFT JOIN penyedia py ON py.id = s.penyedia_id
             WHERE s.sekolah_id = ? AND i.is_deleted = 0 AND i.tanggal IS NOT NULL AND i.nilai <= 2000000
             GROUP BY py.nama, bln HAVING j >= 3',
            [$sid]
        )->getResultArray() as $r) {
            $pecahCek[] = 'Pola pemecahan terdeteksi: ' . (int) $r['j'] . ' invoice ≤ Rp2 juta ke ' . ($r['penyedia'] ?? 'penyedia yang sama')
                . ' pada ' . $r['bln'] . ' — pembelian maksimal Rp2 juta yang bebas PPh 22 tidak boleh dipecah-pecah.';
        }

        // ALERT master: bahan/upah yang sama tetapi satuan atau harga
        // berbeda — harus diperbaiki dan digabung di Master Bahan & Upah
        // sebelum datanya konsisten dipakai modul lain.
        $masterKonflik = [];
        $kelompokBahan = [];
        foreach ($db->table('bahan')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $b) {
            $kelompokBahan[Bahan::normalNama($b['nama'])][] = $b;
        }
        foreach ($kelompokBahan as $anggota) {
            if (count($anggota) < 2) {
                continue;
            }
            $satuanUnik = array_unique(array_map(static fn ($x) => (string) $x['satuan'], $anggota));
            $hargaUnik = array_unique(array_map(static fn ($x) => (string) $x['harga_rab'], $anggota));
            if (count($satuanUnik) > 1 || count($hargaUnik) > 1) {
                $masterKonflik[] = 'Bahan "' . $anggota[0]['nama'] . '" tercatat ' . count($anggota) . ' kali dengan satuan/harga berbeda — perbaiki dan gabungkan di Master Bahan & Upah.';
            }
        }
        $kelompokUpah = [];
        foreach ($db->table('upah_master')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $u) {
            $kelompokUpah[Bahan::normalNama($u['jenis'])][] = $u;
        }
        foreach ($kelompokUpah as $anggota) {
            if (count($anggota) < 2) {
                continue;
            }
            $tarifUnik = array_unique(array_map(static fn ($x) => (string) $x['tarif'], $anggota));
            if (count($tarifUnik) > 1) {
                $masterKonflik[] = 'Jenis tenaga "' . $anggota[0]['jenis'] . '" tercatat ' . count($anggota) . ' kali dengan tarif berbeda — perbaiki dan gabungkan di Master Bahan & Upah.';
            }
        }

        $setTerpakaiDash = $this->idBahanDipakaiGedung();
        $tanpaHarga = 0;
        foreach ($db->query(
            'SELECT b.id FROM bahan b
             LEFT JOIN negosiasi n ON n.bahan_id = b.id AND n.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0
               AND (b.harga_rab IS NULL OR b.harga_rab = 0)
               AND (n.harga_nego IS NULL OR n.harga_nego = 0)
               AND (n.harga_penawaran IS NULL OR n.harga_penawaran = 0)',
            [$sid]
        )->getResultArray() as $r) {
            if (isset($setTerpakaiDash[(int) $r['id']])) {
                $tanpaHarga++;
            }
        }
        $tanpaTarif = (int) $db->table('upah_master')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->where('tarif <=', 0)->countAllResults();

        // Panel Pembangunan Gedung: bandingkan Nilai BOQ Asli (kolom
        // gedung.nilai_boq — angka dokumen perencana, BEKU; keputusan
        // AMWAL 25 Nov 2026) dengan Nilai Analisa (AHSP berjalan) agar
        // perubahan akibat perbaikan harga/tarif analisa terlihat per gedung.
        $panelGedung = [];
        $panelTotal = ['asli' => 0.0, 'analisa' => 0.0, 'perubahan' => 0.0];
        foreach ($this->semuaGedung() as $g) {
            $g['boq_asli'] = (float) $g['nilai_boq'];
            $g['nilai_analisa'] = $this->totalBoqGedung((int) $g['id']);
            $g['perubahan'] = $g['nilai_analisa'] - $g['boq_asli'];
            $panelTotal['asli'] += $g['boq_asli'];
            $panelTotal['analisa'] += $g['nilai_analisa'];
            $panelTotal['perubahan'] += $g['perubahan'];
            $panelGedung[] = $g;
        }
        $totalRab = $panelTotal['analisa'];
        // Biaya Non-Gedung tampil sebagai PANEL SENDIRI di dashboard
        // (permintaan AMWAL 25 Nov 2026) — tidak digabung ke panel
        // Pembangunan Gedung; total panel gedung tetap murni gedung.
        $verifikasi = $this->verifikasiAnggaran();
        $biayaNonGedung = (float) $verifikasi['biaya_lain'];
        $panelBiayaLain = $db->table('biaya_lain')
            ->select('kelompok, uraian, jumlah')
            ->where(['sekolah_id' => $sid, 'is_deleted' => 0])
            ->orderBy('kelompok')->orderBy('id')
            ->get()->getResultArray();

        return $this->tampil('dashboard/index', [
            'panelGedung' => $panelGedung, 'panelTotal' => $panelTotal,
            'biayaNonGedung' => $biayaNonGedung,
            'panelBiayaLain' => $panelBiayaLain,
            'tanpaHarga' => $tanpaHarga, 'tanpaTarif' => $tanpaTarif,
            'identitas'   => $identitas,
            'totalRab'    => $totalRab,
            'totalSpk'    => $totalSpk,
            'realisasi'   => $realisasi,
            'sisa'        => $totalRab - $realisasi,
            'tanpaSpk'    => $tanpaSpk,
            'invoiceCek'  => $invoiceCek,
            'sisaNegatif' => $sisaNegatif,
            'perSpk'      => $perSpk,
            'verifikasi'  => $verifikasi,
            'tahap2JatuhTempo' => $tahap2JatuhTempo,
            'fisikRata' => $fisikRata,
            'pecahCek' => $pecahCek,
            'masterKonflik' => $masterKonflik,
        ], 'Dashboard — Aplikasi PBJ Sekolah');
    }
}

<?php

namespace App\Controllers;

class Invoice extends BaseController
{
    private function totalSpk(int $spkId): float
    {
        $row = $this->db()->query('SELECT COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id = ? AND is_deleted = 0', [$spkId])->getRow();
        return (float) $row->t;
    }

    public function index()
    {
        $inv = $this->db()->query(
            'SELECT i.*, s.nomor AS spk_nomor, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia,
                    (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS total_spk
             FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.sekolah_id = ? AND i.is_deleted = 0 ORDER BY i.nomor_urut',
            [$this->sid()]
        )->getResultArray();
        // Data untuk modal form invoice (baru & ubah) di halaman register —
        // daftar SPK/BAP/BAST + nomor urut berikutnya, sama seperti baru().
        $row = $this->db()->query('SELECT MAX(nomor_urut) m FROM invoice')->getRow();
        return $this->tampil('invoice/index', [
            'inv'        => $inv,
            'spk'        => $this->daftarSpk(),
            'nomor_baru' => ((int) $row->m) + 1,
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
            'tgl_jurnal_terakhir' => $this->tanggalPembayaranTerakhir(),
        ] + $this->batasBuku(), 'Register Invoice — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(nomor_urut) m FROM invoice')->getRow();
        // Tautan dalam dari modul lain (mis. Detail SPK) dapat membawa
        // ?spk=<id> agar formulir langsung memilih SPK itu.
        $spkPilih = (int) $this->request->getGet('spk') ?: null;
        return $this->tampil('invoice/form', [
            'i' => ['nomor_urut' => ((int) $row->m) + 1, 'status' => 'Belum Bayar', 'spk_id' => $spkPilih, 'tanggal' => $this->tanggalBulanBekerja()],
            'tgl_jurnal_terakhir' => $this->tanggalPembayaranTerakhir(),
            'spk' => $this->daftarSpk(),
            'aksi' => 'invoice/simpan',
        ] + $this->batasBuku(), 'Invoice Baru');
    }

    /** Balasan form modal (pola balasForm): AJAX -> JSON {ok, pesan}. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return $ok
            ? redirect()->to('invoice')->with('sukses', $pesan)
            : redirect()->back()->withInput()->with('galat', $pesan);
    }

    public function simpan()
    {
        // Modal form_modal mengirim id tersembunyi: 0 = baru, >0 = ubah.
        return $this->prosesSimpan((int) $this->request->getPost('id'));
    }

    public function perbarui($id)
    {
        return $this->prosesSimpan((int) $id);
    }

    private function prosesSimpan(int $id)
    {
        if ($id > 0 && $this->sudahTerbayar($id)) {
            return $this->balasForm(false, 'Invoice ini sudah dibayar dan terkunci. Koreksi dilakukan dengan menghapus jurnal pembayaran terlebih dahulu.');
        }
        $data = $this->ambilForm();
        // Invoice adalah entitas uang: tanggalnya mengikuti Bulan Terbuka
        // yang sama dengan pencatatan arus kas (instruksi Wilda 26 Nov 2026).
        if (! empty($data['tanggal']) && ($g = $this->galatBulanTertutup((string) $data['tanggal']))) {
            return $this->balasForm(false, $g);
        }
        // Kelayakan SPK (instruksi Wilda 26 Nov 2026): SPK hanya boleh
        // di-invoice bila sudah punya BAST (rantai BAP terakhir), tanggal
        // BAST tidak sesudah tanggal invoice, dan nilai BAST belum habis
        // ter-invoice. Nilai kosong = otomatis sebesar sisa.
        if ((int) $data['spk_id'] > 0) {
            // SPK Batal tidak boleh di-invoice baru (invoice lama SPK yang
            // sama tetap bisa diubah).
            $statusSpk = $this->db()->table('spk')->select('status')->where(['id' => (int) $data['spk_id'], 'sekolah_id' => $this->sid()])->get()->getRowArray()['status'] ?? null;
            if ($statusSpk === 'Batal') {
                $spkLama = $id > 0 ? (int) ($this->db()->table('invoice')->select('spk_id')->where('id', $id)->get()->getRowArray()['spk_id'] ?? 0) : 0;
                if ($spkLama !== (int) $data['spk_id']) {
                    return $this->balasForm(false, 'SPK ini berstatus Batal — invoice baru tidak bisa dicatat untuk SPK yang dibatalkan.');
                }
            }
            $w = $this->warisanSpk((int) $data['spk_id'], $id);
            if ($w['bast'] === null) {
                return $this->balasForm(false, 'SPK ini belum punya BAST — buat BAP & BAST dulu di halaman Dokumen atau Detail SPK, baru invoice bisa dicatat.');
            }
            if (! empty($data['tanggal']) && ! empty($w['bast']['tanggal']) && (string) $w['bast']['tanggal'] > (string) $data['tanggal']) {
                return $this->balasForm(false, 'Tanggal BAST (' . tgl($w['bast']['tanggal']) . ') sesudah tanggal invoice (' . tgl($data['tanggal']) . ') — invoice dicatat sesudah serah terima, periksa tanggalnya.');
            }
            if ($w['sisa'] <= 0) {
                return $this->balasForm(false, 'Nilai SPK ini sudah habis ter-invoice (nilai BAST ' . rp($w['nilai_bast']) . ') — SPK tidak bisa di-invoice lagi.');
            }
            if ($data['nilai'] === null) {
                $data['nilai'] = (int) $w['sisa'];
            }
            if ((int) $data['nilai'] <= 0) {
                return $this->balasForm(false, 'Nilai invoice harus lebih dari 0.');
            }
            if ((int) $data['nilai'] > (int) $w['sisa']) {
                return $this->balasForm(false, 'Nilai invoice melebihi sisa nilai BAST yang belum ter-invoice (' . rp($w['sisa']) . ').');
            }
        } elseif ($data['nilai'] === null) {
            $data['nilai'] = 0;
        }
        if ($this->nomorDipakai((int) $data['nomor_urut'], $id)) {
            return $this->balasForm(false, 'Nomor urut invoice ' . $data['nomor_urut'] . ' sudah dipakai di sekolah ini.');
        }
        // Pembayaran otomatis: status "Sudah Bayar" pada formulir mencatat
        // jurnal pembayaran dalam transaksi yang sama, dengan penjaga yang
        // sama seperti Jurnal Pembayaran (bendahara, Bulan Terbuka, tanggal
        // bayar >= tanggal invoice, kontrol pagu). Invoice yang sudah punya
        // pembayaran aktif terkunci (diperiksa di atas), jadi di sini selalu
        // belum terbayar.
        $tglBayar = trim((string) $this->request->getPost('tanggal_bayar'));
        $catatBayar = false;
        if (($data['status'] ?? '') === 'Sudah Bayar') {
            if ($tglBayar === '') {
                return $this->balasForm(false, 'Tanggal Pembayaran wajib diisi bila status Sudah Bayar — pembayaran akan tercatat otomatis sebagai jurnal pembayaran.');
            }
            $peran = (string) ($this->pengguna['peran'] ?? '');
            if ($peran !== 'admin' && $peran !== 'bendahara') {
                return $this->balasForm(false, 'Mencatat pembayaran hanya boleh dilakukan bendahara/admin. Simpan sebagai Belum Bayar, lalu bendahara mencatatnya di Jurnal Pembayaran.');
            }
            if ($g = $this->galatBulanTertutup($tglBayar)) {
                return $this->balasForm(false, $g);
            }
            if (! empty($data['tanggal']) && $tglBayar < (string) $data['tanggal']) {
                return $this->balasForm(false, 'Tanggal pembayaran (' . tgl($tglBayar) . ') tidak boleh sebelum tanggal invoice (' . tgl($data['tanggal']) . ') — pembayaran dicatat sesudah tagihan diterbitkan.');
            }
            [$boleh, $sisaPagu, $anggaran] = $this->cekPagu((float) $data['nilai']);
            if (! $boleh) {
                return $this->balasForm(false, sprintf(
                    'Pembayaran ditahan: nilai invoice %s melewati sisa pagu anggaran %s dari total anggaran %s.',
                    $this->rp($data['nilai']),
                    $this->rp(max(0, $sisaPagu)),
                    $this->rp($anggaran)
                ));
            }
            $pjk = $this->pajakPembelian((float) $data['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $data['spk_id'], (float) $data['nilai']));
            // Kanal pembayaran: bawaan tunai; hanya non-tunai yang masuk Buku Bank.
            $kanalBayar = (string) $this->request->getPost('kanal_bayar') === 'bank' ? 'bank' : 'tunai';
            if ($g = $this->galatSaldoMinus($kanalBayar, $tglBayar, (float) $pjk['dibayar'])) {
                return $this->balasForm(false, $g);
            }
            if ($kanalBayar === 'bank' && ($g = $this->galatRekeningPenyedia((int) $data['spk_id']))) {
                return $this->balasForm(false, $g);
            }
            $catatBayar = true;
        }
        $this->db()->transStart();
        if ($id > 0) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db()->table('invoice')->where('id', $id)->update($data);
            $invoiceId = $id;
        } else {
            $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db()->table('invoice')->insert($data);
            $invoiceId = (int) $this->db()->insertID();
        }
        if ($catatBayar) {
            $this->db()->table('pembayaran')->insert([
                'invoice_id' => $invoiceId,
                'tanggal' => $tglBayar,
                'kanal' => $kanalBayar ?? 'tunai',
                'keterangan' => null,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db()->transComplete();
        if ($catatBayar) {
            $this->catatAudit('pembayaran', $invoiceId, 'buat', 'Pembayaran invoice sebesar ' . $this->rp($data['nilai']) . ' dicatat otomatis dari formulir invoice (status Sudah Bayar).');
            return $this->balasForm(true, ($id > 0 ? 'Invoice diperbarui' : 'Invoice dicatat') . ' dan pembayaran tercatat di Jurnal Pembayaran.');
        }
        return $this->balasForm(true, $id > 0 ? 'Invoice diperbarui.' : 'Invoice dicatat.');
    }

    /** Nomor urut invoice unik per sekolah (lewat SPK induknya). */
    private function nomorDipakai(int $urut, int $kecualiId): bool
    {
        $row = $this->db()->query(
            'SELECT COUNT(*) j FROM invoice i JOIN spk s ON s.id = i.spk_id
             WHERE i.nomor_urut = ? AND s.sekolah_id = ? AND i.id <> ? AND i.is_deleted = 0',
            [$urut, $this->sid(), $kecualiId]
        )->getRow();
        return (int) $row->j > 0;
    }

    public function ubah($id)
    {
        $i = $this->db()->table('invoice')->where(['id' => (int) $id, 'is_deleted' => 0])->get()->getRowArray();
        if (! $i) {
            return redirect()->to('invoice')->with('galat', 'Invoice tidak ditemukan.');
        }
        return $this->tampil('invoice/form', [
            'i' => $i, 'spk' => $this->daftarSpk(), 'aksi' => 'invoice/perbarui/' . $id,
            'tgl_jurnal_terakhir' => $this->tanggalPembayaranTerakhir(),
        ] + $this->batasBuku(), 'Ubah Invoice ' . $i['nomor_urut']);
    }

    /** Invoice terkunci bila sudah memiliki jurnal pembayaran aktif. */
    protected function sudahTerbayar(int $invoiceId): bool
    {
        return $this->db()->table('pembayaran')->where(['invoice_id' => $invoiceId, 'is_deleted' => 0])->countAllResults() > 0;
    }

    public function hapus($id)
    {
        if ($this->sudahTerbayar((int) $id)) {
            return redirect()->to('invoice')->with('galat', 'Invoice ini sudah dibayar dan terkunci — hapus jurnal pembayarannya terlebih dahulu bila memang perlu dikoreksi.');
        }
        $this->hapusLunak('invoice', ['id' => (int) $id]);
        $this->catatAudit('invoice', (int) $id, 'hapus', 'Invoice dihapus.');
        return redirect()->to('invoice')->with('sukses', 'Invoice dihapus.');
    }

    /** Id invoice milik sekolah aktif (invoice tertaut sekolah lewat SPK). */
    private function idInvoiceSekolah(): array
    {
        return array_map('intval', array_column($this->db()->query(
            'SELECT i.id FROM invoice i JOIN spk s ON s.id = i.spk_id WHERE s.sekolah_id = ? AND i.is_deleted = 0',
            [$this->sid()]
        )->getResultArray(), 'id'));
    }

    public function hapusMassal()
    {
        $milik = $this->idInvoiceSekolah();
        $n = 0;
        $terkunci = 0;
        foreach ($this->idsTerpilih() as $id) {
            if (in_array($id, $milik, true)) {
                if ($this->sudahTerbayar($id)) {
                    $terkunci++;
                    continue;
                }
                $this->hapusLunak('invoice', ['id' => $id]);
                $n++;
            }
        }
        if ($n > 0) {
            $this->catatAudit('invoice', null, 'hapus-massal', $n . ' invoice terpilih dihapus.');
        }
        $pesan = $n > 0 ? $n . ' invoice terpilih dihapus.' : 'Tidak ada invoice yang diproses.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' invoice dilewati karena sudah dibayar (terkunci).';
        }
        return redirect()->to('invoice')->with($n > 0 ? 'sukses' : 'galat', $pesan);
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('invoice', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = $this->idInvoiceSekolah();
        $terkunci = 0;
        foreach ($ids as $id) {
            if ($this->sudahTerbayar($id)) {
                $terkunci++;
                continue;
            }
            $this->hapusLunak('invoice', ['id' => $id]);
        }
        $pesan = 'Daftar invoice dikosongkan: ' . (count($ids) - $terkunci) . ' invoice dihapus.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' invoice dilewati karena sudah dibayar (terkunci).';
        }
        return redirect()->to('invoice')->with('sukses', $pesan);
    }

    private function daftarSpk(): array
    {
        $spk = $this->db()->query(
            'SELECT s.id, s.nomor, s.paket, s.tanggal, s.status,
                    (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS total,
                    (SELECT COALESCE(SUM(i.nilai),0) FROM invoice i WHERE i.spk_id = s.id AND i.is_deleted = 0) AS terinvoice
             FROM spk s WHERE s.sekolah_id = ? AND s.is_deleted = 0 ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        $bap = [];
        foreach ($this->db()->table('bap')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $bap[$r['spk_id']][] = $r;
        }
        $bast = [];
        foreach ($this->db()->table('bast')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $bast[$r['spk_id']][] = $r;
        }
        // Nilai tiap BAP = jumlah rincian SPK yang tercakup bap_detail dan
        // DITERIMA BAIK (kondisi Baik/Cukup; Kurang/Tidak Sesuai tidak ikut
        // nilai BAP/BAST sehingga tidak ikut sisa invoice).
        $nilaiBap = [];
        foreach ($this->db()->query(
            "SELECT bd.bap_id, COALESCE(SUM(d.qty*d.harga),0) v FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             WHERE bd.is_deleted = 0 AND d.is_deleted = 0 AND bd.kondisi NOT IN ('Kurang', 'Tidak Sesuai') GROUP BY bd.bap_id"
        )->getResultArray() as $r) {
            $nilaiBap[(int) $r['bap_id']] = (float) $r['v'];
        }
        // Warisan per SPK: tanggal SPK + BAP terakhir (nomor urut terbesar)
        // dan BAST dari BAP itu (rantai 1 BAP = 1 BAST) beserta tanggal &
        // nilainya — invoice mewarisi ini dari SPK, pengguna tidak lagi
        // memilih BAP/BAST sendiri. sisa = nilai BAST − sudah ter-invoice;
        // SPK tanpa BAST atau sisa habis tidak layak di-invoice lagi.
        $warisan = [];
        foreach ($spk as $s) {
            $sid = (int) $s['id'];
            $bapW = self::terakhir($bap[$sid] ?? []);
            $bastW = self::bastWarisan($bast[$sid] ?? [], $bapW);
            $nilaiBast = 0.0;
            if ($bastW !== null) {
                $bastRow = null;
                foreach ($bast[$sid] ?? [] as $t) {
                    if ((int) $t['id'] === (int) $bastW['id']) { $bastRow = $t; break; }
                }
                // BAST lama tanpa bap_id mencakup seluruh rincian SPK.
                $nilaiBast = ($bastRow !== null && ! empty($bastRow['bap_id']))
                    ? ($nilaiBap[(int) $bastRow['bap_id']] ?? 0.0)
                    : (float) $s['total'];
            }
            $warisan[$sid] = [
                'tanggal'    => $s['tanggal'] ?? null,
                'bap'        => $bapW,
                'bast'       => $bastW,
                'nilai_bap'  => $bapW !== null ? ($nilaiBap[(int) $bapW['id']] ?? 0.0) : 0.0,
                'nilai_bast' => $nilaiBast,
                'sisa'       => $nilaiBast - (float) $s['terinvoice'],
            ];
        }
        return ['list' => $spk, 'bap' => $bap, 'bast' => $bast, 'warisan' => $warisan];
    }

    /** BAP/BAST terakhir dari daftar (nomor urut terbesar, lalu id terbesar). */
    private static function terakhir(array $daftar): ?array
    {
        if ($daftar === []) {
            return null;
        }
        usort($daftar, static fn ($a, $b) => [(int) ($b['nomor_urut'] ?? 0), (int) $b['id']] <=> [(int) ($a['nomor_urut'] ?? 0), (int) $a['id']]);
        $t = $daftar[0];
        return ['id' => (int) $t['id'], 'nomor_urut' => (int) ($t['nomor_urut'] ?? 0), 'tanggal' => $t['tanggal'] ?? null];
    }

    /** BAST warisan: BAST milik BAP warisan; tanpa BAP, BAST terakhir SPK (warisan lama tanpa bap_id). */
    private static function bastWarisan(array $daftarBast, ?array $bapW): ?array
    {
        if ($bapW !== null) {
            $milik = array_values(array_filter($daftarBast, static fn ($t) => (int) ($t['bap_id'] ?? 0) === (int) $bapW['id']));
            return self::terakhir($milik);
        }
        return self::terakhir($daftarBast);
    }

    /**
     * Warisan satu SPK untuk invoice: BAP terakhir, BAST miliknya, nilai
     * BAST, dan sisa yang masih boleh di-invoice (nilai BAST − invoice
     * aktif lain; $kecualiInvoiceId dikecualikan saat mengubah invoice).
     */
    private function warisanSpk(int $spkId, int $kecualiInvoiceId = 0): array
    {
        $b = $this->db()->table('bap')->where(['spk_id' => $spkId, 'is_deleted' => 0])->orderBy('nomor_urut', 'DESC')->orderBy('id', 'DESC')->get()->getRowArray();
        $qb = $this->db()->table('bast')->where(['spk_id' => $spkId, 'is_deleted' => 0]);
        if ($b) {
            $qb->where('bap_id', (int) $b['id']);
        }
        $t = $qb->orderBy('nomor_urut', 'DESC')->orderBy('id', 'DESC')->get()->getRowArray();
        $nilaiBast = 0.0;
        if ($t) {
            if (! empty($t['bap_id'])) {
                $row = $this->db()->query(
                    "SELECT COALESCE(SUM(d.qty*d.harga),0) v FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
                     WHERE bd.bap_id = ? AND bd.is_deleted = 0 AND d.is_deleted = 0 AND bd.kondisi NOT IN ('Kurang', 'Tidak Sesuai')",
                    [(int) $t['bap_id']]
                )->getRow();
                $nilaiBast = (float) $row->v;
            } else {
                $nilaiBast = $this->totalSpk($spkId);
            }
        }
        $row = $this->db()->query(
            'SELECT COALESCE(SUM(nilai),0) v FROM invoice WHERE spk_id = ? AND is_deleted = 0 AND id <> ?',
            [$spkId, $kecualiInvoiceId]
        )->getRow();
        return [
            'bap'        => $b,
            'bast'       => $t,
            'nilai_bast' => $nilaiBast,
            'sisa'       => $nilaiBast - (float) $row->v,
        ];
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        $spkId = (int) $r->getPost('spk_id');
        $nilai = $r->getPost('nilai');
        // BAP & BAST diwarisi dari SPK (BAP terakhir + BAST miliknya),
        // bukan dipilih pengguna — server yang menentukan, nilai POST
        // diabaikan.
        $bapId = $bastId = null;
        if ($spkId > 0) {
            $w = $this->warisanSpk($spkId);
            $bapId = $w['bap'] ? (int) $w['bap']['id'] : null;
            $bastId = $w['bast'] ? (int) $w['bast']['id'] : null;
        }
        return [
            'nomor_urut' => (int) $r->getPost('nomor_urut'),
            'no_toko' => trim((string) $r->getPost('no_toko')) ?: null,
            'tanggal' => $r->getPost('tanggal') ?: null,
            'spk_id' => $spkId,
            'bap_id' => $bapId,
            'bast_id' => $bastId,
            // Kosong = otomatis sebesar sisa nilai BAST (diisi prosesSimpan).
            'nilai' => ($nilai === '' || $nilai === null) ? null : (int) $nilai,
            'status' => (string) $r->getPost('status') ?: 'Belum Bayar',
        ];
    }

    /** Halaman cetak invoice (tagihan) per SPK. */
    public function cetak($id)
    {
        $db = $this->db();
        $i = $db->query(
            'SELECT i.*, s.nomor AS spk_nomor, s.tanggal AS spk_tanggal, s.paket, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia,
                    COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, p.alamat AS penyedia_alamat
             FROM invoice i JOIN spk s ON s.id = i.spk_id
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE i.id = ? AND s.sekolah_id = ? AND i.is_deleted = 0 AND s.is_deleted = 0',
            [(int) $id, $this->sid()]
        )->getRowArray();
        if (! $i) {
            return redirect()->to('invoice')->with('galat', 'Invoice tidak ditemukan di sekolah aktif.');
        }
        $detail = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan,
                    d.qty, d.harga, d.qty*d.harga AS total
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $i['spk_id']]
        )->getResultArray();
        return view('cetak/invoice', [
            'idn' => $this->sekolahAktif(), 'i' => $i, 'detail' => $detail,
            'pajak' => $this->pajakPembelian((float) $i['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $i['spk_id'], (float) $i['nilai'])),
            'totalSpk' => array_sum(array_column($detail, 'total')),
            'terbilang' => $this->terbilang($i['nilai']),
            'nomor' => $this->nomorSurat('INV', (int) $i['nomor_urut']),
            'nomorSpk' => $this->nomorSurat('SPK', (int) $i['spk_nomor']),
        ]);
    }

    // ---------------- JURNAL PEMBAYARAN ----------------

    public function jurnal()
    {
        $baris = $this->db()->query(
            'SELECT p.*, i.nomor_urut, i.no_toko, i.nilai, i.spk_id, s.nomor AS spk_nomor
             FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0
             JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE s.sekolah_id = ? AND p.is_deleted = 0 ORDER BY p.id',
            [$this->sid()]
        )->getResultArray();
        $peta = $this->ppnSpkMap();
        foreach ($baris as &$r) {
            $r += $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($peta, (int) $r['spk_id'], (float) $r['nilai']));
        }
        unset($r);
        $belum = $this->db()->query(
            "SELECT i.id, i.nomor_urut, i.nilai, i.tanggal, s.nomor AS spk_nomor FROM invoice i
             JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE i.status = 'Belum Bayar' AND s.sekolah_id = ? AND i.is_deleted = 0 ORDER BY i.nomor_urut",
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('invoice/jurnal', [
            'baris' => $baris, 'belum' => $belum,
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
            'tgl_jurnal_terakhir' => $this->tanggalPembayaranTerakhir() ?? '',
        ] + $this->batasBuku(), 'Jurnal Pembayaran — Aplikasi PBJ Sekolah');
    }

    /** Balasan form modal jurnal: AJAX -> JSON {ok, pesan}; non-AJAX -> redirect ke halaman jurnal seperti semula. */
    private function balasJurnal(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('invoice/jurnal')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function jurnalTambah()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $invoiceId = (int) $this->request->getPost('invoice_id');
        if ($invoiceId <= 0) {
            return $this->balasJurnal(false, 'Pilih invoice yang dibayar.');
        }
        $inv = $this->db()->query(
            'SELECT i.* FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE i.id = ? AND s.sekolah_id = ? AND i.is_deleted = 0',
            [$invoiceId, $this->sid()]
        )->getRowArray();
        if ($inv === null) {
            return $this->balasJurnal(false, 'Invoice tidak ditemukan.');
        }
        // Penjaga status: pemilih hanya menampilkan Belum Bayar, tetapi server
        // memeriksa ulang agar pembayaran tidak tercatat dua kali.
        if (($inv['status'] ?? '') !== 'Belum Bayar') {
            return $this->balasJurnal(false, 'Invoice ini sudah berstatus Sudah Bayar — pembayaran tidak bisa dicatat dua kali. Bila perlu koreksi, hapus jurnal pembayaran sebelumnya terlebih dahulu.');
        }
        $tglBayar = (string) ($this->request->getPost('tanggal') ?: '');
        if ($tglBayar !== '' && ! empty($inv['tanggal']) && $tglBayar < (string) $inv['tanggal']) {
            return $this->balasJurnal(false, 'Tanggal bayar (' . tgl($tglBayar) . ') tidak boleh sebelum tanggal invoice (' . tgl($inv['tanggal']) . ') — pembayaran dicatat sesudah tagihan diterbitkan.');
        }
        if ($g = $this->galatBulanTertutup($this->request->getPost('tanggal') ?: null)) {
            return $this->balasJurnal(false, $g);
        }
        // Kontrol pagu: total belanja terbayar tidak boleh melewati total anggaran
        [$boleh, $sisaPagu, $anggaran] = $this->cekPagu((float) $inv['nilai']);
        if (! $boleh) {
            return $this->balasJurnal(false, sprintf(
                'Pembayaran ditahan: nilai invoice %s melewati sisa pagu anggaran %s dari total anggaran %s.',
                $this->rp($inv['nilai']),
                $this->rp(max(0, $sisaPagu)),
                $this->rp($anggaran)
            ));
        }
        // Kanal pembayaran: bawaan tunai; hanya non-tunai yang masuk Buku Bank.
        $kanal = (string) $this->request->getPost('kanal') === 'bank' ? 'bank' : 'tunai';
        // Saldo kanal tidak boleh minus pada tanggal bayar maupun sesudahnya.
        $pjk = $this->pajakPembelian((float) $inv['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $inv['spk_id'], (float) $inv['nilai']));
        if ($g = $this->galatSaldoMinus($kanal, (string) ($this->request->getPost('tanggal') ?: $inv['tanggal']), (float) $pjk['dibayar'])) {
            return $this->balasJurnal(false, $g);
        }
        // Pembayaran bank butuh nomor rekening penyedia.
        if ($kanal === 'bank' && ($g = $this->galatRekeningPenyedia((int) $inv['spk_id']))) {
            return $this->balasJurnal(false, $g);
        }
        $this->db()->transStart();
        $this->db()->table('pembayaran')->insert([
            'invoice_id' => $invoiceId,
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'kanal' => $kanal,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db()->table('invoice')->where('id', $invoiceId)->update(['status' => 'Sudah Bayar', 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db()->transComplete();
        $this->catatAudit('pembayaran', $invoiceId, 'buat', 'Pembayaran invoice sebesar ' . $this->rp($inv['nilai']) . ' dicatat.');
        return $this->balasJurnal(true, 'Pembayaran dicatat; PPh 22 dan nilai bersih terhitung otomatis.');
    }

    public function jurnalHapus($id)
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $p = $this->db()->table('pembayaran')->where(['id' => (int) $id, 'is_deleted' => 0])->get()->getRowArray();
        if ($p) {
            $invTgl = $this->db()->table('invoice')->select('tanggal')->where('id', $p['invoice_id'])->get()->getRowArray();
            if ($g = $this->galatBulanTertutup($p['tanggal'] ?: ($invTgl['tanggal'] ?? null))) {
                return redirect()->to('invoice/jurnal')->with('galat', $g);
            }
        }
        if ($p) {
            $this->db()->transStart();
            $this->hapusLunak('pembayaran', ['id' => (int) $id]);
            $this->db()->table('invoice')->where('id', $p['invoice_id'])->update(['status' => 'Belum Bayar', 'updated_at' => date('Y-m-d H:i:s')]);
            $this->db()->transComplete();
            $this->catatAudit('pembayaran', (int) $id, 'hapus', 'Jurnal pembayaran dihapus; invoice kembali Belum Bayar.');
        }
        return redirect()->to('invoice/jurnal')->with('sukses', 'Baris jurnal dihapus; invoice kembali Belum Bayar.');
    }
}

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
        if ($this->nomorDipakai((int) $data['nomor_urut'], $id)) {
            return $this->balasForm(false, 'Nomor urut invoice ' . $data['nomor_urut'] . ' sudah dipakai di sekolah ini.');
        }
        if ($id > 0) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db()->table('invoice')->where('id', $id)->update($data);
            return $this->balasForm(true, 'Invoice diperbarui.');
        }
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('invoice')->insert($data);
        return $this->balasForm(true, 'Invoice dicatat.');
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
            'SELECT s.id, s.nomor, s.paket, (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS total
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
        return ['list' => $spk, 'bap' => $bap, 'bast' => $bast];
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        $spkId = (int) $r->getPost('spk_id');
        $nilai = $r->getPost('nilai');
        return [
            'nomor_urut' => (int) $r->getPost('nomor_urut'),
            'no_toko' => trim((string) $r->getPost('no_toko')) ?: null,
            'tanggal' => $r->getPost('tanggal') ?: null,
            'spk_id' => $spkId,
            'bap_id' => (int) $r->getPost('bap_id') ?: null,
            'bast_id' => (int) $r->getPost('bast_id') ?: null,
            'nilai' => ($nilai === '' || $nilai === null) ? (int) $this->totalSpk($spkId) : (int) $nilai,
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
            "SELECT i.id, i.nomor_urut, i.nilai, s.nomor AS spk_nomor FROM invoice i
             JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE i.status = 'Belum Bayar' AND s.sekolah_id = ? AND i.is_deleted = 0 ORDER BY i.nomor_urut",
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('invoice/jurnal', ['baris' => $baris, 'belum' => $belum, 'tgl_bayar_default' => $this->tanggalBulanBekerja()] + $this->batasBuku(), 'Jurnal Pembayaran — Aplikasi PBJ Sekolah');
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
        $this->db()->transStart();
        $this->db()->table('pembayaran')->insert([
            'invoice_id' => $invoiceId,
            'tanggal' => $this->request->getPost('tanggal') ?: null,
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

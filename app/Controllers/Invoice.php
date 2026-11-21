<?php

namespace App\Controllers;

class Invoice extends BaseController
{
    private function totalSpk(int $spkId): float
    {
        $row = $this->db()->query('SELECT COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id = ?', [$spkId])->getRow();
        return (float) $row->t;
    }

    public function index()
    {
        $inv = $this->db()->query(
            'SELECT i.*, s.nomor AS spk_nomor, p.nama AS penyedia,
                    (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id) AS total_spk
             FROM invoice i JOIN spk s ON s.id = i.spk_id
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.sekolah_id = ? ORDER BY i.nomor_urut',
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('invoice/index', ['inv' => $inv], 'Register Invoice — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(nomor_urut) m FROM invoice')->getRow();
        return $this->tampil('invoice/form', [
            'i' => ['nomor_urut' => ((int) $row->m) + 1, 'status' => 'Belum Bayar'],
            'spk' => $this->daftarSpk(),
            'aksi' => 'invoice/simpan',
        ], 'Invoice Baru');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->nomorDipakai((int) $data['nomor_urut'], 0)) {
            return redirect()->back()->withInput()->with('galat', 'Nomor urut invoice ' . $data['nomor_urut'] . ' sudah dipakai di sekolah ini.');
        }
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('invoice')->insert($data);
        return redirect()->to('invoice')->with('sukses', 'Invoice dicatat.');
    }

    /** Nomor urut invoice unik per sekolah (lewat SPK induknya). */
    private function nomorDipakai(int $urut, int $kecualiId): bool
    {
        $row = $this->db()->query(
            'SELECT COUNT(*) j FROM invoice i JOIN spk s ON s.id = i.spk_id
             WHERE i.nomor_urut = ? AND s.sekolah_id = ? AND i.id <> ?',
            [$urut, $this->sid(), $kecualiId]
        )->getRow();
        return (int) $row->j > 0;
    }

    public function ubah($id)
    {
        $i = $this->db()->table('invoice')->where('id', (int) $id)->get()->getRowArray();
        if (! $i) {
            return redirect()->to('invoice')->with('galat', 'Invoice tidak ditemukan.');
        }
        return $this->tampil('invoice/form', [
            'i' => $i, 'spk' => $this->daftarSpk(), 'aksi' => 'invoice/perbarui/' . $id,
        ], 'Ubah Invoice ' . $i['nomor_urut']);
    }

    public function perbarui($id)
    {
        $data = $this->ambilForm();
        if ($this->nomorDipakai((int) $data['nomor_urut'], (int) $id)) {
            return redirect()->back()->withInput()->with('galat', 'Nomor urut invoice ' . $data['nomor_urut'] . ' sudah dipakai di sekolah ini.');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('invoice')->where('id', (int) $id)->update($data);
        return redirect()->to('invoice')->with('sukses', 'Invoice diperbarui.');
    }

    public function hapus($id)
    {
        $this->db()->table('invoice')->where('id', (int) $id)->delete();
        return redirect()->to('invoice')->with('sukses', 'Invoice dihapus (jurnal pembayaran terkait ikut terhapus).');
    }

    private function daftarSpk(): array
    {
        $spk = $this->db()->query(
            'SELECT s.id, s.nomor, s.paket, (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id) AS total
             FROM spk s WHERE s.sekolah_id = ? ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        $bap = [];
        foreach ($this->db()->table('bap')->get()->getResultArray() as $r) {
            $bap[$r['spk_id']][] = $r;
        }
        $bast = [];
        foreach ($this->db()->table('bast')->get()->getResultArray() as $r) {
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
            'SELECT i.*, s.nomor AS spk_nomor, s.tanggal AS spk_tanggal, s.paket, s.ttd_penyedia,
                    p.nama AS penyedia, p.alamat AS penyedia_alamat
             FROM invoice i JOIN spk s ON s.id = i.spk_id
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE i.id = ? AND s.sekolah_id = ?',
            [(int) $id, $this->sid()]
        )->getRowArray();
        if (! $i) {
            return redirect()->to('invoice')->with('galat', 'Invoice tidak ditemukan di sekolah aktif.');
        }
        $detail = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan,
                    d.qty, d.harga, d.qty*d.harga AS total
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? ORDER BY d.id',
            [(int) $i['spk_id']]
        )->getResultArray();
        return view('cetak/invoice', [
            'idn' => $this->sekolahAktif(), 'i' => $i, 'detail' => $detail,
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
            'SELECT p.*, i.nomor_urut, i.no_toko, i.nilai, s.nomor AS spk_nomor,
                    ROUND(i.nilai * 0.015) AS pph22, i.nilai - ROUND(i.nilai * 0.015) AS dibayar
             FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id
             JOIN spk s ON s.id = i.spk_id WHERE s.sekolah_id = ? ORDER BY p.id',
            [$this->sid()]
        )->getResultArray();
        $belum = $this->db()->query(
            "SELECT i.id, i.nomor_urut, i.nilai, s.nomor AS spk_nomor FROM invoice i
             JOIN spk s ON s.id = i.spk_id WHERE i.status = 'Belum Bayar' AND s.sekolah_id = ? ORDER BY i.nomor_urut",
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('invoice/jurnal', ['baris' => $baris, 'belum' => $belum], 'Jurnal Pembayaran — Aplikasi PBJ Sekolah');
    }

    public function jurnalTambah()
    {
        $invoiceId = (int) $this->request->getPost('invoice_id');
        if ($invoiceId <= 0) {
            return redirect()->to('invoice/jurnal')->with('galat', 'Pilih invoice yang dibayar.');
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
        return redirect()->to('invoice/jurnal')->with('sukses', 'Pembayaran dicatat; PPh 22 dan nilai bersih terhitung otomatis.');
    }

    public function jurnalHapus($id)
    {
        $p = $this->db()->table('pembayaran')->where('id', (int) $id)->get()->getRowArray();
        if ($p) {
            $this->db()->transStart();
            $this->db()->table('pembayaran')->where('id', (int) $id)->delete();
            $this->db()->table('invoice')->where('id', $p['invoice_id'])->update(['status' => 'Belum Bayar', 'updated_at' => date('Y-m-d H:i:s')]);
            $this->db()->transComplete();
        }
        return redirect()->to('invoice/jurnal')->with('sukses', 'Baris jurnal dihapus; invoice kembali Belum Bayar.');
    }
}

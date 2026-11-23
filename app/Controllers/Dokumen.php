<?php

namespace App\Controllers;

class Dokumen extends BaseController
{
    private function hariTanggal(?string $tgl): string
    {
        if (! $tgl) {
            return '……………, tanggal ……………';
        }
        $ts = strtotime($tgl);
        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $hari[date('l', $ts)] . ', tanggal ' . $this->terbilang((int) date('j', $ts)) . ' bulan '
            . $bulan[(int) date('n', $ts)] . ' tahun ' . $this->terbilang((int) date('Y', $ts))
            . ' (' . date('d/m/Y', $ts) . ')';
    }

    private function identitas(): array
    {
        return $this->sekolahAktif();
    }

    public function index()
    {
        $db = $this->db();
        $spk = $db->query(
            'SELECT s.id, s.nomor, s.paket, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia FROM spk s
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.sekolah_id = ? AND s.is_deleted = 0 ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        $bap = [];
        foreach ($db->table('bap')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $bap[$r['spk_id']][] = $r;
        }
        $bast = [];
        foreach ($db->table('bast')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $bast[$r['spk_id']][] = $r;
        }
        return $this->tampil('dokumen/index', ['spk' => $spk, 'bap' => $bap, 'bast' => $bast], 'BAP & BAST — Aplikasi PBJ Sekolah');
    }

    public function bapBuat($spkId)
    {
        $db = $this->db();
        $row = $db->query('SELECT MAX(nomor_urut) m FROM bap')->getRow();
        $db->table('bap')->insert([
            'spk_id' => (int) $spkId, 'nomor_urut' => ((int) $row->m) + 1,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $bapId = (int) $db->insertID();
        foreach ($db->table('spk_detail')->where(['spk_id' => (int) $spkId, 'is_deleted' => 0])->get()->getResultArray() as $d) {
            $db->table('bap_detail')->insert(['bap_id' => $bapId, 'detail_id' => $d['id'], 'kondisi' => 'Cukup']);
        }
        return redirect()->to('dokumen/bap-ubah/' . $bapId)->with('sukses', 'BAP dibuat dari rincian SPK — lengkapi tanggal dan kondisi pemeriksaan.');
    }

    public function bapUbah($bapId)
    {
        $db = $this->db();
        $bap = $db->table('bap')->where(['id' => (int) $bapId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bap) {
            return redirect()->to('dokumen')->with('galat', 'BAP tidak ditemukan.');
        }
        $items = $db->query(
            'SELECT bd.id AS bd_id, bd.kondisi, bd.keterangan, d.qty,
                    COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
             FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.bap_id = ? AND bd.is_deleted = 0 ORDER BY bd.id',
            [(int) $bapId]
        )->getResultArray();
        $spk = $db->query('SELECT nomor, tanggal, tgl_mulai, tgl_selesai FROM spk WHERE id = ?', [(int) $bap['spk_id']])->getRowArray();
        return $this->tampil('dokumen/bap_form', ['bap' => $bap, 'items' => $items, 'spk' => $spk], 'BAP ' . $this->nomorSurat('BAP', (int) $bap['nomor_urut']));
    }

    public function bapSimpan($bapId)
    {
        $db = $this->db();
        $db->table('bap')->where('id', (int) $bapId)->update([
            'nomor_urut' => (int) $this->request->getPost('nomor_urut'),
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($this->request->getPost('kondisi') ?? [] as $bdId => $kondisi) {
            $db->table('bap_detail')->where('id', (int) $bdId)->update([
                'kondisi' => $kondisi,
                'keterangan' => ($this->request->getPost('keterangan') ?? [])[$bdId] ?? null,
            ]);
        }
        return redirect()->to('dokumen')->with('sukses', 'BAP disimpan.');
    }

    public function bastBuat($spkId)
    {
        $db = $this->db();
        $row = $db->query('SELECT MAX(nomor_urut) m FROM bast')->getRow();
        $db->table('bast')->insert([
            'spk_id' => (int) $spkId, 'nomor_urut' => ((int) $row->m) + 1,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('dokumen/bast-ubah/' . $db->insertID())->with('sukses', 'BAST dibuat — lengkapi tanggalnya.');
    }

    public function bastUbah($bastId)
    {
        $bast = $this->db()->table('bast')->where(['id' => (int) $bastId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bast) {
            return redirect()->to('dokumen')->with('galat', 'BAST tidak ditemukan.');
        }
        return $this->tampil('dokumen/bast_form', ['bast' => $bast], 'BAST ' . $this->nomorSurat('BAST', (int) $bast['nomor_urut']));
    }

    public function bastSimpan($bastId)
    {
        $this->db()->table('bast')->where('id', (int) $bastId)->update([
            'nomor_urut' => (int) $this->request->getPost('nomor_urut'),
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('dokumen')->with('sukses', 'BAST disimpan.');
    }

    // ---------------- CETAK ----------------

    public function cetakSpk($spkId)
    {
        $db = $this->db();
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, p.alamat AS penyedia_alamat, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $spkId])->getRowArray();
        if (! $s) {
            return redirect()->to('dokumen')->with('galat', 'SPK tidak ditemukan.');
        }
        $detail = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty, d.harga, d.qty*d.harga AS total
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $spkId]
        )->getResultArray();
        $total = array_sum(array_column($detail, 'total'));
        return view('cetak/spk', [
            'idn' => $this->identitas(), 's' => $s, 'detail' => $detail,
            'total' => $total, 'terbilang' => $this->terbilang($total),
            'nomor' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($s['tanggal']),
        ]);
    }

    public function cetakBap($bapId)
    {
        $db = $this->db();
        $bap = $db->table('bap')->where(['id' => (int) $bapId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bap) {
            return redirect()->to('dokumen')->with('galat', 'BAP tidak ditemukan.');
        }
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $bap['spk_id']])->getRowArray();
        $items = $db->query(
            'SELECT bd.kondisi, bd.keterangan, d.qty, COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
             FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.bap_id = ? AND bd.is_deleted = 0 ORDER BY bd.id',
            [(int) $bapId]
        )->getResultArray();
        return view('cetak/bap', [
            'idn' => $this->identitas(), 'bap' => $bap, 's' => $s, 'items' => $items,
            'nomor' => $this->nomorSurat('BAP', (int) $bap['nomor_urut']),
            'nomorSpk' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($bap['tanggal']),
        ]);
    }

    public function cetakBast($bastId)
    {
        $db = $this->db();
        $bast = $db->table('bast')->where('id', (int) $bastId)->get()->getRowArray();
        if (! $bast) {
            return redirect()->to('dokumen')->with('galat', 'BAST tidak ditemukan.');
        }
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $bast['spk_id']])->getRowArray();
        $items = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $bast['spk_id']]
        )->getResultArray();
        return view('cetak/bast', [
            'idn' => $this->identitas(), 'bast' => $bast, 's' => $s, 'items' => $items,
            'nomor' => $this->nomorSurat('BAST', (int) $bast['nomor_urut']),
            'nomorSpk' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($bast['tanggal']),
        ]);
    }

    /**
     * BAST Aset per gedung (format dokumen Juknis): serah terima aset
     * hasil pembangunan — untuk satuan pendidikan negeri disampaikan
     * kepada Dinas Pendidikan, untuk swasta kepada penyelenggara
     * (yayasan). Berbeda dari BAST Pekerjaan yang disampaikan ke PPK.
     */
    public function cetakBastAset($gedungId)
    {
        $g = null;
        foreach ($this->semuaGedung() as $row) {
            if ((int) $row['id'] === (int) $gedungId) {
                $g = $row;
                break;
            }
        }
        if (! $g) {
            return redirect()->to('lpj')->with('galat', 'Gedung tidak ditemukan di sekolah aktif.');
        }
        return view('cetak/bast_aset', [
            'idn' => $this->identitas(), 'g' => $g,
            'hariTanggal' => $this->hariTanggal(date('Y-m-d')),
        ]);
    }

    /** Papan Nama Kegiatan (format dokumen Juknis) dari identitas sekolah. */
    public function cetakPapanNama()
    {
        return view('cetak/papan_nama', ['idn' => $this->identitas()]);
    }
}

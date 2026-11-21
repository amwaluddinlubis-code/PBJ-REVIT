<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = $this->db();

        $totalRab = (float) $db->query('SELECT COALESCE(SUM((qty_rkb+qty_adm+qty_toilet)*harga_rab),0) t FROM bahan')->getRow()->t;
        $totalSpk = (float) $db->query('SELECT COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id IS NOT NULL')->getRow()->t;
        $realisasi = 0.0;
        foreach ($db->table('pembayaran p')->select('i.nilai')->join('invoice i', 'i.id = p.invoice_id')->get()->getResultArray() as $r) {
            $realisasi += (float) $r['nilai'] - round((float) $r['nilai'] * 0.015);
        }

        $tanpaSpk = $db->query('SELECT COUNT(*) j, COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id IS NULL')->getRow();

        $invoiceCek = $db->query(
            'SELECT i.nomor_urut, i.nilai, s.nomor AS spk_nomor, COALESCE(SUM(d.qty*d.harga),0) AS total_spk
             FROM invoice i JOIN spk s ON s.id = i.spk_id
             LEFT JOIN spk_detail d ON d.spk_id = s.id
             GROUP BY i.id HAVING i.nilai <> total_spk'
        )->getResultArray();

        $sisaNegatif = $db->query(
            'SELECT b.kode, b.nama, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_rab,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id
             GROUP BY b.id HAVING terpakai > qty_rab'
        )->getResultArray();

        $perSpk = $db->query(
            'SELECT s.nomor, s.tanggal, s.paket, s.status, p.nama AS penyedia,
                    COUNT(d.id) AS jumlah, COALESCE(SUM(d.qty*d.harga),0) AS nilai
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             LEFT JOIN spk_detail d ON d.spk_id = s.id
             GROUP BY s.id ORDER BY s.nomor'
        )->getResultArray();

        $identitas = $db->table('identitas')->get()->getRowArray();

        return $this->tampil('dashboard/index', [
            'identitas'   => $identitas,
            'totalRab'    => $totalRab,
            'totalSpk'    => $totalSpk,
            'realisasi'   => $realisasi,
            'sisa'        => $totalRab - $realisasi,
            'tanpaSpk'    => $tanpaSpk,
            'invoiceCek'  => $invoiceCek,
            'sisaNegatif' => $sisaNegatif,
            'perSpk'      => $perSpk,
        ], 'Dashboard — Aplikasi PBJ Sekolah');
    }
}

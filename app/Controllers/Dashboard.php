<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $sid = $this->sid();

        $totalRab = (float) $db->query('SELECT COALESCE(SUM((qty_rkb+qty_adm+qty_toilet)*harga_rab),0) t FROM bahan WHERE sekolah_id = ? AND is_deleted = 0', [$sid])->getRow()->t;
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

        $sisaNegatif = $db->query(
            'SELECT b.kode, b.nama, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_rab,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id AND d.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0
             GROUP BY b.id HAVING terpakai > qty_rab',
            [$sid]
        )->getResultArray();

        $perSpk = $db->query(
            'SELECT s.nomor, s.tanggal, s.paket, s.status, p.nama AS penyedia,
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
            'verifikasi'  => $this->verifikasiAnggaran(),
            'tahap2JatuhTempo' => $tahap2JatuhTempo,
            'fisikRata' => $fisikRata,
        ], 'Dashboard — Analisa Perencana');
    }
}

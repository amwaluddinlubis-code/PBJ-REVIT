<?php

namespace App\Controllers;

/**
 * Rencana Belanja: turunan kebutuhan bahan & upah dari BOQ/AHSP.
 * Dari sini panitia membuat SPK berisi bahan yang dipilih — rencana belanja
 * bukan daftar bebas, melainkan kebutuhan pekerjaan yang belum terpenuhi.
 */
class Rencana extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $gedungFilter = (int) ($this->request->getGet('gedung') ?? 0);
        $kb = $gedungFilter > 0 && $this->ambilGedung($gedungFilter) !== null
            ? $this->kebutuhanBahan($gedungFilter)
            : $this->kebutuhanBahanSekolah();

        $dipesan = [];
        $sql = 'SELECT d.bahan_id, SUM(d.qty) q FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
                WHERE s.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0';
        $bind = [$this->sid()];
        if ($gedungFilter > 0) {
            $sql .= ' AND s.gedung_id = ?';
            $bind[] = $gedungFilter;
        }
        $sql .= ' GROUP BY d.bahan_id';
        foreach ($db->query($sql, $bind)->getResultArray() as $r) {
            $dipesan[(int) $r['bahan_id']] = (float) $r['q'];
        }
        foreach ($kb['tertaut'] as &$b) {
            $b['harga'] = $this->hargaAcuan((int) $b['bahan_id']);
            $b['dipesan'] = $dipesan[(int) $b['bahan_id']] ?? 0;
            $b['sisa'] = max(0, (float) $b['qty_butuh'] - $b['dipesan']);
        }

        $upah = $gedungFilter > 0 ? $this->kebutuhanUpah($gedungFilter) : $this->kebutuhanUpahSekolah();
        $sekolah = $this->sekolahAktif();
        $anggaran = (float) ($sekolah['total_anggaran'] ?? 0);
        $terbayar = $this->totalBelanja();

        return $this->tampil('rencana/index', [
            'kebutuhan' => $kb, 'kebutuhan_upah' => $upah,
            'gedung_filter' => $gedungFilter,
            'anggaran' => $anggaran, 'terbayar' => $terbayar,
            'sisa_pagu' => $anggaran > 0 ? $anggaran - $terbayar : null,
        ], 'Rencana Belanja — Dari Kebutuhan Pekerjaan');
    }

    /** Buat SPK baru berisi bahan-bahan terpilih dari rencana belanja. */
    public function buatSpk()
    {
        $db = $this->db();
        $pilihan = (array) $this->request->getPost('pilih');
        $qtyPost = (array) $this->request->getPost('qty');
        $gedungId = (int) $this->request->getPost('gedung_id') ?: null;
        if ($gedungId !== null && $this->ambilGedung($gedungId) === null) {
            $gedungId = null;
        }
        $baris = [];
        foreach ($pilihan as $bahanId) {
            $bahanId = (int) $bahanId;
            $qty = (float) str_replace(',', '.', (string) ($qtyPost[$bahanId] ?? 0));
            $b = $db->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($b !== null && $qty > 0) {
                $baris[] = ['bahan' => $b, 'qty' => $qty, 'harga' => $this->hargaAcuan($bahanId)];
            }
        }
        if ($baris === []) {
            return redirect()->to('/rencana-belanja')->with('galat', 'Centang minimal satu bahan dengan jumlah rencana lebih dari 0.');
        }
        $row = $db->query('SELECT MAX(nomor) m FROM spk WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow();
        $nomor = ((int) ($row->m ?? 0)) + 1;
        $db->transStart();
        $db->table('spk')->insert([
            'sekolah_id' => $this->sid(), 'nomor' => $nomor, 'tanggal' => date('Y-m-d'),
            'gedung_id' => $gedungId,
            'paket' => 'Rencana belanja dari kebutuhan' . ($gedungId !== null ? ' — ' . ($this->ambilGedung($gedungId)['nama'] ?? '') : ''),
            'status' => 'Aktif', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $spkId = (int) $db->insertID();
        foreach ($baris as $b) {
            $db->table('spk_detail')->insert([
                'spk_id' => $spkId, 'bahan_id' => (int) $b['bahan']['id'], 'sekolah_id' => $this->sid(),
                'qty' => $b['qty'], 'harga' => $b['harga'],
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        return redirect()->to('/spk/detail/' . $spkId)->with('sukses', 'SPK ' . $nomor . ' dibuat dari rencana belanja (' . count($baris) . ' bahan). Lengkapi penyedia & tanggalnya bila perlu.');
    }
}

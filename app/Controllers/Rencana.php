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

        // Asal-usul tiap bahan di rencana: jumlah komponen & analisa sumbernya
        // (untuk aksi hapus dari rencana lewat penghapusan komponen di Analisa).
        $asalBahan = [];
        foreach ($db->query(
            'SELECT ak.bahan_id, COUNT(*) AS komponen, COUNT(DISTINCT ak.analisa_id) AS analisa, MIN(ak.analisa_id) AS analisa_pertama
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0 AND a.is_deleted = 0
             GROUP BY ak.bahan_id',
            [$this->sid(), 'bahan']
        )->getResultArray() as $r) {
            $asalBahan[(int) $r['bahan_id']] = $r;
        }
        foreach ($kb['tertaut'] as &$b) {
            $asal = $asalBahan[(int) $b['bahan_id']] ?? null;
            $b['komponen_dari'] = (int) ($asal['komponen'] ?? 0);
            $b['analisa_dari'] = (int) ($asal['analisa'] ?? 0);
            $b['analisa_pertama'] = (int) ($asal['analisa_pertama'] ?? 0);
        }
        unset($b);

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

    /**
     * Hapus bahan dari rencana belanja: komponen bahan ini di semua analisa
     * sekolah dihapus lunak (sumber kebutuhan memang dari Analisa), sehingga
     * bahan hilang dari kebutuhan semua gedung. SPK yang sudah terlanjur
     * memakai bahan ini tidak berubah. Dapat dipulihkan dari Sampah.
     */
    public function hapusBahan($id)
    {
        $db = $this->db();
        $bahan = $db->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($bahan === null) {
            return redirect()->to(base_url('rencana-belanja'))->with('galat', 'Bahan tidak ditemukan.');
        }
        $baris = $db->query(
            'SELECT ak.id, ak.analisa_id FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id = ? AND ak.is_deleted = 0 AND a.is_deleted = 0',
            [$this->sid(), 'bahan', (int) $id]
        )->getResultArray();
        if ($baris === []) {
            return redirect()->to(base_url('rencana-belanja'))->with('galat', 'Bahan ini sudah tidak memiliki komponen aktif di Analisa.');
        }
        $jmlAnalisa = count(array_unique(array_column($baris, 'analisa_id')));
        $db->table('analisa_komponen')->whereIn('id', array_map('intval', array_column($baris, 'id')))
            ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        $diSpk = (int) $db->table('spk_detail')->where(['bahan_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        $pesan = 'Bahan ' . $bahan['nama'] . ' dihapus dari rencana: ' . count($baris) . ' komponen di ' . $jmlAnalisa . ' analisa dihapus dari Analisa (dapat dipulihkan lewat Pemeliharaan Database, menu Sampah). Bahan ini hilang dari kebutuhan semua gedung.';
        if ($diSpk > 0) {
            $pesan .= ' Catatan: bahan ini sudah tercantum di ' . $diSpk . ' baris SPK — SPK tersebut tidak berubah.';
        }
        return redirect()->to(base_url('rencana-belanja'))->with('sukses', $pesan);
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

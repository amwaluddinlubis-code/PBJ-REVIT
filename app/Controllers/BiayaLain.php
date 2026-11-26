<?php

namespace App\Controllers;

/**
 * Biaya Non-Gedung: biaya tingkat sekolah di luar BOQ gedung —
 * perencanaan & pengawasan serta biaya pengelolaan (operasional P2SP).
 * Terimpor otomatis dari sheet "Prcn dan pgws" dan "ADMINISTRASI"
 * dokumen perencana (sumber = impor, diganti segar tiap impor ulang);
 * pengguna dapat menambah baris manual. Ikut dalam verifikasi anggaran.
 */
class BiayaLain extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $daftar = $db->table('biaya_lain')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('kelompok')->orderBy('id')
            ->get()->getResultArray();

        $opsiPajak = $this->opsiPajakBiayaLain();
        $kelompok = [];
        foreach ($daftar as $r) {
            $r['potongan'] = $this->potonganPajak($r['pajak'] ?? null, (float) $r['jumlah']);
            $r['bersih'] = (float) $r['jumlah'] - $r['potongan'];
            $r['label_pajak'] = $opsiPajak[$r['pajak'] ?? ''] ?? '';
            $kelompok[$r['kelompok']][] = $r;
        }
        $total = array_sum(array_map(static fn ($r) => (float) $r['jumlah'], $daftar));

        return $this->tampil('biayalain/index', [
            'kelompok' => $kelompok,
            'total' => $total,
            'tautan_spk' => $this->tautanSpk(array_column($daftar, 'id')),
            'verifikasi' => $this->verifikasiAnggaran(),
            'saran_kelompok' => ['Perencanaan & Pengawasan', 'Biaya Pengelolaan'],
            'opsi_pajak' => $opsiPajak,
        ], 'Biaya Non-Gedung');
    }

    /** Balasan ganda seperti Bahan::balasForm: AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balas(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/biaya-non-gedung')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function simpan()
    {
        $id = (int) $this->request->getPost('id');
        $kelompok = trim((string) $this->request->getPost('kelompok'));
        $uraian = trim((string) $this->request->getPost('uraian'));
        $volume = (float) str_replace(',', '.', (string) $this->request->getPost('volume'));
        $periodeRaw = trim((string) $this->request->getPost('periode'));
        $periode = $periodeRaw === '' ? null : (float) str_replace(',', '.', $periodeRaw);
        $harga = (float) preg_replace('/[^0-9]/', '', (string) $this->request->getPost('harga_satuan'));
        if ($kelompok === '' || $uraian === '' || $volume <= 0) {
            return $this->balas(false, 'Kelompok, uraian, dan volume wajib diisi.');
        }
        $pajak = trim((string) $this->request->getPost('pajak'));
        if (! array_key_exists($pajak, $this->opsiPajakBiayaLain())) {
            $pajak = '';
        }
        $data = [
            'kelompok' => $kelompok,
            'uraian' => $uraian,
            'volume' => $volume,
            'satuan' => trim((string) $this->request->getPost('satuan')) ?: null,
            'periode' => $periode,
            'satuan_periode' => trim((string) $this->request->getPost('satuan_periode')) ?: null,
            'harga_satuan' => $harga,
            'jumlah' => $volume * ($periode !== null && $periode > 0 ? $periode : 1) * $harga,
            'pajak' => $pajak !== '' ? $pajak : null,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db = $this->db();
        if ($id > 0) {
            $lama = $db->table('biaya_lain')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($lama === null) {
                return $this->balas(false, 'Baris tidak ditemukan.');
            }
            $db->table('biaya_lain')->where('id', $id)->update($data);
            return $this->balas(true, 'Baris biaya "' . $uraian . '" diperbarui.');
        }
        $data['sekolah_id'] = $this->sid();
        $data['sumber'] = 'manual';
        $data['created_at'] = date('Y-m-d H:i:s');
        $db->table('biaya_lain')->insert($data);
        return $this->balas(true, 'Baris biaya "' . $uraian . '" ditambahkan.');
    }

    public function hapus(int $id)
    {
        $baris = $this->db()->table('biaya_lain')
            ->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($baris === null) {
            return $this->balas(false, 'Baris tidak ditemukan.');
        }
        $this->hapusLunak('biaya_lain', ['id' => $id]);
        return $this->balas(true, 'Baris biaya "' . $baris['uraian'] . '" dihapus.');
    }

    /**
     * Peta tautan baris biaya -> SPK aktif yang memuatnya:
     * biaya_lain_id => ['spk_id' => id, 'nomor' => nomor SPK].
     * SPK terhapus lunak dan SPK berstatus Batal tidak dihitung —
     * baris biaya pada SPK Batal boleh di-generate ulang.
     */
    private function tautanSpk(array $biayaIds): array
    {
        $biayaIds = array_values(array_filter(array_map('intval', $biayaIds)));
        if ($biayaIds === []) {
            return [];
        }
        $rows = $this->db()->query(
            'SELECT d.biaya_lain_id, s.id AS spk_id, s.nomor
             FROM spk_detail d JOIN spk s ON s.id = d.spk_id
             WHERE d.biaya_lain_id IN (' . implode(',', array_fill(0, count($biayaIds), '?')) . ')
               AND d.is_deleted = 0 AND s.is_deleted = 0 AND s.status <> ?
               AND s.sekolah_id = ?',
            [...$biayaIds, 'Batal', $this->sid()]
        )->getResultArray();
        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['biaya_lain_id']] = ['spk_id' => (int) $r['spk_id'], 'nomor' => (int) $r['nomor']];
        }
        return $peta;
    }

    /**
     * Generate satu SPK (gedung NULL) dari baris Biaya Non-Gedung
     * terpilih. Baris yang sudah tertaut ke SPK aktif dilewati
     * (anti-dobel). Rincian SPK memakai nama/satuan manual dari
     * uraian & satuan baris biaya; qty = volume x periode (bila ada),
     * harga = harga satuan biaya. spk_detail.pajak adalah penanda PPN
     * rincian, BUKAN kode potongan biaya_lain.pajak — diisi 0; potongan
     * PPh pada baris biaya hanya acuan tampilan Bersih Diterima.
     */
    public function buatSpk()
    {
        $ids = $this->idsTerpilih();
        if ($ids === []) {
            return redirect()->to('/biaya-non-gedung')->with('galat', 'Centang minimal satu baris biaya untuk dibuatkan SPK.');
        }
        $db = $this->db();
        $baris = $db->table('biaya_lain')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->whereIn('id', $ids)
            ->orderBy('kelompok')->orderBy('id')
            ->get()->getResultArray();
        if ($baris === []) {
            return redirect()->to('/biaya-non-gedung')->with('galat', 'Baris biaya terpilih tidak ditemukan pada sekolah aktif.');
        }
        $tertaut = $this->tautanSpk(array_column($baris, 'id'));
        $proses = array_values(array_filter($baris, static fn ($r) => ! isset($tertaut[(int) $r['id']])));
        $dilewati = count($baris) - count($proses);
        if ($proses === []) {
            return redirect()->to('/biaya-non-gedung')->with('galat', 'Semua baris biaya terpilih sudah tertaut ke SPK aktif — tidak ada SPK baru yang dibuat.');
        }

        $kelompokList = array_values(array_unique(array_map(static fn ($r) => (string) $r['kelompok'], $proses)));
        $nomor = $this->nomorSpkBerikutnya();
        $hariIni = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('spk')->insert([
            'sekolah_id' => $this->sid(), 'nomor' => $nomor,
            'tanggal' => $hariIni, 'tgl_mulai' => $hariIni,
            'gedung_id' => null, 'penyedia_id' => null,
            'paket' => 'Biaya Non-Gedung — ' . implode(', ', $kelompokList),
            'status' => 'Aktif', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $spkId = (int) $db->insertID();
        foreach ($proses as $r) {
            $periode = ($r['periode'] !== null && (float) $r['periode'] > 0) ? (float) $r['periode'] : 1.0;
            $db->table('spk_detail')->insert([
                'sekolah_id' => $this->sid(), 'spk_id' => $spkId,
                'bahan_id' => null, 'gedung_id' => null,
                'nama_manual' => $r['uraian'], 'satuan_manual' => $r['satuan'],
                'qty' => (float) $r['volume'] * $periode,
                'harga' => (int) round((float) $r['harga_satuan']),
                'pajak' => 0, 'biaya_lain_id' => (int) $r['id'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $db->transComplete();
        $this->catatAudit('spk', $spkId, 'buat_dari_biaya', 'SPK ' . $nomor . ' dibuat dari ' . count($proses) . ' baris Biaya Non-Gedung (' . implode(', ', $kelompokList) . ').');

        $pesan = 'SPK ' . $nomor . ' dibuat dari ' . count($proses) . ' baris Biaya Non-Gedung. Lengkapi penyedia di rincian SPK ini.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' baris dilewati karena sudah tertaut ke SPK aktif.';
        }
        return redirect()->to('/spk/detail/' . $spkId)->with('sukses', $pesan);
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('biaya_lain')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('biaya_lain', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('/biaya-non-gedung')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' baris biaya terpilih dihapus.' : 'Tidak ada baris yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('biaya_lain', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('biaya_lain')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('biaya_lain', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('/biaya-non-gedung')->with('sukses', 'Biaya Non-Gedung dikosongkan: ' . count($ids) . ' baris dihapus (termasuk baris manual). Impor ulang dokumen perencana untuk mengisi kembali baris sumber impor.');
    }
}

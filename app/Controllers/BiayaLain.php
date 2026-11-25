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

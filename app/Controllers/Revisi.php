<?php

namespace App\Controllers;

/**
 * Berita Acara Revisi Pekerjaan: mencatat setiap perubahan pekerjaan
 * (volume/biaya) beserta berita acaranya untuk dicetak dan
 * ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis.
 *
 * Revisi terstruktur (jenis volume/baru/ganti/hapus) berstatus draft
 * saat dicatat dan baru mengubah RAB berjalan setelah DISAHKAN.
 * RAB awal tidak pernah berubah; yang bergerak adalah volume/harga
 * berjalan pada pekerjaan (kolom volume_revisi/harga_revisi).
 */
class Revisi extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $rows = $db->query(
            'SELECT r.*, g.nama AS gedung_nama, p.nama AS pekerjaan_nama
             FROM revisi_pekerjaan r JOIN gedung g ON g.id = r.gedung_id
             LEFT JOIN pekerjaan p ON p.id = r.pekerjaan_id
             WHERE r.sekolah_id = ? AND r.is_deleted = 0
             ORDER BY r.tanggal DESC, r.id DESC',
            [$this->sid()]
        )->getResultArray();
        $pekerjaan = $db->query(
            'SELECT p.* FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0
             ORDER BY p.gedung_id, p.id',
            [$this->sid()]
        )->getResultArray();
        $analisa = $db->table('analisa')->select('kode, nama')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('kode')->get()->getResultArray();
        return $this->tampil('revisi/index', [
            'revisi' => $rows, 'gedung' => $this->semuaGedung(), 'pekerjaan' => $pekerjaan,
            'analisa' => $analisa,
        ], 'Berita Acara Revisi Pekerjaan — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        $r = $this->request;
        $gedungId = (int) $r->getPost('gedung_id');
        $uraian = trim((string) $r->getPost('uraian'));
        if (! $this->ambilGedung($gedungId) || $uraian === '' || ! $r->getPost('tanggal')) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Gedung, tanggal, dan uraian revisi wajib diisi.');
        }
        $jenis = (string) $r->getPost('jenis');
        if (! in_array($jenis, ['catatan', 'volume', 'baru', 'ganti', 'hapus'], true)) {
            $jenis = 'catatan';
        }
        $pekerjaanId = (int) $r->getPost('pekerjaan_id') ?: null;
        if ($pekerjaanId !== null) {
            $p = $this->db()->table('pekerjaan')->where(['id' => $pekerjaanId, 'gedung_id' => $gedungId, 'is_deleted' => 0])->get()->getRowArray();
            if (! $p) {
                $pekerjaanId = null;
            }
        }
        $volumeBaru = $r->getPost('volume_baru') !== null && $r->getPost('volume_baru') !== ''
            ? (float) str_replace(',', '.', (string) $r->getPost('volume_baru')) : null;
        $hargaBaru = $r->getPost('harga_baru') !== null && $r->getPost('harga_baru') !== ''
            ? (float) str_replace(['.', ','], ['', '.'], (string) $r->getPost('harga_baru')) : null;
        $namaBaru = trim((string) $r->getPost('nama_baru')) ?: null;

        if (in_array($jenis, ['volume', 'hapus', 'ganti'], true) && $pekerjaanId === null) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Revisi jenis ini wajib memilih pekerjaan yang diubah.');
        }
        if ($jenis === 'volume' && $volumeBaru === null) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Volume baru wajib diisi untuk revisi volume.');
        }
        if (in_array($jenis, ['baru', 'ganti'], true) && ($namaBaru === null || $volumeBaru === null || $hargaBaru === null)) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Pekerjaan pengganti/baru wajib diisi nama, volume, dan harga satuannya.');
        }
        $this->db()->table('revisi_pekerjaan')->insert([
            'sekolah_id' => $this->sid(), 'gedung_id' => $gedungId, 'pekerjaan_id' => $pekerjaanId,
            'tanggal' => (string) $r->getPost('tanggal'), 'uraian' => $uraian,
            'perubahan' => trim((string) $r->getPost('perubahan')) ?: null,
            'status' => 'draft', 'jenis' => $jenis,
            'volume_baru' => $volumeBaru, 'harga_baru' => $hargaBaru,
            'nama_baru' => $namaBaru,
            'satuan_baru' => trim((string) $r->getPost('satuan_baru')) ?: null,
            'kode_analisa_baru' => trim((string) $r->getPost('kode_analisa_baru')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Revisi pekerjaan dicatat sebagai draft. Sahkan agar mengubah RAB berjalan.');
    }

    /** Selisih nilai revisi terhadap RAB berjalan saat ini. */
    private function deltaNilai(array $r): float
    {
        $db = $this->db();
        $p = null;
        $lama = 0.0;
        if (! empty($r['pekerjaan_id'])) {
            $p = $db->table('pekerjaan')->where('id', (int) $r['pekerjaan_id'])->get()->getRowArray();
            if ($p) {
                $lama = $this->volBerjalan($p) * $this->hargaBerjalan($p);
            }
        }
        return match ($r['jenis']) {
            'volume' => (float) $r['volume_baru'] * (float) ($r['harga_baru'] ?? ($p ? $this->hargaBerjalan($p) : 0)) - $lama,
            'hapus' => -$lama,
            'baru' => (float) $r['volume_baru'] * (float) $r['harga_baru'],
            'ganti' => -$lama + (float) $r['volume_baru'] * (float) $r['harga_baru'],
            default => 0.0,
        };
    }

    public function sahkan($id)
    {
        if ($d = $this->penjagaPeran(['ketua', 'kepsek'])) {
            return $d;
        }
        $db = $this->db();
        $r = $db->table('revisi_pekerjaan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $r) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Catatan revisi tidak ditemukan.');
        }
        if ($r['status'] === 'disahkan') {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Revisi ini sudah disahkan.');
        }
        // Kontrol pagu: total RAB berjalan + biaya non-gedung sesudah
        // revisi ini tidak boleh melebihi pagu sekolah.
        $ver = $this->verifikasiAnggaran();
        $delta = $this->deltaNilai($r);
        if ($ver['pagu'] > 0 && $ver['total_semua'] + $delta > $ver['pagu'] + 0.5) {
            return redirect()->to('/revisi-pekerjaan')->with('galat',
                'Revisi ditolak: total RAB berjalan menjadi Rp ' . number_format($ver['total_semua'] + $delta, 0, ',', '.')
                . ', melebihi pagu Rp ' . number_format($ver['pagu'], 0, ',', '.')
                . '. Kurangi pekerjaan lain lewat revisi terlebih dahulu, atau sesuaikan pagu.');
        }
        $db->transStart();
        $db->table('revisi_pekerjaan')->where('id', (int) $id)
            ->update(['status' => 'disahkan', 'disahkan_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        if (in_array($r['jenis'], ['volume', 'hapus'], true) && ! empty($r['pekerjaan_id'])) {
            $this->segarkanBerjalan((int) $r['pekerjaan_id']);
        }
        if (in_array($r['jenis'], ['baru', 'ganti'], true)) {
            if ($r['jenis'] === 'ganti' && ! empty($r['pekerjaan_id'])) {
                $this->segarkanBerjalan((int) $r['pekerjaan_id']);
            }
            $urutan = (int) ($db->query('SELECT COALESCE(MAX(urutan), 0) AS m FROM pekerjaan WHERE gedung_id = ?', [(int) $r['gedung_id']])->getRowArray()['m'] ?? 0) + 1;
            $db->table('pekerjaan')->insert([
                'gedung_id' => (int) $r['gedung_id'], 'bab' => 'Pekerjaan Revisi',
                'nama' => $r['nama_baru'], 'kode_analisa' => $r['kode_analisa_baru'],
                'volume' => 0, 'volume_revisi' => (float) $r['volume_baru'],
                'satuan' => $r['satuan_baru'] ?: 'ls', 'harga_satuan' => (float) $r['harga_baru'],
                'jumlah' => 0, 'urutan' => $urutan, 'dari_revisi_id' => (int) $id,
            ]);
            $baruId = (int) $db->insertID();
            $db->table('revisi_pekerjaan')->where('id', (int) $id)->update(['pekerjaan_baru_id' => $baruId]);
        }
        $db->transComplete();
        $this->catatAudit('revisi', (int) $id, 'sahkan', 'Revisi disahkan; selisih RAB berjalan ' . ($delta >= 0 ? '+' : '-') . 'Rp ' . number_format(abs($delta), 0, ',', '.'));
        return redirect()->to('/revisi-pekerjaan')->with('sukses',
            'Revisi disahkan — RAB berjalan diperbarui (selisih ' . ($delta >= 0 ? '+' : '−') . 'Rp ' . number_format(abs($delta), 0, ',', '.') . ').');
    }

    /** Urungkan dampak sebuah revisi disahkan (pekerjaan pengganti dihapus lunak, berjalan dihitung ulang). */
    private function batalkanDampak(array $r): void
    {
        if (! empty($r['pekerjaan_baru_id'])) {
            $this->hapusLunak('pekerjaan', ['id' => (int) $r['pekerjaan_baru_id']]);
        }
        if (! empty($r['pekerjaan_id'])) {
            $this->segarkanBerjalan((int) $r['pekerjaan_id']);
        }
    }

    public function batalSah($id)
    {
        if ($d = $this->penjagaPeran(['ketua', 'kepsek'])) {
            return $d;
        }
        $db = $this->db();
        $r = $db->table('revisi_pekerjaan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $r || $r['status'] !== 'disahkan') {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Hanya revisi yang sudah disahkan yang bisa dibatalkan pengesahannya.');
        }
        $db->transStart();
        $db->table('revisi_pekerjaan')->where('id', (int) $id)
            ->update(['status' => 'draft', 'disahkan_at' => null, 'pekerjaan_baru_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->batalkanDampak($r);
        $db->transComplete();
        $this->catatAudit('revisi', (int) $id, 'batal-sah', 'Pengesahan revisi dibatalkan; RAB berjalan dikembalikan.');
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Pengesahan dibatalkan — RAB berjalan kembali seperti sebelum revisi ini.');
    }

    public function hapus($id)
    {
        $r = $this->db()->table('revisi_pekerjaan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($r && $r['status'] === 'disahkan') {
            $this->db()->table('revisi_pekerjaan')->where('id', (int) $id)
                ->update(['status' => 'draft', 'disahkan_at' => null, 'pekerjaan_baru_id' => null]);
            $this->batalkanDampak($r);
        }
        $this->hapusLunak('revisi_pekerjaan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Catatan revisi dihapus.');
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('revisi_pekerjaan')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapus($id);
                $n++;
            }
        }
        return redirect()->to('/revisi-pekerjaan')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' catatan revisi terpilih dihapus.' : 'Tidak ada catatan yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('revisi', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('revisi_pekerjaan')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapus((int) $id);
        }
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Catatan revisi dikosongkan: ' . count($ids) . ' catatan dihapus.');
    }

    public function cetak($id)
    {
        $r = $this->db()->query(
            'SELECT r.*, g.nama AS gedung_nama, p.nama AS pekerjaan_nama
             FROM revisi_pekerjaan r JOIN gedung g ON g.id = r.gedung_id
             LEFT JOIN pekerjaan p ON p.id = r.pekerjaan_id
             WHERE r.id = ? AND r.sekolah_id = ? AND r.is_deleted = 0',
            [(int) $id, $this->sid()]
        )->getRowArray();
        if (! $r) {
            return redirect()->to('/revisi-pekerjaan')->with('galat', 'Catatan revisi tidak ditemukan.');
        }
        return view('cetak/ba_revisi', ['idn' => $this->sekolahAktif(), 'r' => $r]);
    }
}

<?php

namespace App\Controllers;

/**
 * Berita Acara Revisi Pekerjaan: mencatat setiap perubahan pekerjaan
 * (volume/biaya) beserta berita acaranya untuk dicetak dan
 * ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis.
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
        return $this->tampil('revisi/index', [
            'revisi' => $rows, 'gedung' => $this->semuaGedung(), 'pekerjaan' => $pekerjaan,
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
        $pekerjaanId = (int) $r->getPost('pekerjaan_id') ?: null;
        if ($pekerjaanId !== null) {
            $p = $this->db()->table('pekerjaan')->where(['id' => $pekerjaanId, 'gedung_id' => $gedungId, 'is_deleted' => 0])->get()->getRowArray();
            if (! $p) {
                $pekerjaanId = null;
            }
        }
        $this->db()->table('revisi_pekerjaan')->insert([
            'sekolah_id' => $this->sid(), 'gedung_id' => $gedungId, 'pekerjaan_id' => $pekerjaanId,
            'tanggal' => (string) $r->getPost('tanggal'), 'uraian' => $uraian,
            'perubahan' => trim((string) $r->getPost('perubahan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Revisi pekerjaan dicatat.');
    }

    public function hapus($id)
    {
        $this->hapusLunak('revisi_pekerjaan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('/revisi-pekerjaan')->with('sukses', 'Catatan revisi dihapus.');
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

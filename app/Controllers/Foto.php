<?php

namespace App\Controllers;

/**
 * Dokumentasi Foto Progres: unggah dan kelola foto kondisi sebelum,
 * selama (proses), dan sesudah pekerjaan per gedung. Foto tersimpan
 * di folder publik uploads/foto-progres dan menjadi lampiran laporan
 * kemajuan serta laporan akhir sesuai Juknis.
 */
class Foto extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $rows = $db->query(
            'SELECT f.*, g.nama AS gedung_nama FROM foto_progres f
             LEFT JOIN gedung g ON g.id = f.gedung_id
             WHERE f.sekolah_id = ? AND f.is_deleted = 0
             ORDER BY f.tanggal DESC, f.id DESC',
            [$this->sid()]
        )->getResultArray();
        $hitung = ['sebelum' => 0, 'proses' => 0, 'akhir' => 0];
        foreach ($rows as $r) {
            if (isset($hitung[$r['tahap']])) {
                $hitung[$r['tahap']]++;
            }
        }
        return $this->tampil('foto/index', [
            'foto' => $rows, 'hitung' => $hitung, 'gedung' => $this->semuaGedung(),
        ], 'Dokumentasi Foto Progres — Aplikasi PBJ Sekolah');
    }

    public function unggah()
    {
        $r = $this->request;
        $file = $r->getFile('foto');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('/foto-progres')->with('galat', 'Pilih berkas foto yang valid terlebih dahulu.');
        }
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return redirect()->to('/foto-progres')->with('galat', 'Berkas harus berupa gambar JPG, PNG, atau WebP.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to('/foto-progres')->with('galat', 'Ukuran foto maksimal 5 MB.');
        }
        $tahap = (string) $r->getPost('tahap');
        if (! in_array($tahap, ['sebelum', 'proses', 'akhir'], true)) {
            $tahap = 'proses';
        }
        $gedungId = (int) $r->getPost('gedung_id') ?: null;
        if ($gedungId !== null && ! $this->ambilGedung($gedungId)) {
            $gedungId = null;
        }
        $dir = FCPATH . 'uploads/foto-progres';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $namaBaru = $file->getRandomName();
        $file->move($dir, $namaBaru);
        $this->db()->table('foto_progres')->insert([
            'sekolah_id' => $this->sid(),
            'gedung_id' => $gedungId,
            'tahap' => $tahap,
            'tanggal' => (string) $r->getPost('tanggal') ?: date('Y-m-d'),
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'berkas' => 'uploads/foto-progres/' . $namaBaru,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/foto-progres')->with('sukses', 'Foto progres tersimpan.');
    }

    public function hapus($id)
    {
        $row = $this->db()->table('foto_progres')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->get()->getRowArray();
        if ($row) {
            $this->hapusLunak('foto_progres', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
            $path = FCPATH . $row['berkas'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        return redirect()->to('/foto-progres')->with('sukses', 'Foto dihapus.');
    }

    /** Inti penghapusan satu foto (catatan + berkas) untuk dipakai massal. */
    private function hapusSatuFoto(int $id): bool
    {
        $row = $this->db()->table('foto_progres')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $row) {
            return false;
        }
        $this->hapusLunak('foto_progres', ['id' => $id, 'sekolah_id' => $this->sid()]);
        $path = FCPATH . $row['berkas'];
        if (is_file($path)) {
            unlink($path);
        }
        return true;
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            if ($this->hapusSatuFoto($id)) {
                $n++;
            }
        }
        return redirect()->to('/foto-progres')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' foto terpilih dihapus beserta berkasnya.' : 'Tidak ada foto yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('foto', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('foto_progres')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        $n = 0;
        foreach ($ids as $id) {
            if ($this->hapusSatuFoto((int) $id)) {
                $n++;
            }
        }
        return redirect()->to('/foto-progres')->with('sukses', 'Foto progres dikosongkan: ' . $n . ' foto dihapus beserta berkasnya.');
    }
}

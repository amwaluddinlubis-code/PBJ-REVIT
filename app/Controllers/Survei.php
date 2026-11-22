<?php

namespace App\Controllers;

class Survei extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $identitas = $this->sekolahAktif();
        $bahan = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray();
        $harga = [];
        foreach ($db->table('survei_harga')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $harga[$r['bahan_id']][$r['toko']] = $r['harga'];
        }
        return $this->tampil('survei/index', [
            'identitas' => $identitas, 'bahan' => $bahan, 'harga' => $harga,
        ], 'Survei Harga — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        $db = $this->db();
        $tgl = $this->request->getPost('tanggal_survei');
        $db->table('sekolah')->where('id', $this->sid())->update(['tanggal_survei' => $tgl ?: null]);
        $semua = $this->request->getPost('harga') ?? [];
        $db->transStart();
        foreach ($semua as $bahanId => $perToko) {
            foreach ($perToko as $toko => $nilai) {
                $ada = $db->table('survei_harga')->where(['bahan_id' => (int) $bahanId, 'toko' => (int) $toko, 'is_deleted' => 0])->get()->getRowArray();
                if ($nilai === '' || $nilai === null) {
                    if ($ada) {
                        $this->hapusLunak('survei_harga', ['id' => $ada['id']]);
                    }
                } elseif ($ada) {
                    $db->table('survei_harga')->where('id', $ada['id'])->update(['harga' => (int) $nilai]);
                } else {
                    $db->table('survei_harga')->insert(['bahan_id' => (int) $bahanId, 'toko' => (int) $toko, 'harga' => (int) $nilai]);
                }
            }
        }
        $db->transComplete();
        return redirect()->to('survei')->with('sukses', 'Harga survei disimpan. Kolom Termurah & Terpilih terhitung otomatis.');
    }

    /** Hapus harga survei bahan tercentang (semua toko). */
    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $bahanId) {
            $milik = $this->db()->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($milik === 0) {
                continue;
            }
            $ada = $this->db()->table('survei_harga')->where(['bahan_id' => $bahanId, 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('survei_harga', ['bahan_id' => $bahanId]);
                $n++;
            }
        }
        return redirect()->to('survei')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? 'Harga survei ' . $n . ' bahan terpilih dihapus (semua toko).' : 'Tidak ada harga survei yang diproses.');
    }

    /** Kosongkan seluruh harga survei sekolah aktif. */
    public function kosongkan()
    {
        $n = (int) $this->db()->query(
            'SELECT COUNT(*) j FROM survei_harga s JOIN bahan b ON b.id = s.bahan_id WHERE b.sekolah_id = ? AND s.is_deleted = 0',
            [$this->sid()]
        )->getRow()->j;
        $this->hapusLunak('survei_harga', fn ($b) => $b->whereIn('bahan_id',
            $this->db()->table('bahan')->select('id')->where('sekolah_id', $this->sid())));
        return redirect()->to('survei')->with('sukses', 'Harga survei dikosongkan: ' . $n . ' harga toko dihapus.');
    }
}

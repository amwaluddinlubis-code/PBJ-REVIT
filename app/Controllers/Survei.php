<?php

namespace App\Controllers;

class Survei extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $identitas = $db->table('identitas')->get()->getRowArray();
        $bahan = $db->table('bahan')->orderBy('kode')->get()->getResultArray();
        $harga = [];
        foreach ($db->table('survei_harga')->get()->getResultArray() as $r) {
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
        $db->table('identitas')->where('id', 1)->update(['tanggal_survei' => $tgl ?: null]);
        $semua = $this->request->getPost('harga') ?? [];
        $db->transStart();
        foreach ($semua as $bahanId => $perToko) {
            foreach ($perToko as $toko => $nilai) {
                $ada = $db->table('survei_harga')->where(['bahan_id' => (int) $bahanId, 'toko' => (int) $toko])->get()->getRowArray();
                if ($nilai === '' || $nilai === null) {
                    if ($ada) {
                        $db->table('survei_harga')->where('id', $ada['id'])->delete();
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
}

<?php

namespace App\Controllers;

use App\Libraries\ParserPerencana;

class Impor extends BaseController
{
    public function index()
    {
        $log = $this->db()->table('impor_log')->where('sekolah_id', $this->sid())
            ->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($log as &$l) {
            $l['ringkasan'] = json_decode((string) ($l['ringkasan'] ?? ''), true) ?: null;
        }
        return $this->tampil('impor/index', ['log' => $log], 'Impor Dokumen Perencana');
    }

    public function proses()
    {
        $berkas = $this->request->getFile('berkas');
        if ($berkas === null || ! $berkas->isValid()) {
            return redirect()->to('/impor')->with('galat', 'Pilih berkas .xlsx dokumen perencana terlebih dahulu.');
        }
        if (strtolower($berkas->getClientExtension()) !== 'xlsx') {
            return redirect()->to('/impor')->with('galat', 'Berkas harus berformat .xlsx.');
        }
        $folder = (new \Config\Paths())->writableDirectory . '/uploads';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $namaBaru = 'perencana-' . date('Ymd-His') . '.xlsx';
        $berkas->move($folder, $namaBaru);
        try {
            $parser = new ParserPerencana($this->db(), $this->sid());
            $r = $parser->impor($folder . '/' . $namaBaru);
        } catch (\Throwable $e) {
            return redirect()->to('/impor')->with('galat', 'Impor gagal: ' . $e->getMessage());
        }
        $this->db()->table('impor_log')->insert([
            'sekolah_id' => $this->sid(), 'berkas' => $berkas->getClientName(),
            'ringkasan' => json_encode($r), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $pesan = sprintf(
            'Impor selesai: %d gedung, %d analisa (%d komponen), %d bahan baru ditambahkan ke master.',
            count($r['gedung']),
            $r['analisa'],
            $r['komponen'],
            $r['bahan_baru']
        );
        return redirect()->to('/impor')->with('sukses', $pesan);
    }
}

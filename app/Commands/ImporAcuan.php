<?php

namespace App\Commands;

use App\Libraries\PengimporAcuan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImporAcuan extends BaseCommand
{
    protected $group       = 'PBJ';
    protected $name        = 'impor:acuan';
    protected $description = 'Muat Master Acuan AHSP Nasional (app/Data/ahsp_acuan.json) ke database.';
    protected $usage       = 'impor:acuan';

    public function run(array $params)
    {
        $path = APPPATH . 'Data/ahsp_acuan.json';
        if (! is_file($path)) {
            CLI::error('Berkas data acuan tidak ditemukan: ' . $path);
            return;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            CLI::error('Berkas data acuan bukan JSON yang sah.');
            return;
        }
        $db = \Config\Database::connect();
        if (! $db->tableExists('ahsp_acuan')) {
            CLI::error('Tabel ahsp_acuan belum ada — jalankan php spark migrate dulu.');
            return;
        }
        try {
            $r = (new PengimporAcuan($db))->impor($data);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }
        CLI::write("Master acuan dimuat: {$r['item']} item ({$r['tanpa']} tanpa padanan), {$r['komponen']} komponen.", 'green');
    }
}

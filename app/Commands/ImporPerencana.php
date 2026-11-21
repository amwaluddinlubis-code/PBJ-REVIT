<?php

namespace App\Commands;

use App\Libraries\ParserPerencana;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImporPerencana extends BaseCommand
{
    protected $group       = 'PBJ';
    protected $name        = 'impor:perencana';
    protected $description = 'Impor dokumen perencana (.xlsx) ke sekolah pertama / sekolah_id tertentu.';
    protected $usage       = 'impor:perencana <berkas.xlsx> [sekolah_id]';

    public function run(array $params)
    {
        $path = $params[0] ?? '';
        if ($path === '' || ! is_file($path)) {
            CLI::error('Berkas tidak ditemukan: ' . $path);
            return;
        }
        $db = \Config\Database::connect();
        $sid = isset($params[1]) ? (int) $params[1] : (int) ($db->table('sekolah')->orderBy('id')->get()->getRowArray()['id'] ?? 1);
        $parser = new ParserPerencana($db, $sid);
        $r = $parser->impor($path);
        CLI::write('Analisa: ' . $r['analisa'] . ' | Komponen: ' . $r['komponen'] . ' (tak tertaut: ' . $r['komponen_tak_tertaut'] . ')');
        CLI::write('Bahan baru: ' . $r['bahan_baru'] . ' | Upah: ' . $r['upah'] . ' | Total anggaran: Rp ' . number_format($r['total_anggaran'], 0, ',', '.'));
        foreach ($r['gedung'] as $g) {
            $selisih = $g['total_hitung'] - $g['total_sheet'];
            CLI::write(sprintf(
                'Gedung %-22s pekerjaan=%-4d hitung=Rp %s sheet=Rp %s selisih=Rp %s',
                $g['nama'],
                $g['pekerjaan'],
                number_format($g['total_hitung'], 0, ',', '.'),
                number_format($g['total_sheet'], 0, ',', '.'),
                number_format($selisih, 0, ',', '.')
            ));
        }
    }
}

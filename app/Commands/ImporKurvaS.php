<?php

namespace App\Commands;

use App\Libraries\PengimporKurvaS;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImporKurvaS extends BaseCommand
{
    protected $group       = 'PBJ';
    protected $name        = 'impor:kurva-s';
    protected $description = 'Impor rencana Kurva S resmi (.json) ke sekolah pertama / sekolah_id tertentu.';
    protected $usage       = 'impor:kurva-s <berkas.json> [sekolah_id]';

    public function run(array $params)
    {
        $path = $params[0] ?? '';
        if ($path === '' || ! is_file($path)) {
            CLI::error('Berkas tidak ditemukan: ' . $path);
            return;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            CLI::error('Berkas bukan JSON yang sah.');
            return;
        }
        $db = \Config\Database::connect();
        $sid = isset($params[1]) ? (int) $params[1] : (int) ($db->table('sekolah')->orderBy('id')->get()->getRowArray()['id'] ?? 1);
        try {
            $r = (new PengimporKurvaS($db, $sid))->impor($data);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }
        CLI::write('Minggu: ' . $r['minggu'] . ' | Baris bab: ' . $r['baris'] . ' | Total bobot: ' . number_format($r['total_bobot'], 2, ',', '.') . '%');
        foreach ($r['pemetaan'] as $grup => $gedung) {
            CLI::write(sprintf('  %-18s -> %s', $grup, $gedung ?? '(kurva total saja)'));
        }
    }
}

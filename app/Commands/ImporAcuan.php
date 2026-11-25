<?php

namespace App\Commands;

use App\Libraries\PengimporAcuan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImporAcuan extends BaseCommand
{
    protected $group       = 'PBJ';
    protected $name        = 'impor:acuan';
    protected $description = 'Muat Acuan AHSP (app/Data/ahsp_acuan.json): tanpa argumen ke templat nasional, dengan id sekolah ke set sekolah itu, atau "semua".';
    protected $usage       = 'impor:acuan [sekolah_id|semua]';

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
        if (! in_array('sekolah_id', $db->getFieldNames('ahsp_acuan'), true)) {
            CLI::error('Kolom sekolah_id belum ada — jalankan php spark migrate (000029) dulu.');
            return;
        }
        // Sasaran: [label, sekolah_id|null] — null = templat nasional.
        $arg = isset($params[0]) ? (string) $params[0] : null;
        $sasaran = [];
        if ($arg === null) {
            $sasaran[] = ['templat nasional', null];
        } elseif ($arg === 'semua') {
            $sasaran[] = ['templat nasional', null];
            foreach ($db->table('sekolah')->where('is_deleted', 0)->get()->getResultArray() as $s) {
                $sasaran[] = [(string) $s['nama_sekolah'], (int) $s['id']];
            }
        } elseif (ctype_digit($arg)) {
            $s = $db->table('sekolah')->where('id', (int) $arg)->where('is_deleted', 0)->get()->getRowArray();
            if ($s === null) {
                CLI::error('Sekolah dengan id ' . $arg . ' tidak ditemukan.');
                return;
            }
            $sasaran[] = [(string) $s['nama_sekolah'], (int) $s['id']];
        } else {
            CLI::error('Argumen tidak dikenal. Pakai: impor:acuan | impor:acuan <id-sekolah> | impor:acuan semua');
            return;
        }
        foreach ($sasaran as [$label, $sidTujuan]) {
            try {
                $r = (new PengimporAcuan($db, $sidTujuan))->impor($data);
            } catch (\Throwable $e) {
                CLI::error($label . ': ' . $e->getMessage());
                return;
            }
            CLI::write("Acuan dimuat ke {$label}: {$r['item']} item ({$r['tanpa']} tanpa padanan), {$r['komponen']} komponen.", 'green');
        }
    }
}

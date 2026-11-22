<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Memuat Master Acuan AHSP Nasional dari berkas JSON bawaan aplikasi
 * (app/Data/ahsp_acuan.json) ke tabel ahsp_acuan + ahsp_acuan_komponen.
 * Idempoten: item dicocokkan lewat kode_kita; komponen lama dihapus lunak
 * lalu ditulis ulang, baris item yang terhapus dihidupkan kembali.
 */
class PengimporAcuan
{
    private BaseConnection $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /** @return array{item:int, komponen:int, tanpa:int} */
    public function impor(array $data): array
    {
        $items = $data['item'] ?? null;
        if (! is_array($items)) {
            throw new \RuntimeException('Struktur JSON tidak dikenal: kunci "item" tidak ada.');
        }
        $now = date('Y-m-d H:i:s');
        $nItem = 0;
        $nKomp = 0;
        $nTanpa = 0;
        $this->db->transStart();
        foreach ($items as $it) {
            $kode = trim((string) ($it['kode_kita'] ?? ''));
            if ($kode === '') {
                continue;
            }
            $tanpa = ! empty($it['tanpa_padanan']);
            if ($tanpa) {
                $nTanpa++;
            }
            $row = [
                'kode_nasional' => (string) ($it['kode_nasional'] ?? ''),
                'nama'          => (string) ($it['nama'] ?? ''),
                'satuan'        => (string) ($it['satuan'] ?? ''),
                'catatan'       => (string) ($it['catatan'] ?? ''),
                'tanpa_padanan' => $tanpa ? 1 : 0,
                'is_deleted'    => 0,
                'deleted_at'    => null,
                'updated_at'    => $now,
            ];
            $ada = $this->db->table('ahsp_acuan')->where('kode_kita', $kode)->get()->getRowArray();
            if ($ada) {
                $id = (int) $ada['id'];
                $this->db->table('ahsp_acuan')->where('id', $id)->update($row);
            } else {
                $row['kode_kita']  = $kode;
                $row['created_at'] = $now;
                $this->db->table('ahsp_acuan')->insert($row);
                $id = (int) $this->db->insertID();
            }
            $nItem++;
            $this->db->table('ahsp_acuan_komponen')->where('acuan_id', $id)
                ->update(['is_deleted' => 1, 'deleted_at' => $now, 'updated_at' => $now]);
            foreach (($it['komponen'] ?? []) as $k) {
                $this->db->table('ahsp_acuan_komponen')->insert([
                    'acuan_id'   => $id,
                    'jenis'      => (string) ($k['jenis'] ?? 'bahan'),
                    'nama'       => (string) ($k['nama'] ?? ''),
                    'satuan'     => (string) ($k['satuan'] ?? ''),
                    'koefisien'  => (float) ($k['koefisien'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $nKomp++;
            }
        }
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Impor acuan gagal, transaksi dibatalkan.');
        }
        return ['item' => $nItem, 'komponen' => $nKomp, 'tanpa' => $nTanpa];
    }
}

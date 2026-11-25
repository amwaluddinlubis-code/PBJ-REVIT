<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Memuat Acuan AHSP dari berkas JSON bawaan aplikasi
 * (app/Data/ahsp_acuan.json) ke tabel ahsp_acuan + ahsp_acuan_komponen.
 * Sejak migrasi 000029 acuan bersifat PER SEKOLAH: konstruktor menerima
 * sekolah tujuan; tanpa sekolah, yang dimuat adalah TEMPLAT nasional
 * (sekolah_id NULL) — sumber kloning, bukan data pembanding langsung.
 * Idempoten: item dicocokkan lewat (sekolah_id, kode_kita); komponen lama
 * dihapus lunak lalu ditulis ulang, baris item yang terhapus dihidupkan
 * kembali.
 */
class PengimporAcuan
{
    private BaseConnection $db;
    private ?int $sekolahId;

    public function __construct(BaseConnection $db, ?int $sekolahId = null)
    {
        $this->db = $db;
        $this->sekolahId = $sekolahId;
    }

    /**
     * Kloning templat nasional (sekolah_id NULL) ke satu sekolah: hanya
     * item yang kode_kita-nya belum ada (aktif) di sekolah itu, beserta
     * komponennya. Mengembalikan jumlah item yang dikloning. Dipakai
     * tombol "Siapkan Acuan dari Nasional" dan pembuatan sekolah baru.
     */
    public static function kloningKeSekolah(BaseConnection $db, int $sekolahId): int
    {
        if (! $db->tableExists('ahsp_acuan')) {
            return 0;
        }
        $sudah = [];
        foreach ($db->table('ahsp_acuan')->select('kode_kita')->where('sekolah_id', $sekolahId)->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $sudah[(string) $r['kode_kita']] = true;
        }
        $templat = $db->table('ahsp_acuan')->where('sekolah_id', null)->where('is_deleted', 0)->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        $n = 0;
        $db->transStart();
        foreach ($templat as $t) {
            if (isset($sudah[(string) $t['kode_kita']])) {
                continue;
            }
            $komponen = $db->table('ahsp_acuan_komponen')
                ->where('acuan_id', (int) $t['id'])->where('is_deleted', 0)->get()->getResultArray();
            unset($t['id']);
            $t['sekolah_id'] = $sekolahId;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
            $db->table('ahsp_acuan')->insert($t);
            $idBaru = (int) $db->insertID();
            foreach ($komponen as $k) {
                unset($k['id']);
                $k['acuan_id'] = $idBaru;
                $k['created_at'] = $now;
                $k['updated_at'] = $now;
                $db->table('ahsp_acuan_komponen')->insert($k);
            }
            $n++;
        }
        $db->transComplete();
        return $n;
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
            $ada = $this->db->table('ahsp_acuan')->where('kode_kita', $kode)
                ->where('sekolah_id', $this->sekolahId)->get()->getRowArray();
            if ($ada) {
                $id = (int) $ada['id'];
                $this->db->table('ahsp_acuan')->where('id', $id)->update($row);
            } else {
                $row['kode_kita']  = $kode;
                $row['sekolah_id'] = $this->sekolahId;
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

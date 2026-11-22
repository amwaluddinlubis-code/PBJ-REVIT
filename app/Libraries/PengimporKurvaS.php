<?php

namespace App\Libraries;

/**
 * Impor rencana Kurva S resmi (Time Schedule perencana) dari berkas JSON:
 * { minggu: [{ke, mulai, selesai}], baris: [{grup, bab, jumlah, bobot,
 * minggu: [bobot % per minggu]}] }. Bobot dalam persen terhadap total
 * proyek. Kelompok dokumen dipetakan ke gedung aplikasi berdasar kata
 * kunci nama; kelompok Persiapan & Biaya Manajemen (dan kelompok yang
 * tidak dikenal) disimpan tanpa gedung — hanya masuk kurva total sekolah.
 * Impor menggantikan rencana lama sekolah yang sama (hapus lunak).
 */
class PengimporKurvaS
{
    private $db;
    private int $sid;

    public function __construct($db, int $sid)
    {
        $this->db = $db;
        $this->sid = $sid;
    }

    /** Kata kunci pemetaan grup dokumen → gedung, menurut urutan prioritas. */
    private function petaGrup(array $gedung): array
    {
        $cari = static function (array $kataKunci) use ($gedung): ?int {
            foreach ($kataKunci as $k) {
                foreach ($gedung as $g) {
                    if (str_contains(strtoupper((string) $g['nama']), $k)) {
                        return (int) $g['id'];
                    }
                }
            }
            return null;
        };
        return [
            'RUANG KELAS BARU' => $cari(['RKB 3', 'RKB', 'KELAS']),
            'TOILET'           => $cari(['TOILET']),
            'RUANG GURU'       => $cari(['ADM', 'ADMINISTRASI', 'GURU']),
            'PERSIAPAN'        => null,
            'BIAYA MANAGEMEN'  => null,
            'BIAYA MANAJEMEN'  => null,
        ];
    }

    /**
     * @return array{minggu:int, baris:int, pemetaan:array<string,?string>, total_bobot:float}
     */
    public function impor(array $data): array
    {
        $minggu = $data['minggu'] ?? [];
        $baris = $data['baris'] ?? [];
        if ($minggu === [] || $baris === []) {
            throw new \InvalidArgumentException('Struktur JSON tidak dikenal: wajib ada daftar "minggu" dan "baris".');
        }
        $db = $this->db;
        $gedung = $db->table('gedung')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])
            ->orderBy('urutan, id')->get()->getResultArray();
        $peta = $this->petaGrup($gedung);
        $namaGedung = array_column($gedung, 'nama', 'id');
        $sekarang = date('Y-m-d H:i:s');

        $db->transStart();
        $db->table('kurva_rencana')->where('sekolah_id', $this->sid)
            ->update(['is_deleted' => 1, 'deleted_at' => $sekarang, 'updated_at' => $sekarang]);
        $db->table('kurva_minggu')->where('sekolah_id', $this->sid)
            ->update(['is_deleted' => 1, 'deleted_at' => $sekarang, 'updated_at' => $sekarang]);

        foreach ($minggu as $m) {
            $db->table('kurva_minggu')->insert([
                'sekolah_id' => $this->sid, 'minggu_ke' => (int) ($m['ke'] ?? 0),
                'tanggal_mulai' => $m['mulai'] ?? null, 'tanggal_selesai' => $m['selesai'] ?? null,
                'created_at' => $sekarang, 'updated_at' => $sekarang,
            ]);
        }
        $pemetaan = [];
        $totalBobot = 0.0;
        $jmlBaris = 0;
        foreach ($baris as $b) {
            $grup = strtoupper(trim((string) ($b['grup'] ?? '')));
            $gid = $peta[$grup] ?? null;
            $pemetaan[$grup] = $gid !== null ? ($namaGedung[$gid] ?? null) : null;
            $totalBobot += (float) ($b['bobot'] ?? 0);
            $jmlBaris++;
            foreach ((array) ($b['minggu'] ?? []) as $i => $bm) {
                if ((float) $bm <= 0) {
                    continue;
                }
                $db->table('kurva_rencana')->insert([
                    'sekolah_id' => $this->sid, 'gedung_id' => $gid,
                    'grup' => $grup, 'bab' => (string) ($b['bab'] ?? ''),
                    'jumlah' => (float) ($b['jumlah'] ?? 0), 'bobot' => (float) ($b['bobot'] ?? 0),
                    'minggu_ke' => $i + 1, 'bobot_minggu' => (float) $bm,
                    'created_at' => $sekarang, 'updated_at' => $sekarang,
                ]);
            }
        }
        $db->transComplete();
        return [
            'minggu' => count($minggu), 'baris' => $jmlBaris,
            'pemetaan' => $pemetaan, 'total_bobot' => $totalBobot,
        ];
    }
}

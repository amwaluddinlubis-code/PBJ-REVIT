<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Utilitas bersama perbandingan analisa aplikasi vs Master Acuan AHSP
 * Nasional: pengelompokan kanonik nama komponen (dipakai untuk kedua sisi
 * agar nama perencana dan nama nasional bisa dipasangkan), normalisasi
 * satuan, dan konversi koefisien agregat antar satuan.
 */
class BandingAcuan
{
    /**
     * Baris acuan untuk satu kode analisa kita; null bila tidak ada /
     * tanpa padanan / tabel belum ada. Sejak acuan per sekolah (migrasi
     * 000029) pemanggil WAJIB meneruskan sekolah_id: yang dipakai adalah
     * set milik sekolah itu; bila kode itu belum ada di set sekolah,
     * templat nasional (sekolah_id NULL) menjadi cadangan agar
     * perbandingan tetap jalan sebelum set sekolah disiapkan.
     */
    public static function acuanUntukKode(BaseConnection $db, string $kode, ?int $sekolahId = null): ?array
    {
        if (! $db->tableExists('ahsp_acuan')) {
            return null;
        }
        $perSekolah = in_array('sekolah_id', $db->getFieldNames('ahsp_acuan'), true);
        $ac = null;
        if ($perSekolah && $sekolahId !== null) {
            $ac = $db->table('ahsp_acuan')->where('kode_kita', $kode)->where('sekolah_id', $sekolahId)
                ->where('is_deleted', 0)->get()->getRowArray();
        }
        if (! $ac) {
            $q = $db->table('ahsp_acuan')->where('kode_kita', $kode)->where('is_deleted', 0);
            if ($perSekolah) {
                $q->where('sekolah_id', null);
            }
            $ac = $q->get()->getRowArray();
        }
        return ($ac && ! $ac['tanpa_padanan']) ? $ac : null;
    }

    /** Komponen aktif satu item acuan. */
    public static function komponenAcuan(BaseConnection $db, int $acuanId): array
    {
        return $db->table('ahsp_acuan_komponen')->where('acuan_id', $acuanId)->where('is_deleted', 0)->get()->getResultArray();
    }

    /**
     * Total koefisien acuan untuk satu grup, dikonversi ke satuan komponen
     * kita. Null bila grup tidak ada di acuan atau satuannya tidak terkonversi.
     */
    public static function koefGrup(array $kompAcuan, string $grup, string $satuanKita): ?float
    {
        $total = 0.0;
        $ada = false;
        foreach ($kompAcuan as $k) {
            if (self::grupKanon((string) $k['nama'], (string) $k['jenis']) !== $grup) {
                continue;
            }
            $ada = true;
            $kv = self::konversiKoef($grup, (float) $k['koefisien'], (string) $k['satuan'], $satuanKita);
            if ($kv === null) {
                return null;
            }
            $total += $kv;
        }
        return $ada ? $total : null;
    }

    /** Grup kanonik komponen agar nama perencana dan nama nasional bisa dipasangkan. */
    public static function grupKanon(string $nama, string $jenis): string
    {
        $n = strtolower($nama);
        if ($jenis === 'upah') {
            if (str_contains($n, 'pekerja')) {
                return 'pekerja';
            }
            if (str_contains($n, 'kepala')) {
                return 'kepala';
            }
            if (str_contains($n, 'mandor')) {
                return 'mandor';
            }
            if (str_contains($n, 'tukang')) {
                return 'tukang';
            }
            return 'upah_lain';
        }
        if (str_contains($n, 'semen warna')) {
            return 'semen_warna';
        }
        if (str_contains($n, 'semen')) {
            return 'semen';
        }
        if (str_contains($n, 'pasir')) {
            return 'pasir';
        }
        if (str_contains($n, 'kerikil') || str_contains($n, 'split')) {
            return 'kerikil';
        }
        if (str_contains($n, 'batu')) {
            return 'batu';
        }
        if (str_contains($n, 'bata')) {
            return 'bata';
        }
        if (str_contains($n, 'besi beton') || str_contains($n, 'besi polos') || str_contains($n, 'besi ulir') || str_contains($n, 'baja tulangan')) {
            return 'besi';
        }
        if (str_contains($n, 'kawat beton') || str_contains($n, 'kawat tali') || str_contains($n, 'kawat bendrat')) {
            return 'kawat';
        }
        if (str_contains($n, 'kawat las')) {
            return 'kawat_las';
        }
        if (str_starts_with($n, 'lem')) {
            return 'lem';
        }
        if (str_contains($n, 'dolken')) {
            return 'dolken';
        }
        if (str_contains($n, 'plywood') || str_contains($n, 'triplek') || str_contains($n, 'multiplek')) {
            return 'plywood';
        }
        if (str_contains($n, 'minyak')) {
            return 'minyak';
        }
        if (str_contains($n, 'kayu') || str_contains($n, 'papan') || str_contains($n, 'balok') || str_contains($n, 'kaso')) {
            return 'kayu';
        }
        if (str_contains($n, 'fischer') || str_contains($n, 'fisher')) {
            return 'fischer';
        }
        if (str_contains($n, 'klem')) {
            return 'klem';
        }
        if (str_contains($n, 'tedoos') || str_contains($n, 'inbouwdoos') || str_contains($n, 'doos')) {
            return 'tdus';
        }
        if (str_contains($n, 'sochet')) {
            return 'socket';
        }
        if (str_contains($n, 'paku sekrup') || str_contains($n, 'sekrup')) {
            return 'paku_sekrup';
        }
        if (str_contains($n, 'paku')) {
            return 'paku';
        }
        if (trim($n) === 'air' || str_starts_with($n, 'air ')) {
            return 'air';
        }
        if (str_contains($n, 'meni')) {
            return 'meni';
        }
        if (str_contains($n, 'plamur') || str_contains($n, 'plamir')) {
            return 'plamur';
        }
        if (str_contains($n, 'cat dasar')) {
            return 'cat_dasar';
        }
        if (str_contains($n, 'cat')) {
            return 'cat_penutup';
        }
        if (str_contains($n, 'kuas') || str_contains($n, 'kwas')) {
            return 'kuas';
        }
        if (str_contains($n, 'pengencer') || str_contains($n, 'thinner') || str_contains($n, 'tiner')) {
            return 'pengencer';
        }
        if (str_contains($n, 'ampelas') || str_contains($n, 'amplas')) {
            return 'ampelas';
        }
        if (str_contains($n, 'keramik')) {
            return 'keramik';
        }
        if (str_contains($n, 'hollow')) {
            return 'hollow';
        }
        if (str_contains($n, 'baja ringan') && ! str_contains($n, 'reng')) {
            return 'baja_ringan';
        }
        if (str_contains($n, 'reng')) {
            return 'reng';
        }
        if (str_contains($n, 'rivet') || str_contains($n, 'keling')) {
            return 'rivet';
        }
        if (str_contains($n, 'nok')) {
            return 'nok';
        }
        if (str_contains($n, 'kaca')) {
            return 'kaca';
        }
        if (str_contains($n, 'sealant')) {
            return 'sealant';
        }
        if (str_contains($n, 'engsel')) {
            return 'engsel';
        }
        if (str_contains($n, 'grendel') || str_contains($n, 'sloot') || str_contains($n, 'slot')) {
            return 'grendel';
        }
        if (str_contains($n, 'kunci')) {
            return 'kunci';
        }
        if (str_contains($n, 'kait')) {
            return 'kait';
        }
        if (str_contains($n, 'handle') || str_contains($n, 'spring')) {
            return 'handle';
        }
        if (str_contains($n, 'saklar')) {
            return 'saklar';
        }
        if (str_contains($n, 'stop kontak')) {
            return 'stopkontak';
        }
        if (str_contains($n, 'socket') || str_contains($n, 'soket')) {
            return 'socket';
        }
        if (str_contains($n, 'kabel')) {
            return 'kabel';
        }
        if (str_contains($n, 'conduit') || str_contains($n, 'konduit') || str_contains($n, 'pipa')) {
            return 'conduit';
        }
        if (str_contains($n, 't dus') || str_contains($n, 'tdus') || str_contains($n, 'dus')) {
            return 'tdus';
        }
        if (str_contains($n, 'elbow') || str_contains($n, 'knie')) {
            return 'elbow';
        }
        if (str_contains($n, 'isolasi')) {
            return 'isolasi';
        }
        if (str_contains($n, 'lasdop')) {
            return 'lasdop';
        }
        if (str_contains($n, 'lisplang') || str_contains($n, 'lisplank')) {
            return 'lisplank';
        }
        if (str_contains($n, 'spandek') || str_contains($n, 'galval') || str_contains($n, 'atap metal') || str_contains($n, 'zincalum')) {
            return 'atap';
        }
        if (str_contains($n, 'waterpass')) {
            return 'waterpass';
        }
        if (str_contains($n, 'tanah')) {
            return 'tanah';
        }
        return 'lain_' . substr((string) preg_replace('/[^a-z0-9]+/', '_', $n), 0, 24);
    }

    public static function normSat(string $s): string
    {
        $s = str_replace(['³', '²', '¹'], ['3', '2', ''], strtolower(trim($s)));
        return ['bh' => 'buah', 'lbr' => 'lembar', 'btg' => 'batang', "m'" => 'm', 'org' => 'oh'][$s] ?? $s;
    }

    /** Konversi koefisien antar satuan (agregat lepas: pasir 1.400 kg/m3, kerikil 1.600 kg/m3 — selaras faktorSatuan bawaan). */
    public static function konversiKoef(string $grup, float $nilai, string $dari, string $ke): ?float
    {
        $dari = self::normSat($dari);
        $ke = self::normSat($ke);
        if ($dari === $ke) {
            return $nilai;
        }
        $faktor = [
            'pasir'   => ['m3>kg' => 1400.0, 'kg>m3' => 1 / 1400],
            'kerikil' => ['m3>kg' => 1600.0, 'kg>m3' => 1 / 1600],
        ];
        $f = $faktor[$grup][$dari . '>' . $ke] ?? null;
        return $f === null ? null : $nilai * $f;
    }
}

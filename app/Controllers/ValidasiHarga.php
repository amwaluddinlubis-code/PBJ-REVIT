<?php

namespace App\Controllers;

/**
 * Validasi Harga Satuan: membandingkan harga satuan pada tabel pekerjaan
 * untuk kode analisa yang sama, antar pekerjaan itu sendiri dan terhadap
 * kalkulasi analisa_komponen (harga AHSP hasil hitungan komponen).
 * Perbaikan menyamakan harga_satuan pekerjaan dalam satu kode — harga
 * berjalan yang berasal dari BA Revisi yang disahkan tidak disentuh.
 */
class ValidasiHarga extends BaseController
{
    /** Kumpulkan ringkasan harga per kode analisa untuk sekolah aktif. */
    private function kumpulkan(): array
    {
        $db = $this->db();
        $sid = $this->sid();
        $rows = $db->query(
            "SELECT p.harga_satuan, p.analisa_id, p.kode_analisa,
                    a.kode AS kode_a, a.nama AS nama_a, a.harga_satuan AS harga_a
             FROM pekerjaan p
             JOIN gedung g ON g.id = p.gedung_id AND g.is_deleted = 0 AND g.sekolah_id = ?
             LEFT JOIN analisa a ON a.id = p.analisa_id AND a.is_deleted = 0
             WHERE p.is_deleted = 0
               AND ((p.kode_analisa IS NOT NULL AND p.kode_analisa <> '') OR p.analisa_id IS NOT NULL)",
            [$sid]
        )->getResultArray();
        $jmlKomp = [];
        foreach ($db->query(
            "SELECT a.kode, COUNT(ak.id) AS n FROM analisa a
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.is_deleted = 0
             WHERE a.sekolah_id = ? AND a.is_deleted = 0 GROUP BY a.kode",
            [$sid]
        )->getResultArray() as $r) {
            $jmlKomp[$r['kode']] = (int) $r['n'];
        }
        $per = [];
        foreach ($rows as $r) {
            $kode = $r['kode_a'] ?? $r['kode_analisa'];
            if (! $kode) {
                continue;
            }
            if (! isset($per[$kode])) {
                $per[$kode] = [
                    'kode' => $kode, 'nama' => $r['nama_a'], 'harga_a' => $r['harga_a'] !== null ? (float) $r['harga_a'] : null,
                    'harga' => [], 'n' => 0, 'komponen' => $jmlKomp[$kode] ?? 0,
                    'kalkulasi' => $this->hargaAhspKode($kode, $sid),
                ];
            }
            $h = (float) $r['harga_satuan'];
            $per[$kode]['harga'][(string) $h] = ($per[$kode]['harga'][(string) $h] ?? 0) + 1;
            $per[$kode]['n']++;
        }
        ksort($per);
        $bedaAntar = [];
        $bedaKalkulasi = [];
        foreach ($per as $v) {
            if (count($v['harga']) > 1) {
                $bedaAntar[] = $v;
            }
            if ($v['komponen'] > 0 && $v['kalkulasi'] > 0) {
                foreach (array_keys($v['harga']) as $h) {
                    if (abs((float) $h - $v['kalkulasi']) > 1) {
                        $bedaKalkulasi[] = $v;
                        break;
                    }
                }
            }
        }
        return ['semua' => $per, 'bedaAntar' => $bedaAntar, 'bedaKalkulasi' => $bedaKalkulasi];
    }

    public function index()
    {
        $data = $this->kumpulkan();
        return $this->tampil('validasi_harga/index', $data, 'Validasi Harga Satuan — Aplikasi PBJ Sekolah');
    }

    /** Terapkan satu harga_satuan ke semua pekerjaan aktif berkode sama. */
    private function terapkanHarga(string $kode, float $harga): int
    {
        $db = $this->db();
        $sid = $this->sid();
        $aid = $this->analisaIdDariKode($kode, $sid);
        if ($aid !== null) {
            $db->query(
                "UPDATE pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 SET p.harga_satuan = ?
                 WHERE p.is_deleted = 0 AND g.is_deleted = 0 AND g.sekolah_id = ?
                   AND (p.analisa_id = ? OR (p.analisa_id IS NULL AND p.kode_analisa = ?))",
                [$harga, $sid, $aid, $kode]
            );
        } else {
            $db->query(
                "UPDATE pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 SET p.harga_satuan = ?
                 WHERE p.is_deleted = 0 AND g.is_deleted = 0 AND g.sekolah_id = ?
                   AND p.kode_analisa = ?",
                [$harga, $sid, $kode]
            );
        }
        return $db->affectedRows();
    }

    /** Samakan harga pekerjaan satu kode ke salah satu nilai yang sudah ada. */
    public function samakanPekerjaan()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $kode = trim((string) $this->request->getPost('kode'));
        $harga = (float) $this->request->getPost('harga');
        $data = $this->kumpulkan();
        if (! isset($data['semua'][$kode]) || ! isset($data['semua'][$kode]['harga'][(string) $harga])) {
            return redirect()->to('validasi-harga')->with('galat', 'Kode atau harga tidak ditemukan pada data pekerjaan.');
        }
        $n = $this->terapkanHarga($kode, $harga);
        $this->catatAudit('pekerjaan', null, 'samakan_harga_pekerjaan', $kode . ': harga satuan disamakan ke ' . $this->rp($harga) . ' pada ' . $n . ' pekerjaan');
        return redirect()->to('validasi-harga')->with('sukses', 'Harga satuan ' . $kode . ' disamakan ke ' . $this->rp($harga) . ' pada ' . $n . ' pekerjaan.');
    }

    /** Samakan harga pekerjaan satu kode ke kalkulasi analisa_komponen. */
    public function samakanKalkulasi()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $kode = trim((string) $this->request->getPost('kode'));
        $data = $this->kumpulkan();
        $v = $data['semua'][$kode] ?? null;
        if (! $v || $v['komponen'] === 0 || $v['kalkulasi'] <= 0) {
            return redirect()->to('validasi-harga')->with('galat', 'Kalkulasi komponen untuk kode itu tidak tersedia.');
        }
        $n = $this->terapkanHarga($kode, round($v['kalkulasi'], 2));
        $this->catatAudit('pekerjaan', null, 'samakan_harga_kalkulasi', $kode . ': harga satuan disamakan ke kalkulasi komponen ' . $this->rp($v['kalkulasi']) . ' pada ' . $n . ' pekerjaan');
        return redirect()->to('validasi-harga')->with('sukses', 'Harga satuan ' . $kode . ' disamakan ke kalkulasi komponen ' . $this->rp($v['kalkulasi']) . ' pada ' . $n . ' pekerjaan.');
    }

    /** Samakan semua kode yang berbeda dari kalkulasi ke kalkulasi komponennya. */
    public function samakanKalkulasiSemua()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $data = $this->kumpulkan();
        $db = $this->db();
        $db->transStart();
        $kodeN = 0;
        $baris = 0;
        foreach ($data['bedaKalkulasi'] as $v) {
            $baris += $this->terapkanHarga((string) $v['kode'], round($v['kalkulasi'], 2));
            $kodeN++;
        }
        $db->transComplete();
        $this->catatAudit('pekerjaan', null, 'samakan_harga_kalkulasi_semua', $kodeN . ' kode disamakan ke kalkulasi komponen (' . $baris . ' pekerjaan)');
        return redirect()->to('validasi-harga')->with('sukses', $kodeN . ' kode analisa disamakan ke kalkulasi komponennya (' . $baris . ' pekerjaan diperbarui).');
    }
}

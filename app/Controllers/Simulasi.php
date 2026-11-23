<?php

namespace App\Controllers;

/**
 * Simulasi Mingguan: mesin simulasi rencana belanja & kebutuhan pekerja
 * per minggu kalender mengikuti Kurva S resmi, yang dapat di-generate
 * ulang sesudah panitia memasukkan realisasi mingguan.
 *
 * Masukan: bobot realisasi per minggu per gedung (persen terhadap total
 * proyek, tabel simulasi_realisasi). Nilai realisasi satu minggu disebar
 * ke bab gedung itu mengikuti porsi bobot rencana minggu tersebut (bab
 * yang selesai lebih dulu jatahnya berhenti; sisa limpahan pindah ke bab
 * terjadwal berikutnya). Sisa volume tiap bab lalu dijadwalkan ulang ke
 * minggu-minggu berikutnya mengikuti pola fraksi Kurva S bab itu
 * (dinormalisasi ulang); bab yang jadwalnya sudah lewat tetapi belum
 * selesai dimasukkan seluruh sisanya ke minggu berikutnya (kejar
 * ketertinggalan). Kebutuhan bahan & upah dihitung mesin yang sama
 * dengan Rencana Belanja (hitungPilihan) — tidak ada jalur hitung kedua.
 */
class Simulasi extends Rencana
{
    /** Alias bab BOQ tanpa baris sendiri di Kurva S → bab induknya. */
    private const ALIAS_BAB = [
        'PEKERJAAN TANGGA BETON' => 'PEKERJAAN STRUKTUR',
        'PEKERJAAN ARSITEKTUR' => 'PEKERJAAN LAIN LAIN',
        'PEK RAMPA HAND RAILING DISABILITAS' => 'PEKERJAAN LAIN LAIN',
    ];

    /** Normalisasi nama bab — sama dengan RencanaMingguan::normalBab(). */
    private function normalBab(?string $bab): string
    {
        $s = strtoupper((string) $bab);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? '';
        $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
        $s = preg_replace('/^(XV|IV|V?I{0,3}|IX|X) /', '', $s) ?? $s;
        return trim($s);
    }

    private function babKunci(?string $bab): string
    {
        $n = $this->normalBab($bab);
        return self::ALIAS_BAB[$n] ?? $n;
    }

    public function index()
    {
        $db = $this->db();
        $sid = $this->sid();
        if (! $db->tableExists('kurva_rencana') || ! $db->tableExists('kurva_minggu')) {
            return $this->tampil('simulasi/index', ['belum' => 'migrasi'], 'Simulasi Mingguan');
        }
        $kalender = $db->table('kurva_minggu')->where(['sekolah_id' => $sid, 'is_deleted' => 0])
            ->orderBy('minggu_ke')->get()->getResultArray();
        $barisKurva = $db->table('kurva_rencana')->where(['sekolah_id' => $sid, 'is_deleted' => 0])
            ->get()->getResultArray();
        if ($kalender === [] || $barisKurva === []) {
            return $this->tampil('simulasi/index', ['belum' => 'kurva'], 'Simulasi Mingguan');
        }

        // Total proyek dokumen (sama seperti RencanaMingguan::targetKurva).
        $totalProyek = 0.0;
        foreach ($db->query(
            'SELECT MAX(jumlah) AS j FROM kurva_rencana WHERE sekolah_id = ? AND is_deleted = 0 GROUP BY grup, bab',
            [$sid]
        )->getResultArray() as $row) {
            $totalProyek += (float) $row['j'];
        }

        // Gedung yang punya baris kurva.
        $gedungIds = [];
        foreach ($barisKurva as $b) {
            if ($b['gedung_id'] !== null) {
                $gedungIds[(int) $b['gedung_id']] = true;
            }
        }
        $gedung = [];
        foreach ($db->table('gedung')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->orderBy('id')->get()->getResultArray() as $g) {
            if (isset($gedungIds[(int) $g['id']])) {
                $gedung[(int) $g['id']] = $g;
            }
        }

        // Pekerjaan gedung-gedung itu, dikelompokkan per (gedung, bab kunci).
        $pekerjaan = [];
        $perBab = [];
        if ($gedung !== []) {
            foreach ($db->query(
                'SELECT p.id, p.nama, p.bab, p.satuan, COALESCE(p.volume_revisi, p.volume) AS volume_berjalan, p.gedung_id
                 FROM pekerjaan p WHERE p.gedung_id IN (' . implode(',', array_map('intval', array_keys($gedung))) . ') AND p.is_deleted = 0',
            )->getResultArray() as $p) {
                $p['bab_kunci'] = $this->babKunci($p['bab'] ?? '');
                $pekerjaan[(int) $p['id']] = $p;
                $perBab[(int) $p['gedung_id'] . '|' . $p['bab_kunci']][] = (int) $p['id'];
            }
        }

        // Bab kurva per gedung: nilai penuh (jumlah) + bobot per minggu.
        // Bab tanpa pekerjaan tertaut (termasuk baris tanpa gedung dan
        // baris Perabot) menjadi baris biaya non-BOQ terjadwal.
        $babKurva = [];   // "gid|babKunci" => ['jumlah'=>, 'minggu'=>[ke=>bobot], 'bobot_total'=>]
        $nonBoq = [];     // [ke][] => ['label'=>, 'nilai'=>]
        foreach ($barisKurva as $b) {
            $ke = (int) $b['minggu_ke'];
            $gid = $b['gedung_id'] !== null ? (int) $b['gedung_id'] : 0;
            $kunci = $gid . '|' . $this->babKunci($b['bab']);
            if ($gid > 0 && isset($perBab[$kunci])) {
                $babKurva[$kunci]['jumlah'] = max($babKurva[$kunci]['jumlah'] ?? 0.0, (float) $b['jumlah']);
                $babKurva[$kunci]['minggu'][$ke] = ($babKurva[$kunci]['minggu'][$ke] ?? 0.0) + (float) $b['bobot_minggu'];
                $babKurva[$kunci]['label'] = (string) $b['bab'];
            } elseif ((float) $b['bobot_minggu'] > 0) {
                $nonBoq[$ke][] = [
                    'label' => trim((string) $b['grup'] . ' — ' . (string) $b['bab']),
                    'nilai' => (float) $b['bobot_minggu'] / 100 * $totalProyek,
                ];
            }
        }
        foreach ($babKurva as $k => $bk) {
            $babKurva[$k]['bobot_total'] = array_sum($bk['minggu']);
        }

        // Masukan realisasi tersimpan.
        $input = [];
        $c = 0;
        if ($db->tableExists('simulasi_realisasi')) {
            foreach ($db->table('simulasi_realisasi')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $r) {
                $input[(int) $r['minggu_ke']][(int) $r['gedung_id']] = (float) $r['bobot'];
                if ((float) $r['bobot'] > 0) {
                    $c = max($c, (int) $r['minggu_ke']);
                }
            }
        }
        $mingguTerakhir = (int) $kalender[count($kalender) - 1]['minggu_ke'];

        // Sebar realisasi per gedung ke bab + jadwalkan ulang sisanya.
        $jadwal = [];      // [ke][pekerjaan_id] => volume target
        $realisasiBab = []; // [ke][kunci] => nilai terealisasi minggu itu
        $ringkasGedung = [];
        foreach ($gedung as $gid => $g) {
            $sisaNilai = [];
            foreach ($babKurva as $kunci => $bk) {
                if (str_starts_with($kunci, $gid . '|')) {
                    $sisaNilai[$kunci] = $bk['jumlah'];
                }
            }
            $realisasiKum = 0.0;
            for ($w = 1; $w <= $c; $w++) {
                $nilai = ($input[$w][$gid] ?? 0.0) / 100 * $totalProyek;
                $realisasiKum += ($input[$w][$gid] ?? 0.0);
                $sisa = $nilai;
                $putaran = 0;
                while ($sisa > 0.5 && $putaran++ < 12) {
                    $kandidat = [];
                    foreach ($sisaNilai as $kunci => $sv) {
                        if ($sv > 0.5 && ($babKurva[$kunci]['minggu'][$w] ?? 0.0) > 0) {
                            $kandidat[$kunci] = (float) $babKurva[$kunci]['minggu'][$w];
                        }
                    }
                    if ($kandidat === []) {
                        foreach ($sisaNilai as $kunci => $sv) {
                            if ($sv > 0.5) {
                                $kandidat[$kunci] = $sv;
                            }
                        }
                    }
                    if ($kandidat === []) {
                        break;
                    }
                    $totalW = array_sum($kandidat);
                    $maju = false;
                    foreach ($kandidat as $kunci => $bobotW) {
                        $alokasi = min($sisaNilai[$kunci], $sisa * $bobotW / $totalW);
                        if ($alokasi > 0.005) {
                            $sisaNilai[$kunci] -= $alokasi;
                            $sisa -= $alokasi;
                            $realisasiBab[$w][$kunci] = ($realisasiBab[$w][$kunci] ?? 0.0) + $alokasi;
                            $maju = true;
                        }
                    }
                    if (! $maju) {
                        break;
                    }
                }
            }
            // Jadwal ulang sisa ke minggu > c mengikuti fraksi kurva bab.
            foreach ($sisaNilai as $kunci => $sv) {
                if ($sv <= 0.5) {
                    continue;
                }
                $bk = $babKurva[$kunci];
                $denom = 0.0;
                foreach ($bk['minggu'] as $ke => $bw) {
                    if ($ke > $c) {
                        $denom += $bw;
                    }
                }
                $fraksiSisa = $bk['jumlah'] > 0 ? $sv / $bk['jumlah'] : 0.0;
                $porsi = [];
                if ($denom > 0) {
                    foreach ($bk['minggu'] as $ke => $bw) {
                        if ($ke > $c && $bw > 0) {
                            $porsi[$ke] = $bw / $denom;
                        }
                    }
                } else {
                    $porsi[min($c + 1, $mingguTerakhir)] = 1.0;
                }
                foreach ($perBab[$kunci] ?? [] as $pid) {
                    foreach ($porsi as $ke => $fr) {
                        $v = (float) $pekerjaan[$pid]['volume_berjalan'] * $fraksiSisa * $fr;
                        if ($v > 0) {
                            $jadwal[$ke][$pid] = ($jadwal[$ke][$pid] ?? 0.0) + $v;
                        }
                    }
                }
            }
            // Volume terealisasi per pekerjaan untuk minggu-minggu lewat.
            for ($w = 1; $w <= $c; $w++) {
                foreach ($realisasiBab[$w] ?? [] as $kunci => $nilaiW) {
                    if (! str_starts_with($kunci, $gid . '|')) {
                        continue;
                    }
                    $jumlah = $babKurva[$kunci]['jumlah'];
                    if ($jumlah <= 0) {
                        continue;
                    }
                    foreach ($perBab[$kunci] ?? [] as $pid) {
                        $v = (float) $pekerjaan[$pid]['volume_berjalan'] * $nilaiW / $jumlah;
                        if ($v > 0) {
                            $jadwal[$w][$pid] = ($jadwal[$w][$pid] ?? 0.0) + $v;
                        }
                    }
                }
            }
            // Ringkasan gedung: bobot total, rencana kumulatif s.d. c, realisasi.
            $perGrupBab = [];
            $rencanaKum = 0.0;
            foreach ($barisKurva as $b) {
                if ($b['gedung_id'] !== null && (int) $b['gedung_id'] === $gid) {
                    $gk = (string) $b['grup'] . '|' . (string) $b['bab'];
                    $perGrupBab[$gk] = max($perGrupBab[$gk] ?? 0.0, (float) $b['bobot']);
                    if ((int) $b['minggu_ke'] <= $c) {
                        $rencanaKum += (float) $b['bobot_minggu'];
                    }
                }
            }
            $bobotGedung = array_sum($perGrupBab);
            $ringkasGedung[$gid] = [
                'nama' => (string) $g['nama'],
                'bobot_total' => $bobotGedung,
                'rencana_kum' => $rencanaKum,
                'realisasi_kum' => $realisasiKum,
            ];
        }

        // Hitung kebutuhan per minggu kalender.
        $hasil = [];
        foreach ($kalender as $km) {
            $ke = (int) $km['minggu_ke'];
            $vol = $jadwal[$ke] ?? [];
            $aktif = [];
            foreach ($vol as $pid => $v) {
                $p = $pekerjaan[$pid];
                $aktif[] = [
                    'gedung_id' => (int) $p['gedung_id'],
                    'bab' => $p['bab_kunci'],
                    'nama' => (string) $p['nama'],
                    'volume' => $v,
                    'satuan' => (string) ($p['satuan'] ?? ''),
                ];
            }
            usort($aktif, static fn ($a, $b) => [$a['gedung_id'], $a['bab'], $a['nama']] <=> [$b['gedung_id'], $b['bab'], $b['nama']]);
            $bahan = [];
            $upah = [];
            if ($vol !== []) {
                $h = $this->hitungPilihan($vol, $pekerjaan, 6);
                foreach ($h['kebutuhan']['tertaut'] as $x) {
                    $bahan[] = [
                        'kode' => (string) $x['kode'], 'nama' => (string) $x['nama'], 'satuan' => (string) $x['satuan'],
                        'qty' => (float) $x['qty_butuh'], 'harga' => (float) $x['harga'],
                        'nilai' => (float) $x['qty_butuh'] * (float) $x['harga'],
                    ];
                }
                usort($bahan, static fn ($a, $b) => $b['nilai'] <=> $a['nilai']);
                $upah = $h['upah'];
            }
            $bobotRencana = 0.0;
            foreach ($barisKurva as $b) {
                if ((int) $b['minggu_ke'] === $ke) {
                    $bobotRencana += (float) $b['bobot_minggu'];
                }
            }
            $hasil[] = [
                'ke' => $ke,
                'mulai' => (string) $km['tanggal_mulai'],
                'selesai' => (string) $km['tanggal_selesai'],
                'lewat' => $ke <= $c,
                'bobot_rencana' => $bobotRencana,
                'aktif' => $aktif,
                'bahan' => $bahan,
                'upah' => $upah,
                'non_boq' => $nonBoq[$ke] ?? [],
            ];
        }

        return $this->tampil('simulasi/index', [
            'belum' => null,
            'kalender' => $kalender,
            'gedung' => $gedung,
            'input' => $input,
            'berjalan' => $c,
            'total_proyek' => $totalProyek,
            'ringkas' => $ringkasGedung,
            'hasil' => $hasil,
        ], 'Simulasi Mingguan');
    }

    public function simpan()
    {
        $db = $this->db();
        $sid = $this->sid();
        if (! $db->tableExists('simulasi_realisasi') || ! $db->tableExists('kurva_minggu')) {
            return redirect()->to('simulasi')->with('galat', 'Tabel simulasi/kurva belum tersedia — jalankan migrasi lebih dulu.');
        }
        $kalender = $db->table('kurva_minggu')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray();
        $gedungIds = [];
        foreach ($db->table('kurva_rencana')->select('gedung_id')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->distinct()->get()->getResultArray() as $r) {
            if ($r['gedung_id'] !== null) {
                $gedungIds[] = (int) $r['gedung_id'];
            }
        }
        $masukan = (array) $this->request->getPost('realisasi');
        $tersimpan = 0;
        $db->transStart();
        foreach ($kalender as $km) {
            $ke = (int) $km['minggu_ke'];
            foreach ($gedungIds as $gid) {
                $mentah = $masukan[$ke][$gid] ?? null;
                $nilai = $mentah === null || trim((string) $mentah) === ''
                    ? 0.0
                    : max(0.0, (float) str_replace(',', '.', (string) $mentah));
                $ada = $db->table('simulasi_realisasi')
                    ->where(['sekolah_id' => $sid, 'minggu_ke' => $ke, 'gedung_id' => $gid])->get()->getRowArray();
                if ($nilai > 0) {
                    if ($ada) {
                        $db->table('simulasi_realisasi')->where('id', (int) $ada['id'])
                            ->update(['bobot' => $nilai, 'is_deleted' => 0, 'deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
                    } else {
                        $db->table('simulasi_realisasi')
                            ->insert(['sekolah_id' => $sid, 'minggu_ke' => $ke, 'gedung_id' => $gid, 'bobot' => $nilai, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                    }
                    $tersimpan++;
                } elseif ($ada && (int) $ada['is_deleted'] === 0) {
                    $this->hapusLunak('simulasi_realisasi', ['id' => (int) $ada['id']]);
                }
            }
        }
        $db->transComplete();
        $this->catatAudit('simulasi_realisasi', null, 'simpan_realisasi', $tersimpan . ' sel realisasi mingguan tersimpan; simulasi di-generate ulang.');
        return redirect()->to('simulasi')->with('sukses', 'Realisasi mingguan tersimpan (' . $tersimpan . ' sel). Simulasi di bawah sudah dihitung ulang dari masukan terbaru.');
    }
}

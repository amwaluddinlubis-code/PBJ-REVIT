<?php

namespace App\Controllers;

class Gedung extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $gedung = $db->query(
            'SELECT g.*, (SELECT COUNT(*) FROM pekerjaan p WHERE p.gedung_id = g.id AND p.is_deleted = 0) AS jml_pekerjaan
             FROM gedung g WHERE g.sekolah_id = ? AND g.is_deleted = 0 ORDER BY g.urutan, g.id',
            [$this->sid()]
        )->getResultArray();
        foreach ($gedung as &$g) {
            $real = $db->query(
                'SELECT COALESCE(SUM(r.volume * p.harga_satuan), 0) t FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0',
                [$g['id']]
            )->getRowArray();
            $g['nilai_realisasi'] = (float) ($real['t'] ?? 0);
            $g['persen_fisik'] = (float) $g['nilai_boq'] > 0 ? min(100, $g['nilai_realisasi'] / (float) $g['nilai_boq'] * 100) : 0;
        }
        return $this->tampil('gedung/index', ['gedung' => $gedung], 'Kelola Gedung');
    }

    public function simpan()
    {
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') {
            return redirect()->to('/gedung')->with('galat', 'Nama gedung wajib diisi.');
        }
        $urut = (int) ($this->db()->query('SELECT COALESCE(MAX(urutan),0) m FROM gedung WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow()->m ?? 0) + 1;
        $this->db()->table('gedung')->insert([
            'sekolah_id' => $this->sid(), 'nama' => $nama,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'urutan' => $urut, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/gedung')->with('sukses', 'Gedung "' . $nama . '" ditambahkan.');
    }

    public function perbarui($id)
    {
        $g = $this->ambilGedung((int) $id);
        if ($g === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $this->db()->table('gedung')->where('id', (int) $id)->update([
            'nama' => trim((string) $this->request->getPost('nama')) ?: $g['nama'],
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/gedung/' . (int) $id)->with('sukses', 'Gedung diperbarui.');
    }

    public function hapus($id)
    {
        $g = $this->ambilGedung((int) $id);
        if ($g === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $db = $this->db();
        // Hapus lunak bertingkat: realisasi + BOQ + gedungnya ditandai bersama.
        $this->hapusLunak('realisasi', static fn ($b) => $b->whereIn('pekerjaan_id', $db->table('pekerjaan')->select('id')->where('gedung_id', (int) $id)));
        $this->hapusLunak('pekerjaan', ['gedung_id' => (int) $id]);
        $this->hapusLunak('gedung', ['id' => (int) $id]);
        return redirect()->to('/gedung')->with('sukses', 'Gedung "' . $g['nama'] . '" dihapus beserta BOQ-nya.');
    }

    /** Inti penghapusan satu gedung (bertingkat) untuk dipakai massal. */
    private function hapusSatuGedung(int $id): bool
    {
        if ($this->ambilGedung($id) === null) {
            return false;
        }
        $db = $this->db();
        $this->hapusLunak('realisasi', static fn ($b) => $b->whereIn('pekerjaan_id', $db->table('pekerjaan')->select('id')->where('gedung_id', $id)));
        $this->hapusLunak('pekerjaan', ['gedung_id' => $id]);
        $this->hapusLunak('gedung', ['id' => $id]);
        return true;
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            if ($this->hapusSatuGedung($id)) {
                $n++;
            }
        }
        return redirect()->to('/gedung')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' gedung terpilih dihapus beserta BOQ dan realisasinya.' : 'Tidak ada gedung yang diproses.');
    }

    public function kosongkan()
    {
        $ids = array_column($this->db()->table('gedung')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        $n = 0;
        foreach ($ids as $id) {
            if ($this->hapusSatuGedung((int) $id)) {
                $n++;
            }
        }
        return redirect()->to('/gedung')->with('sukses', 'Daftar gedung dikosongkan: ' . $n . ' gedung dihapus beserta BOQ dan realisasinya.');
    }

    public function detail($id, $tab = 'boq')
    {
        $g = $this->ambilGedung((int) $id);
        if ($g === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $db = $this->db();
        $data = ['g' => $g, 'tab' => $tab];
        $real = $db->query(
            'SELECT COALESCE(SUM(r.volume * p.harga_satuan), 0) t FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0',
            [$g['id']]
        )->getRowArray();
        $data['nilai_realisasi'] = (float) ($real['t'] ?? 0);
        $data['persen_fisik'] = (float) $g['nilai_boq'] > 0 ? min(100, $data['nilai_realisasi'] / (float) $g['nilai_boq'] * 100) : 0;

        if ($tab === 'boq') {
            $data['pekerjaan'] = $db->table('pekerjaan')->where(['gedung_id' => $g['id'], 'is_deleted' => 0])->orderBy('urutan')->get()->getResultArray();
            // Peta analisa untuk rincian klik pekerjaan: komponen bahan/upah/alat
            // per kode analisa, lengkap dengan faktor konversi satuan bahan.
            $data['petaAnalisa'] = [];
            $kodeUnik = array_values(array_unique(array_filter(array_column($data['pekerjaan'], 'kode_analisa'))));
            if ($kodeUnik !== []) {
                $peta = [];
                foreach ($db->table('analisa')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->whereIn('kode', $kodeUnik)->get()->getResultArray() as $a) {
                    $peta[$a['kode']] = $a + ['komponen' => []];
                }
                if ($peta !== []) {
                    $jenisUpahHapus = array_column($db->table('upah_master')->select('jenis')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 1])->get()->getResultArray(), 'jenis');
                    $komp = $db->query(
                        "SELECT ak.*, a.kode AS kode_analisa, b.nama AS nama_master, b.kode AS kode_master, b.satuan AS satuan_master, b.is_deleted AS master_terhapus
                         FROM analisa_komponen ak
                         JOIN analisa a ON a.id = ak.analisa_id
                         LEFT JOIN bahan b ON b.id = ak.bahan_id
                         WHERE a.sekolah_id = ? AND ak.is_deleted = 0 AND a.kode IN (" . implode(',', array_map(static fn ($k) => $db->escape($k), array_keys($peta))) . ")
                         ORDER BY FIELD(ak.jenis, 'bahan', 'upah', 'alat'), ak.id",
                        [$this->sid()]
                    )->getResultArray();
                    foreach ($komp as $k) {
                        // Komponen yang masternya sudah dihapus tidak ditampilkan lagi.
                        $masterHapus = ($k['jenis'] === 'bahan' && $k['bahan_id'] !== null && (int) ($k['master_terhapus'] ?? 0) === 1)
                            || ($k['jenis'] === 'upah' && in_array($k['nama'], $jenisUpahHapus, true));
                        if ($masterHapus) {
                            $peta[$k['kode_analisa']]['tersembunyi'] = ($peta[$k['kode_analisa']]['tersembunyi'] ?? 0) + 1;
                            continue;
                        }
                        if ($k['jenis'] === 'bahan' && $k['bahan_id'] !== null) {
                            $k['faktor'] = $this->faktorSatuan($k['satuan'] ?? null, ['satuan' => $k['satuan_master'] ?? '', 'nama' => $k['nama_master'] ?? $k['nama']]);
                        } else {
                            $k['faktor'] = 1.0;
                        }
                        $peta[$k['kode_analisa']]['komponen'][] = $k;
                    }
                }
                foreach ($peta as $kodeA => $isiA) {
                    if ($isiA['komponen'] === []) {
                        unset($peta[$kodeA]);
                    }
                }
                $data['petaAnalisa'] = $peta;
            }
        } elseif ($tab === 'bahan') {
            $kb = $this->kebutuhanBahan((int) $g['id']);
            $dipesan = [];
            foreach ($db->query(
                'SELECT d.bahan_id, SUM(d.qty) q FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0 WHERE s.gedung_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0 GROUP BY d.bahan_id',
                [$g['id']]
            )->getResultArray() as $r) {
                $dipesan[(int) $r['bahan_id']] = (float) $r['q'];
            }
            foreach ($kb['tertaut'] as &$b) {
                $b['harga'] = $this->hargaAcuan((int) $b['bahan_id']);
                $b['dipesan'] = $dipesan[(int) $b['bahan_id']] ?? 0;
            }
            $data['kebutuhan'] = $kb;
            $data['semuaBahan'] = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        } elseif ($tab === 'upah') {
            $data['kebutuhan_upah'] = $this->kebutuhanUpah((int) $g['id']);
            $dibayar = [];
            foreach ($db->table('upah_bayar')->select('jenis_tenaga, SUM(orang_hari) oh, SUM(jumlah) jml')
                         ->where(['sekolah_id' => $this->sid(), 'gedung_id' => $g['id'], 'is_deleted' => 0])->groupBy('jenis_tenaga')->get()->getResultArray() as $r) {
                $dibayar[$r['jenis_tenaga']] = $r;
            }
            $data['upah_dibayar'] = $dibayar;
        } elseif ($tab === 'realisasi') {
            $data['pekerjaan'] = $db->query(
                'SELECT p.*, COALESCE(SUM(r.volume), 0) AS volume_terealisasi
                 FROM pekerjaan p LEFT JOIN realisasi r ON r.pekerjaan_id = p.id AND r.is_deleted = 0
                 WHERE p.gedung_id = ? AND p.is_deleted = 0 GROUP BY p.id ORDER BY p.urutan',
                [$g['id']]
            )->getResultArray();
            $data['riwayat'] = $db->query(
                'SELECT r.*, p.nama AS nama_pekerjaan FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id
                 WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0 ORDER BY r.tanggal DESC, r.id DESC LIMIT 30',
                [$g['id']]
            )->getResultArray();
        }
        return $this->tampil('gedung/detail', $data, 'Gedung: ' . $g['nama']);
    }

    /** Ubah volume satu pekerjaan; jumlah & nilai BOQ gedung dihitung ulang. */
    public function volumePekerjaan($pekerjaanId)
    {
        $db = $this->db();
        $p = $db->table('pekerjaan')->where(['id' => (int) $pekerjaanId, 'is_deleted' => 0])->get()->getRowArray();
        if ($p === null || $this->ambilGedung((int) $p['gedung_id']) === null) {
            return redirect()->to('/gedung')->with('galat', 'Pekerjaan tidak ditemukan.');
        }
        $volume = (float) str_replace(',', '.', (string) $this->request->getPost('volume'));
        $db->table('pekerjaan')->where('id', (int) $pekerjaanId)->update([
            'volume' => $volume, 'jumlah' => $volume * (float) $p['harga_satuan'],
        ]);
        $this->segarkanNilaiBoq((int) $p['gedung_id']);
        return redirect()->to('/gedung/' . (int) $p['gedung_id'] . '/boq')->with('sukses', 'Volume diperbarui; kebutuhan bahan & upah terhitung ulang.');
    }

    public function simpanPekerjaan()
    {
        $gid = (int) $this->request->getPost('gedung_id');
        if ($this->ambilGedung($gid) === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $volume = (float) str_replace(',', '.', (string) $this->request->getPost('volume'));
        $harga = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('harga_satuan'));
        $urut = (int) ($this->db()->query('SELECT COALESCE(MAX(urutan),0) m FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0', [$gid])->getRow()->m ?? 0) + 1;
        $this->db()->table('pekerjaan')->insert([
            'gedung_id' => $gid, 'bab' => trim((string) $this->request->getPost('bab')) ?: null,
            'nama' => trim((string) $this->request->getPost('nama')),
            'kode_analisa' => trim((string) $this->request->getPost('kode_analisa')) ?: null,
            'volume' => $volume, 'satuan' => trim((string) $this->request->getPost('satuan')) ?: null,
            'harga_satuan' => $harga, 'jumlah' => $volume * $harga, 'urutan' => $urut,
        ]);
        $this->segarkanNilaiBoq($gid);
        return redirect()->to('/gedung/' . $gid . '/boq')->with('sukses', 'Pekerjaan ditambahkan.');
    }

    public function hapusPekerjaan($pekerjaanId)
    {
        $db = $this->db();
        $p = $db->table('pekerjaan')->where(['id' => (int) $pekerjaanId, 'is_deleted' => 0])->get()->getRowArray();
        if ($p === null || $this->ambilGedung((int) $p['gedung_id']) === null) {
            return redirect()->to('/gedung')->with('galat', 'Pekerjaan tidak ditemukan.');
        }
        $this->hapusLunak('realisasi', ['pekerjaan_id' => (int) $pekerjaanId]);
        $this->hapusLunak('pekerjaan', ['id' => (int) $pekerjaanId]);
        $this->segarkanNilaiBoq((int) $p['gedung_id']);
        return redirect()->to('/gedung/' . (int) $p['gedung_id'] . '/boq')->with('sukses', 'Pekerjaan dihapus.');
    }

    /** Hapus massal pekerjaan tercentang pada satu gedung. */
    public function hapusPekerjaanMassal()
    {
        $gid = (int) $this->request->getPost('gedung_id');
        if ($this->ambilGedung($gid) === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $n = 0;
        foreach ($this->idsTerpilih() as $pid) {
            $p = $this->db()->table('pekerjaan')->where(['id' => $pid, 'gedung_id' => $gid, 'is_deleted' => 0])->get()->getRowArray();
            if ($p === null) {
                continue;
            }
            $this->hapusLunak('realisasi', ['pekerjaan_id' => $pid]);
            $this->hapusLunak('pekerjaan', ['id' => $pid]);
            $n++;
        }
        $this->segarkanNilaiBoq($gid);
        return redirect()->to('/gedung/' . $gid . '/boq')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' pekerjaan terpilih dihapus; nilai BOQ dihitung ulang.' : 'Tidak ada pekerjaan yang diproses.');
    }

    /** Kosongkan BOQ satu gedung (semua pekerjaan + realisasinya). */
    public function kosongkanPekerjaan($gedungId)
    {
        $gid = (int) $gedungId;
        if ($this->ambilGedung($gid) === null) {
            return redirect()->to('/gedung')->with('galat', 'Gedung tidak ditemukan.');
        }
        $ids = array_column($this->db()->table('pekerjaan')->select('id')
            ->where(['gedung_id' => $gid, 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $pid) {
            $this->hapusLunak('realisasi', ['pekerjaan_id' => (int) $pid]);
            $this->hapusLunak('pekerjaan', ['id' => (int) $pid]);
        }
        $this->segarkanNilaiBoq($gid);
        return redirect()->to('/gedung/' . $gid . '/boq')->with('sukses', 'BOQ dikosongkan: ' . count($ids) . ' pekerjaan dihapus; nilai BOQ kini nol.');
    }

    /** Tautkan semua komponen bahan bernama sama (di sekolah ini) ke satu bahan master. */
    public function tautkan()
    {
        $nama = (string) $this->request->getPost('nama_komponen');
        $bahanId = (int) $this->request->getPost('bahan_id');
        $gid = (int) $this->request->getPost('gedung_id');
        $b = $this->db()->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($b === null || $nama === '') {
            return redirect()->to('/gedung/' . $gid . '/bahan')->with('galat', 'Pilih bahan master yang sesuai.');
        }
        $this->db()->query(
            'UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             SET ak.bahan_id = ? WHERE a.sekolah_id = ? AND ak.jenis = \'bahan\' AND ak.nama = ? AND ak.is_deleted = 0',
            [$bahanId, $this->sid(), $nama]
        );
        return redirect()->to('/gedung/' . $gid . '/bahan')->with('sukses', 'Komponen "' . $nama . '" tertaut ke ' . $b['kode'] . ' — ' . $b['nama'] . '.');
    }

    /** Catat kemajuan volume satu pekerjaan (tab Realisasi). */
    public function simpanRealisasi($pekerjaanId)
    {
        $db = $this->db();
        $p = $db->table('pekerjaan')->where(['id' => (int) $pekerjaanId, 'is_deleted' => 0])->get()->getRowArray();
        if ($p === null || $this->ambilGedung((int) $p['gedung_id']) === null) {
            return redirect()->to('/gedung')->with('galat', 'Pekerjaan tidak ditemukan.');
        }
        $volume = (float) str_replace(',', '.', (string) $this->request->getPost('volume'));
        if ($volume <= 0) {
            return redirect()->to('/gedung/' . (int) $p['gedung_id'] . '/realisasi')->with('galat', 'Volume realisasi harus lebih dari 0.');
        }
        $db->table('realisasi')->insert([
            'pekerjaan_id' => (int) $pekerjaanId,
            'tanggal' => (string) ($this->request->getPost('tanggal') ?: date('Y-m-d')),
            'volume' => $volume,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/gedung/' . (int) $p['gedung_id'] . '/realisasi')->with('sukses', 'Kemajuan "' . $p['nama'] . '" dicatat.');
    }

    public function hapusRealisasi($realisasiId)
    {
        $db = $this->db();
        $r = $db->table('realisasi')->where(['id' => (int) $realisasiId, 'is_deleted' => 0])->get()->getRowArray();
        if ($r === null) {
            return redirect()->to('/gedung')->with('galat', 'Catatan realisasi tidak ditemukan.');
        }
        $p = $db->table('pekerjaan')->where('id', (int) $r['pekerjaan_id'])->get()->getRowArray();
        $this->hapusLunak('realisasi', ['id' => (int) $realisasiId]);
        return redirect()->to('/gedung/' . (int) ($p['gedung_id'] ?? 0) . '/realisasi')->with('sukses', 'Catatan realisasi dihapus.');
    }

    private function segarkanNilaiBoq(int $gedungId): void
    {
        $row = $this->db()->query('SELECT COALESCE(SUM(jumlah),0) t FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0', [$gedungId])->getRowArray();
        $this->db()->table('gedung')->where('id', $gedungId)->update([
            'nilai_boq' => (float) ($row['t'] ?? 0), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

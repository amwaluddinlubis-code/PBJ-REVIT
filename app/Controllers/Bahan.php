<?php

namespace App\Controllers;

class Bahan extends BaseController
{
    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $sql = 'SELECT b.*, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_total,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet)*b.harga_rab AS anggaran,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id AND d.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0';
        $bind = [$this->sid()];
        if ($q !== '') {
            $sql .= ' AND (b.nama LIKE ? OR b.kode LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        $sql .= ' GROUP BY b.id ORDER BY b.kode';
        $bahan = $this->db()->query($sql, $bind)->getResultArray();
        $upah = $this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('jenis')->get()->getResultArray();
        return $this->tampil('bahan/index', [
            'bahan' => $bahan, 'q' => $q, 'upah' => $upah,
            'duplikatBahan' => $this->grupDuplikat($bahan),
            'duplikatUpah' => $this->grupDuplikat($upah, 'jenis'),
        ], 'Master Bahan & Upah — Aplikasi PBJ Sekolah');
    }

    /** Bentuk normal nama untuk pencocokan duplikat (huruf kecil, spasi tunggal). */
    public static function normalNama(string $s): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($s)));
    }

    /**
     * Kelompok baris dengan nama normal yang sama tetapi satuan/harga
     * (atau tarif) berbeda — inilah duplikat yang harus digabung agar
     * satu bahan/upah hanya punya satu satuan dan satu harga acuan.
     */
    private function grupDuplikat(array $rows, string $kolomNama = 'nama'): array
    {
        $kelompok = [];
        foreach ($rows as $r) {
            $kelompok[self::normalNama((string) $r[$kolomNama])][] = $r;
        }
        $hasil = [];
        foreach ($kelompok as $nama => $anggota) {
            if (count($anggota) < 2) {
                continue;
            }
            $hasil[] = ['nama' => $anggota[0][$kolomNama], 'anggota' => $anggota];
        }
        return $hasil;
    }

    /** Id bahan lain dengan nama normal yang sama (untuk cegah duplikat). */
    private function namaBahanDipakai(string $nama, int $kecualiId = 0): ?array
    {
        foreach ($this->db()->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray() as $b) {
            if ((int) $b['id'] !== $kecualiId && self::normalNama($b['nama']) === self::normalNama($nama)) {
                return $b;
            }
        }
        return null;
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)) m FROM bahan WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow();
        $kode = 'B' . str_pad((string) (((int) $row->m) + 1), 3, '0', STR_PAD_LEFT);
        return $this->tampil('bahan/form', ['b' => ['kode' => $kode], 'aksi' => 'bahan/simpan'], 'Tambah Bahan');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->db()->table('bahan')->where(['kode' => $data['kode'], 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('galat', 'Kode ' . $data['kode'] . ' sudah dipakai.');
        }
        if ($sama = $this->namaBahanDipakai($data['nama'])) {
            return redirect()->back()->withInput()->with('galat', 'Bahan "' . $data['nama'] . '" sudah ada di master sebagai ' . $sama['kode'] . ' (' . $sama['satuan'] . ', ' . $this->rp($sama['harga_rab']) . '). Satu bahan hanya boleh punya satu satuan dan satu harga — ubah bahan yang sudah ada, atau gabungkan bila terlanjur ganda.');
        }
        $data['sekolah_id'] = $this->sid();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->insert($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' ditambahkan.');
    }

    public function ubah($id)
    {
        $b = $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $b) {
            return redirect()->to('bahan')->with('galat', 'Bahan tidak ditemukan.');
        }
        return $this->tampil('bahan/form', ['b' => $b, 'aksi' => 'bahan/perbarui/' . $id], 'Ubah Bahan ' . $b['kode']);
    }

    public function perbarui($id)
    {
        $data = $this->ambilForm();
        if ($sama = $this->namaBahanDipakai($data['nama'], (int) $id)) {
            return redirect()->back()->withInput()->with('galat', 'Nama itu sudah dipakai bahan ' . $sama['kode'] . '. Satu bahan hanya boleh punya satu satuan dan satu harga — gunakan fitur Gabungkan Duplikat di halaman Master Bahan.');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->update($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' diperbarui.');
    }

    public function hapus($id)
    {
        $dipakai = $this->db()->table('spk_detail')->where(['bahan_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('bahan')->with('galat', 'Bahan ini sudah dipakai di SPK Detail — tidak bisa dihapus.');
        }
        $this->hapusLunak('bahan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('bahan')->with('sukses', 'Bahan dihapus.');
    }

    /**
     * Gabungkan duplikat bahan menjadi satu master: semua referensi
     * (SPK detail, komponen analisa, survei harga, negosiasi, buku
     * material) dialihkan ke bahan utama, kuantitas RAB dijumlahkan,
     * lalu baris duplikat dihapus lunak. Sesudah ini bahan tersebut
     * hanya punya satu satuan dan satu harga acuan.
     */
    public function gabung()
    {
        $utamaId = (int) $this->request->getPost('utama');
        $anggota = array_map('intval', (array) $this->request->getPost('anggota'));
        $utama = $this->db()->table('bahan')->where(['id' => $utamaId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $utama || ! in_array($utamaId, $anggota, true) || count($anggota) < 2) {
            return redirect()->to('bahan')->with('galat', 'Pilih bahan utama dari kelompok duplikatnya.');
        }
        $db = $this->db();
        $db->transStart();
        // Perbaikan dulu, baru gabung: satuan & harga final yang diisi
        // pengguna pada formulir alert menjadi satuan/harga master.
        $satuanFinal = trim((string) $this->request->getPost('satuan_final'));
        $hargaFinal = $this->request->getPost('harga_final');
        $perbaikan = ['updated_at' => date('Y-m-d H:i:s')];
        if ($satuanFinal !== '') {
            $perbaikan['satuan'] = $satuanFinal;
        }
        if ($hargaFinal !== null && $hargaFinal !== '') {
            $perbaikan['harga_rab'] = (int) $hargaFinal;
        }
        if (count($perbaikan) > 1) {
            $db->table('bahan')->where('id', $utamaId)->update($perbaikan);
        }
        foreach ($anggota as $aid) {
            if ($aid === $utamaId) {
                continue;
            }
            $lain = $db->table('bahan')->where(['id' => $aid, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if (! $lain) {
                continue;
            }
            foreach (['spk_detail', 'analisa_komponen', 'survei_harga', 'negosiasi', 'buku_material'] as $tabel) {
                $db->table($tabel)->where('bahan_id', $aid)->update(['bahan_id' => $utamaId]);
            }
            $db->table('bahan')->where('id', $utamaId)->update([
                'qty_rkb' => (float) $utama['qty_rkb'] + (float) $lain['qty_rkb'],
                'qty_adm' => (float) $utama['qty_adm'] + (float) $lain['qty_adm'],
                'qty_toilet' => (float) $utama['qty_toilet'] + (float) $lain['qty_toilet'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $utama['qty_rkb'] = (float) $utama['qty_rkb'] + (float) $lain['qty_rkb'];
            $utama['qty_adm'] = (float) $utama['qty_adm'] + (float) $lain['qty_adm'];
            $utama['qty_toilet'] = (float) $utama['qty_toilet'] + (float) $lain['qty_toilet'];
            $this->hapusLunak('bahan', ['id' => $aid, 'sekolah_id' => $this->sid()]);
        }
        $db->transComplete();
        return redirect()->to('bahan')->with('sukses', 'Duplikat "' . $utama['nama'] . '" digabung ke ' . $utama['kode'] . ' — semua referensi dialihkan dan kuantitas RAB dijumlahkan.');
    }

    // ---------- Master Upah (referensi bersama modul Upah & Analisa) ----------

    public function upahSimpan()
    {
        $jenis = trim((string) $this->request->getPost('jenis'));
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        if ($jenis === '') {
            return redirect()->to('bahan')->with('galat', 'Jenis tenaga wajib diisi.');
        }
        foreach ($this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray() as $u) {
            if (self::normalNama($u['jenis']) === self::normalNama($jenis)) {
                return redirect()->to('bahan')->with('galat', 'Jenis tenaga "' . $jenis . '" sudah ada di Master Upah dengan tarif ' . $this->rp($u['tarif']) . '. Satu jenis tenaga hanya boleh punya satu tarif acuan.');
            }
        }
        $this->db()->table('upah_master')->insert([
            'sekolah_id' => $this->sid(), 'jenis' => $jenis,
            'satuan' => trim((string) $this->request->getPost('satuan')) ?: 'OH', 'tarif' => $tarif,
        ]);
        return redirect()->to('bahan')->with('sukses', 'Jenis tenaga "' . $jenis . '" ditambahkan ke Master Upah.');
    }

    public function upahPerbarui($id)
    {
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        $this->db()->table('upah_master')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update(['tarif' => $tarif]);
        return redirect()->to('bahan')->with('sukses', 'Tarif Master Upah diperbarui.');
    }

    public function upahHapus($id)
    {
        $this->hapusLunak('upah_master', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('bahan')->with('sukses', 'Jenis tenaga dihapus dari Master Upah. Pembayaran upah yang sudah tercatat tidak berubah (tarif tersimpan sebagai potret saat pembayaran).');
    }

    /**
     * Gabungkan duplikat jenis tenaga: tarif & satuan mengikuti baris
     * utama, baris lain dihapus. Pembayaran upah historis menyimpan
     * jenis sebagai teks + tarif potret, jadi tidak perlu dialihkan.
     */
    public function upahGabung()
    {
        $utamaId = (int) $this->request->getPost('utama');
        $anggota = array_map('intval', (array) $this->request->getPost('anggota'));
        if (! in_array($utamaId, $anggota, true) || count($anggota) < 2) {
            return redirect()->to('bahan')->with('galat', 'Pilih jenis tenaga utama dari kelompok duplikatnya.');
        }
        // Perbaikan dulu, baru gabung: tarif final dari formulir alert.
        $tarifFinal = $this->request->getPost('tarif_final');
        if ($tarifFinal !== null && $tarifFinal !== '') {
            $this->db()->table('upah_master')->where(['id' => $utamaId, 'sekolah_id' => $this->sid()])->update([
                'tarif' => (float) str_replace(['.', ','], ['', '.'], (string) $tarifFinal),
            ]);
        }
        foreach ($anggota as $aid) {
            if ($aid !== $utamaId) {
                $this->hapusLunak('upah_master', ['id' => $aid, 'sekolah_id' => $this->sid()]);
            }
        }
        return redirect()->to('bahan')->with('sukses', 'Duplikat jenis tenaga digabung — kini hanya ada satu tarif acuan untuk jenis tersebut.');
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        return [
            'kode'       => strtoupper(trim((string) $r->getPost('kode'))),
            'nama'       => trim((string) $r->getPost('nama')),
            'satuan'     => trim((string) $r->getPost('satuan')),
            'harga_rab'  => (int) $r->getPost('harga_rab'),
            'qty_rkb'    => (float) $r->getPost('qty_rkb'),
            'qty_adm'    => (float) $r->getPost('qty_adm'),
            'qty_toilet' => (float) $r->getPost('qty_toilet'),
        ];
    }
}

<?php

namespace App\Controllers;

use App\Libraries\MesinLaporan;
use App\Libraries\PenulisTemplate;

/**
 * Template Laporan — perpustakaan template .xlsx per jenis dokumen dan
 * render-nya dari konteks entitas nyata, mengikuti mesin template SPJ BOSP.
 * Lihat App\Libraries\MesinLaporan untuk katalog & aturan render.
 */
class TemplateLaporan extends BaseController
{
    private string $dir;

    public function __construct()
    {
        $this->dir = WRITEPATH . 'template-laporan';
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    public function index()
    {
        $db = $this->db();
        $template = $db->table('laporan_template')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('jenis')->orderBy('nama')->get()->getResultArray();
        // Contoh nilai dari entitas nyata pertama (untuk panel katalog).
        $spkPertama = $db->query('SELECT id FROM spk WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY nomor LIMIT 1', [$this->sid()])->getRowArray();
        $gedungPertama = $db->query('SELECT id FROM gedung WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY nama LIMIT 1', [$this->sid()])->getRowArray();
        $contoh = [
            'spk' => $spkPertama ? $this->konteksSpk((int) $spkPertama['id']) : null,
            'gedung' => $gedungPertama ? $this->konteksGedung((int) $gedungPertama['id']) : null,
            'sekolah' => $this->konteksSekolah(),
        ];
        $daftarSpk = $db->query('SELECT id, nomor, paket FROM spk WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY nomor', [$this->sid()])->getResultArray();
        $daftarGedung = $db->query('SELECT id, nama FROM gedung WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY nama', [$this->sid()])->getResultArray();
        return $this->tampil('template_laporan/index', [
            'template' => $template, 'jenis' => MesinLaporan::jenis(),
            'katalog' => ['spk' => MesinLaporan::katalog('spk'), 'gedung' => MesinLaporan::katalog('gedung'), 'sekolah' => MesinLaporan::katalog('sekolah')],
            'katalog_berulang' => ['spk' => MesinLaporan::katalogBerulang('spk'), 'gedung' => MesinLaporan::katalogBerulang('gedung'), 'sekolah' => MesinLaporan::katalogBerulang('sekolah')],
            'contoh' => $contoh, 'daftar_spk' => $daftarSpk, 'daftar_gedung' => $daftarGedung,
        ], 'Template Laporan — Aplikasi PBJ Sekolah');
    }

    public function unggah()
    {
        $jenis = (string) $this->request->getPost('jenis');
        if (! isset(MesinLaporan::jenis()[$jenis])) {
            return redirect()->to('template-laporan')->with('galat', 'Jenis dokumen tidak dikenal.');
        }
        $berkas = $this->request->getFile('berkas');
        if ($berkas === null || ! $berkas->isValid() || strtolower($berkas->getExtension()) !== 'xlsx') {
            return redirect()->to('template-laporan')->with('galat', 'Pilih berkas template .xlsx yang valid.');
        }
        if ($berkas->getSize() > 5 * 1024 * 1024) {
            return redirect()->to('template-laporan')->with('galat', 'Ukuran template melebihi 5 MB — kecilkan dulu (hapus gambar/lembar yang tidak perlu).');
        }
        $namaFile = bin2hex(random_bytes(8)) . '.xlsx';
        $berkas->move($this->dir, $namaFile);
        $path = $this->dir . '/' . $namaFile;
        try {
            $terpakai = MesinLaporan::pindai($path);
        } catch (\Throwable $e) {
            @unlink($path);
            return redirect()->to('template-laporan')->with('galat', 'Berkas tidak dapat dibaca sebagai .xlsx: ' . $e->getMessage());
        }
        // Nama kosong = nama berkas unggahan tanpa ekstensi.
        $namaTemplate = trim((string) $this->request->getPost('nama'));
        if ($namaTemplate === '') {
            $namaTemplate = trim(pathinfo((string) $berkas->getClientName(), PATHINFO_FILENAME)) ?: ('Template ' . MesinLaporan::jenis()[$jenis]['label']);
        }
        $this->db()->table('laporan_template')->insert([
            'sekolah_id' => $this->sid(), 'jenis' => $jenis,
            'nama' => $namaTemplate,
            'berkas' => $namaFile, 'placeholder_terpakai' => implode(',', $terpakai), 'aktif' => 1,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db()->insertID();
        $this->catatAudit('laporan_template', $id, 'unggah', 'Template ' . $jenis . ' diunggah (' . count($terpakai) . ' placeholder terdeteksi).');
        return redirect()->to('template-laporan')->with('sukses', 'Template tersimpan — ' . count($terpakai) . ' placeholder terdeteksi di dalamnya.');
    }

    public function contoh($jenis)
    {
        if (! isset(MesinLaporan::jenis()[$jenis])) {
            return redirect()->to('template-laporan')->with('galat', 'Jenis dokumen tidak dikenal.');
        }
        PenulisTemplate::unduh(MesinLaporan::contoh((string) $jenis), 'Template_Contoh_' . $jenis . '.xlsx');
        return null;
    }

    public function unduh($id)
    {
        $t = $this->ambil((int) $id);
        if ($t === null || ! is_file($this->dir . '/' . $t['berkas'])) {
            return redirect()->to('template-laporan')->with('galat', 'Berkas template tidak ditemukan.');
        }
        return $this->response->download($this->dir . '/' . $t['berkas'], null)->setFileName('Template_' . $t['jenis'] . '_' . $t['id'] . '.xlsx');
    }

    public function aktif($id)
    {
        $t = $this->ambil((int) $id);
        if ($t !== null) {
            $this->db()->table('laporan_template')->where('id', (int) $id)
                ->update(['aktif' => (int) $t['aktif'] === 1 ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        return redirect()->to('template-laporan')->with('sukses', 'Status template diubah.');
    }

    public function hapus($id)
    {
        $t = $this->ambil((int) $id);
        if ($t !== null) {
            $this->hapusLunak('laporan_template', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
            $this->catatAudit('laporan_template', (int) $id, 'hapus', 'Template ' . $t['jenis'] . ' "' . $t['nama'] . '" dihapus.');
        }
        return redirect()->to('template-laporan')->with('sukses', 'Template dihapus.');
    }

    /** Render satu template dari konteks entitas (?spk= / ?gedung=). */
    public function render($id)
    {
        $t = $this->ambil((int) $id);
        if ($t === null || ! is_file($this->dir . '/' . $t['berkas'])) {
            return redirect()->to('template-laporan')->with('galat', 'Berkas template tidak ditemukan.');
        }
        $jenis = (string) $t['jenis'];
        if ($jenis === 'spk') {
            $spkId = (int) $this->request->getGet('spk');
            $k = $spkId > 0 ? $this->konteksSpk($spkId) : null;
            if ($k === null) {
                return redirect()->to('template-laporan')->with('galat', 'Pilih SPK dulu dari daftar render pada halaman Template Laporan.');
            }
            [$skalar, $berulang] = $k;
            $namaUnduh = 'SPK_' . ($skalar['NOMOR_SPK'] ?? $spkId) . '_dari_template.xlsx';
        } elseif ($jenis === 'gedung') {
            $gedungId = (int) $this->request->getGet('gedung');
            $k = $gedungId > 0 ? $this->konteksGedung($gedungId) : null;
            if ($k === null) {
                return redirect()->to('template-laporan')->with('galat', 'Pilih gedung dulu dari daftar render pada halaman Template Laporan.');
            }
            [$skalar, $berulang] = $k;
            $namaUnduh = 'Laporan_Gedung_' . $gedungId . '_dari_template.xlsx';
        } else {
            [$skalar, $berulang] = $this->konteksSekolah();
            $namaUnduh = 'Laporan_Sekolah_dari_template.xlsx';
        }
        [$hasil, $takTerisi] = MesinLaporan::render($this->dir . '/' . $t['berkas'], $skalar, $berulang);
        if ($hasil === null) {
            return redirect()->to('template-laporan')->with('galat', 'Render dibatalkan — placeholder berikut tidak ada nilainya pada konteks ini: ' . implode(', ', $takTerisi) . '. Periksa ejaan penanda pada template terhadap katalog.');
        }
        $this->catatAudit('laporan_template', (int) $id, 'render', 'Render ' . $jenis . ' → ' . $namaUnduh);
        PenulisTemplate::unduh($hasil, $namaUnduh);
        return null;
    }

    private function ambil(int $id): ?array
    {
        $row = $this->db()->table('laporan_template')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        return $row ?: null;
    }

    /** Nilai umum sekolah & pejabat dari identitas sekolah aktif. */
    private function konteksUmum(): array
    {
        $i = $this->sekolahAktif();
        $v = static fn ($k) => trim((string) ($i[$k] ?? '')) !== '' ? (string) $i[$k] : '-';
        return [
            'NAMA_SEKOLAH' => $v('nama_sekolah'), 'NPSN' => $v('npsn'), 'ALAMAT_SEKOLAH' => $v('alamat'),
            'DESA' => $v('desa'), 'KECAMATAN' => $v('kecamatan'), 'KABUPATEN' => $v('kabupaten'),
            'PROVINSI' => $v('provinsi'), 'EMAIL_SEKOLAH' => $v('email'),
            'PAKET_PEKERJAAN' => $v('paket_pekerjaan'), 'TAHUN_ANGGARAN' => $v('tahun_anggaran'),
            'NAMA_KEPSEK' => $v('kepsek_nama'), 'NIP_KEPSEK' => $v('kepsek_nip'),
            'NAMA_KETUA' => $v('ketua_nama'), 'NAMA_SEKRETARIS' => $v('sekretaris_nama'),
            'NAMA_PELAKSANA' => $v('pelaksana_nama'),
        ];
    }

    /** Konteks satu SPK: [skalar, berulang]. Null bila SPK tak ditemukan. */
    public function konteksSpk(int $spkId): ?array
    {
        $db = $this->db();
        $s = $db->query(
            'SELECT s.*, p.nama AS penyedia_nama, p.alamat AS penyedia_alamat, p.ttd_nama AS penyedia_ttd,
                    g.nama AS gedung_nama
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             LEFT JOIN gedung g ON g.id = s.gedung_id
             WHERE s.id = ? AND s.sekolah_id = ? AND s.is_deleted = 0',
            [$spkId, $this->sid()]
        )->getRowArray();
        if (! $s) {
            return null;
        }
        $detail = $db->query(
            'SELECT d.*, COALESCE(b.kode, "") AS kode, COALESCE(b.nama, d.nama_manual) AS nama,
                    COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty*d.harga AS total,
                    gd.nama AS gedung_rincian
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id
             LEFT JOIN gedung gd ON gd.id = COALESCE(d.gedung_id, (SELECT s2.gedung_id FROM spk s2 WHERE s2.id = d.spk_id))
             WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [$spkId]
        )->getResultArray();
        $total = 0.0;
        foreach ($detail as $d) {
            $total += (float) $d['total'];
        }
        $pajak = $this->pajakPembelian($total, $this->ppnInvoice($this->ppnSpkMap(), $spkId, $total));
        $bap = $db->query('SELECT * FROM bap WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [$spkId])->getRowArray();
        $bast = $db->query('SELECT * FROM bast WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [$spkId])->getRowArray();
        $inv = $db->query('SELECT * FROM invoice WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [$spkId])->getRowArray();
        $kosong = static fn ($x) => $x === null || trim((string) $x) === '' ? '-' : (string) $x;
        $skalar = $this->konteksUmum() + [
            'NOMOR_SPK' => (string) (int) $s['nomor'],
            'NOMOR_SURAT_SPK' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'TANGGAL_SPK' => ! empty($s['tanggal']) ? tgl($s['tanggal']) : '-',
            'TANGGAL_SPK_PANJANG' => $this->hariTanggal($s['tanggal'] ?? null),
            'PAKET_SPK' => $kosong($s['paket'] ?? null),
            'TGL_MULAI' => ! empty($s['tgl_mulai']) ? tgl($s['tgl_mulai']) : '-',
            'TGL_SELESAI' => ! empty($s['tgl_selesai']) ? tgl($s['tgl_selesai']) : '-',
            'STATUS_SPK' => $kosong($s['status'] ?? null),
            'GEDUNG_SPK' => $kosong($s['gedung_nama'] ?? null),
            'PENYEDIA_NAMA' => $kosong(trim((string) ($s['penerima_manual'] ?? '')) !== '' ? $s['penerima_manual'] : ($s['penyedia_nama'] ?? null)),
            'PENYEDIA_ALAMAT' => $kosong($s['penyedia_alamat'] ?? null),
            'PENYEDIA_TTD' => $kosong(trim((string) ($s['penerima_manual'] ?? '')) !== '' ? $s['penerima_manual'] : ($s['penyedia_ttd'] ?? ($s['penyedia_nama'] ?? null))),
            'PENERIMA_MANUAL' => $kosong($s['penerima_manual'] ?? null),
            'TOTAL_SPK' => MesinLaporan::angka($total),
            'TOTAL_SPK_RP' => 'Rp ' . number_format($total, 0, ',', '.'),
            'TOTAL_TERBILANG' => trim($this->terbilang((int) round($total))),
            'DPP' => MesinLaporan::angka($pajak['dpp']), 'PPN' => MesinLaporan::angka($pajak['ppn']),
            'PPH22' => MesinLaporan::angka($pajak['pph22']),
            'DIBAYAR' => MesinLaporan::angka($pajak['dibayar']),
            'DIBAYAR_RP' => 'Rp ' . number_format((float) $pajak['dibayar'], 0, ',', '.'),
            'BAP_NOMOR_SURAT' => $bap ? $this->nomorSurat('BAP', (int) $bap['nomor_urut']) : '-',
            'BAP_TANGGAL' => $bap && ! empty($bap['tanggal']) ? tgl($bap['tanggal']) : '-',
            'BAST_NOMOR_SURAT' => $bast ? $this->nomorSurat('BAST', (int) $bast['nomor_urut']) : '-',
            'BAST_TANGGAL' => $bast && ! empty($bast['tanggal']) ? tgl($bast['tanggal']) : '-',
            'INVOICE_NOMOR_SURAT' => $inv ? $this->nomorSurat('INV', (int) $inv['nomor_urut']) : '-',
            'INVOICE_NILAI_RP' => $inv ? 'Rp ' . number_format((float) $inv['nilai'], 0, ',', '.') : '-',
        ];
        $rincian = [];
        $no = 1;
        foreach ($detail as $d) {
            $rincian[] = [
                'RINCIAN_NO' => (string) $no++, 'RINCIAN_KODE' => (string) ($d['kode'] ?? ''),
                'RINCIAN_NAMA' => (string) ($d['nama'] ?? ''), 'RINCIAN_GEDUNG' => (string) ($d['gedung_rincian'] ?? ''),
                'RINCIAN_QTY' => MesinLaporan::qty($d['qty']), 'RINCIAN_SATUAN' => (string) ($d['satuan'] ?? ''),
                'RINCIAN_HARGA' => MesinLaporan::angka($d['harga']), 'RINCIAN_TOTAL' => MesinLaporan::angka($d['total']),
            ];
        }
        $tenaga = [];
        try {
            $barisTenaga = $db->table('spk_tenaga')->where(['spk_id' => $spkId, 'is_deleted' => 0])->orderBy('id')->get()->getResultArray();
        } catch (\Throwable $e) {
            $barisTenaga = [];
        }
        $no = 1;
        foreach ($barisTenaga as $t) {
            $tenaga[] = [
                'TENAGA_NO' => (string) $no++, 'TENAGA_JENIS' => (string) ($t['jenis'] ?? ''),
                'TENAGA_KEAHLIAN' => (string) ($t['keahlian'] ?? ''), 'TENAGA_OH' => MesinLaporan::qty($t['oh']),
                'TENAGA_ORANG' => $t['jumlah_orang'] === null ? '' : (string) (int) $t['jumlah_orang'],
            ];
        }
        $rptBap = [];
        $no = 1;
        foreach ($db->query('SELECT * FROM bap WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut', [$spkId])->getResultArray() as $b) {
            $jml = (int) ($db->query('SELECT COUNT(*) AS j FROM bap_detail WHERE bap_id = ? AND is_deleted = 0', [(int) $b['id']])->getRow()->j ?? 0);
            $rptBap[] = [
                'RPT_BAP_NO' => (string) $no++, 'RPT_BAP_NOMOR_SURAT' => $this->nomorSurat('BAP', (int) $b['nomor_urut']),
                'RPT_BAP_TANGGAL' => ! empty($b['tanggal']) ? tgl($b['tanggal']) : '-',
                'RPT_BAP_JUMLAH_RINCIAN' => (string) $jml,
            ];
        }
        $rptBast = [];
        $no = 1;
        foreach ($db->query('SELECT * FROM bast WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut', [$spkId])->getResultArray() as $b) {
            $bapInduk = ! empty($b['bap_id']) ? $db->table('bap')->where('id', (int) $b['bap_id'])->get()->getRowArray() : null;
            $rptBast[] = [
                'RPT_BAST_NO' => (string) $no++, 'RPT_BAST_NOMOR_SURAT' => $this->nomorSurat('BAST', (int) $b['nomor_urut']),
                'RPT_BAST_TANGGAL' => ! empty($b['tanggal']) ? tgl($b['tanggal']) : '-',
                'RPT_BAST_BAP_NOMOR' => $bapInduk ? $this->nomorSurat('BAP', (int) $bapInduk['nomor_urut']) : '-',
            ];
        }
        $rptInv = [];
        $no = 1;
        foreach ($db->query('SELECT * FROM invoice WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut', [$spkId])->getResultArray() as $b) {
            $lunas = (int) ($db->query('SELECT COUNT(*) AS j FROM pembayaran WHERE invoice_id = ? AND is_deleted = 0', [(int) $b['id']])->getRow()->j ?? 0) > 0;
            $rptInv[] = [
                'RPT_INV_NO' => (string) $no++, 'RPT_INV_NOMOR_SURAT' => $this->nomorSurat('INV', (int) $b['nomor_urut']),
                'RPT_INV_TANGGAL' => ! empty($b['tanggal']) ? tgl($b['tanggal']) : '-',
                'RPT_INV_NILAI' => MesinLaporan::angka($b['nilai']), 'RPT_INV_NILAI_RP' => 'Rp ' . number_format((float) $b['nilai'], 0, ',', '.'),
                'RPT_INV_STATUS' => $lunas ? 'Sudah Bayar' : 'Belum Bayar',
            ];
        }
        return [$skalar, ['RINCIAN' => $rincian, 'TENAGA' => $tenaga, 'RPT_BAP' => $rptBap, 'RPT_BAST' => $rptBast, 'RPT_INV' => $rptInv]];
    }

    /** Konteks satu gedung: [skalar, berulang]. Null bila gedung tak ditemukan. */
    public function konteksGedung(int $gedungId): ?array
    {
        $db = $this->db();
        $g = $this->ambilGedung($gedungId);
        if ($g === null) {
            return null;
        }
        $asli = (float) ($db->query('SELECT COALESCE(SUM(volume * harga_satuan), 0) AS t FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0', [$gedungId])->getRow()->t ?? 0);
        $analisa = $this->totalBoqGedung($gedungId);
        $pekerjaan = $db->query('SELECT * FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0 ORDER BY urutan, id', [$gedungId])->getResultArray();
        $nilaiRealisasi = $this->nilaiRealisasiGedung($gedungId);
        $fisik = $analisa > 0 ? min(100, $nilaiRealisasi / $analisa * 100) : 0.0;
        $realisasiPerKerja = [];
        if ($pekerjaan !== []) {
            $ids = implode(',', array_map(static fn ($p) => (int) $p['id'], $pekerjaan));
            foreach ($db->query('SELECT pekerjaan_id, SUM(volume) AS v FROM realisasi WHERE pekerjaan_id IN (' . $ids . ') AND is_deleted = 0 GROUP BY pekerjaan_id')->getResultArray() as $r) {
                $realisasiPerKerja[(int) $r['pekerjaan_id']] = (float) $r['v'];
            }
        }
        $skalar = $this->konteksUmum() + [
            'NAMA_GEDUNG' => (string) $g['nama'],
            'NILAI_BOQ' => MesinLaporan::angka($asli), 'NILAI_BOQ_RP' => 'Rp ' . number_format($asli, 0, ',', '.'),
            'NILAI_ANALISA' => MesinLaporan::angka($analisa), 'NILAI_ANALISA_RP' => 'Rp ' . number_format($analisa, 0, ',', '.'),
            'NILAI_REALISASI_RP' => 'Rp ' . number_format($nilaiRealisasi, 0, ',', '.'),
            'PERSEN_FISIK' => number_format($fisik, 1, ',', '.'),
            'JUMLAH_PEKERJAAN' => (string) count($pekerjaan),
        ];
        $kerja = [];
        $no = 1;
        foreach ($pekerjaan as $p) {
            $vol = (float) ($p['volume_revisi'] ?? $p['volume']);
            $real = $realisasiPerKerja[(int) $p['id']] ?? 0.0;
            $berjalan = $this->hargaBerjalan($p);
            $kerja[] = [
                'KERJA_NO' => (string) $no++, 'KERJA_KODE' => (string) ($p['kode_analisa'] ?? ''),
                'KERJA_NAMA' => (string) ($p['nama'] ?? ''), 'KERJA_VOLUME' => MesinLaporan::qty($vol),
                'KERJA_SATUAN' => (string) ($p['satuan'] ?? ''), 'KERJA_HARGA' => MesinLaporan::angka($berjalan),
                'KERJA_HARGA_RP' => 'Rp ' . number_format($berjalan, 0, ',', '.'),
                'KERJA_NILAI' => MesinLaporan::angka($vol * $berjalan),
                'KERJA_NILAI_RP' => 'Rp ' . number_format($vol * $berjalan, 0, ',', '.'),
                'KERJA_HARGA_DOKUMEN' => MesinLaporan::angka($p['harga_satuan']),
                'KERJA_NILAI_DOKUMEN' => MesinLaporan::angka($vol * (float) $p['harga_satuan']),
                'KERJA_REALISASI' => number_format($vol > 0 ? min(100, $real / $vol * 100) : 0.0, 1, ',', '.'),
            ];
        }
        return [$skalar, ['KERJA' => $kerja]];
    }

    /** Konteks rekap sekolah: [skalar, berulang]. */
    public function konteksSekolah(): array
    {
        $db = $this->db();
        $sekolah = $this->sekolahAktif();
        $pagu = (float) ($sekolah['total_anggaran'] ?? 0);
        $gedung = $db->table('gedung')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0, 'didanai' => 1])->orderBy('nama')->get()->getResultArray();
        $totalBoq = 0.0;
        $rekap = [];
        $no = 1;
        foreach ($gedung as $g) {
            $asli = (float) ($db->query('SELECT COALESCE(SUM(volume * harga_satuan), 0) AS t FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0', [(int) $g['id']])->getRow()->t ?? 0);
            $analisa = $this->totalBoqGedung((int) $g['id']);
            $totalBoq += $analisa;
            $fisik = $analisa > 0 ? min(100, $this->nilaiRealisasiGedung((int) $g['id']) / $analisa * 100) : 0.0;
            $rekap[] = [
                'REKAP_NO' => (string) $no++, 'REKAP_GEDUNG' => (string) $g['nama'],
                'REKAP_BOQ' => MesinLaporan::angka($asli), 'REKAP_ANALISA' => MesinLaporan::angka($analisa),
                'REKAP_FISIK' => number_format($fisik, 1, ',', '.'),
            ];
        }
        $totalSpk = (float) ($db->query('SELECT COALESCE(SUM(d.qty * d.harga), 0) AS t FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0 WHERE s.sekolah_id = ? AND d.is_deleted = 0', [$this->sid()])->getRow()->t ?? 0);
        // Terbayar bersih: pembayaran tak menyimpan nominal — dihitung per
        // invoice lewat pajakPembelian, sama seperti Dashboard.
        $terbayar = 0.0;
        $petaPpn = $this->ppnSpkMap();
        foreach ($db->table('pembayaran p')->select('i.nilai, i.spk_id')->join('invoice i', 'i.id = p.invoice_id AND i.is_deleted = 0')->join('spk s', 's.id = i.spk_id AND s.is_deleted = 0')->where(['s.sekolah_id' => $this->sid(), 'p.is_deleted' => 0])->get()->getResultArray() as $r) {
            $terbayar += $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($petaPpn, (int) $r['spk_id'], (float) $r['nilai']))['dibayar'];
        }
        $jmlSpk = (int) ($db->query('SELECT COUNT(*) AS j FROM spk WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow()->j ?? 0);
        $skalar = $this->konteksUmum() + [
            'TOTAL_ANGGARAN_RP' => 'Rp ' . number_format($pagu, 0, ',', '.'),
            'TOTAL_BOQ_RP' => 'Rp ' . number_format($totalBoq, 0, ',', '.'),
            'TOTAL_SPK_RP' => 'Rp ' . number_format($totalSpk, 0, ',', '.'),
            'TOTAL_TERBAYAR_RP' => 'Rp ' . number_format($terbayar, 0, ',', '.'),
            'SISA_PAGU_RP' => 'Rp ' . number_format($pagu - $terbayar, 0, ',', '.'),
            'JUMLAH_GEDUNG' => (string) count($gedung), 'JUMLAH_SPK' => (string) $jmlSpk,
        ];
        return [$skalar, ['REKAP' => $rekap]];
    }

    /** "Senin, 17 November 2026" — padanan hariTanggal pada Dokumen. */
    private function hariTanggal(?string $tgl): string
    {
        if ($tgl === null || trim($tgl) === '') {
            return '-';
        }
        $ts = strtotime($tgl);
        if ($ts === false) {
            return '-';
        }
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $hari[(int) date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

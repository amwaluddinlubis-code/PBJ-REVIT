<?php

namespace App\Controllers;

/**
 * Pemeliharaan Database (khusus admin): informasi & ukuran tabel, backup
 * SQL (buat/unduh/hapus), pemulihan dari berkas backup, optimasi tabel,
 * dan pengelolaan sampah (baris terhapus-lunak: pulihkan / hapus permanen).
 */
class Pemeliharaan extends BaseController
{
    private function admin(): bool
    {
        return ($this->pengguna['peran'] ?? '') === 'admin';
    }

    private function tolak()
    {
        return redirect()->to('/')->with('galat', 'Pemeliharaan database hanya untuk administrator.');
    }

    private function folderBackup(): string
    {
        $dir = (new \Config\Paths())->writableDirectory . '/backup';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function formatUkuran(float $b): string
    {
        if ($b >= 1048576) {
            return number_format($b / 1048576, 2, ',', '.') . ' MB';
        }
        if ($b >= 1024) {
            return number_format($b / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($b, 0, ',', '.') . ' B';
    }

    /** Semua tabel database aktif beserta ukuran & perkiraan barisnya. */
    private function infoTabel(): array
    {
        return $this->db()->query(
            'SELECT TABLE_NAME AS nama, TABLE_ROWS AS baris, (DATA_LENGTH + INDEX_LENGTH) AS ukuran
             FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
        )->getResultArray();
    }

    /** Tabel yang memiliki kolom is_deleted, dengan hitungan aktif/terhapus. */
    private function tabelSampah(): array
    {
        $db = $this->db();
        $hasil = [];
        foreach ($db->query("SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'is_deleted' ORDER BY TABLE_NAME")->getResultArray() as $r) {
            $t = $r['t'];
            $hasil[] = [
                'nama'     => $t,
                'aktif'    => (int) $db->table($t)->where('is_deleted', 0)->countAllResults(),
                'terhapus' => (int) $db->table($t)->where('is_deleted', 1)->countAllResults(),
            ];
        }
        return $hasil;
    }

    private function namaTabelValid(string $tabel): bool
    {
        foreach ($this->tabelSampah() as $t) {
            if ($t['nama'] === $tabel) {
                return true;
            }
        }
        return false;
    }

    public function index()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $db = $this->db();
        $tabel = $this->infoTabel();
        $totalUkuran = array_sum(array_map(static fn ($t) => (float) $t['ukuran'], $tabel));

        $backups = [];
        foreach (glob($this->folderBackup() . '/*.sql') ?: [] as $f) {
            $backups[] = [
                'nama'    => basename($f),
                'ukuran'  => $this->formatUkuran((float) filesize($f)),
                'tanggal' => date('d-m-Y H:i', filemtime($f)),
                'mtime'   => filemtime($f),
            ];
        }
        usort($backups, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return $this->tampil('pemeliharaan/index', [
            'namaDb'      => $db->getDatabase(),
            'versi'       => (string) ($db->query('SELECT VERSION() AS v')->getRowArray()['v'] ?? ''),
            'tabel'       => $tabel,
            'totalUkuran' => $this->formatUkuran($totalUkuran),
            'sampah'      => $this->tabelSampah(),
            'backups'     => $backups,
        ], 'Pemeliharaan Database');
    }

    /** Tulis satu berkas backup SQL lengkap (struktur + data) ke writable/backup. */
    private function buatBerkasBackup(string $label): string
    {
        $db = $this->db();
        $nama = $label . '-' . date('Ymd-His') . '.sql';
        $path = $this->folderBackup() . '/' . $nama;
        $fh = fopen($path, 'wb');
        fwrite($fh, "-- Backup database PBJ-REVIT (dibuat dari modul Pemeliharaan Database)\n");
        fwrite($fh, '-- Database: ' . $db->getDatabase() . ' · ' . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $daftarTabel = [];
        foreach ($db->query('SHOW TABLES')->getResultArray() as $r) {
            $daftarTabel[] = (string) array_values($r)[0];
        }
        foreach ($daftarTabel as $t) {
            $buat = $db->query('SHOW CREATE TABLE `' . $t . '`')->getRowArray();
            fwrite($fh, '-- Tabel: ' . $t . "\nDROP TABLE IF EXISTS `" . $t . "`;\n" . $buat['Create Table'] . ";\n");
            $baris = $db->table($t)->get()->getResultArray();
            if ($baris !== []) {
                $kolom = '`' . implode('`, `', array_keys($baris[0])) . '`';
                foreach (array_chunk($baris, 200) as $grup) {
                    $nilai = [];
                    foreach ($grup as $r) {
                        $sel = [];
                        foreach ($r as $v) {
                            $sel[] = $v === null ? 'NULL' : $db->escape($v);
                        }
                        $nilai[] = '(' . implode(', ', $sel) . ')';
                    }
                    fwrite($fh, 'INSERT INTO `' . $t . '` (' . $kolom . ') VALUES ' . implode(', ', $nilai) . ";\n");
                }
            }
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return $nama;
    }

    public function backup()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $nama = $this->buatBerkasBackup('backup');
        $this->catatAudit('pemeliharaan', null, 'backup', 'Backup dibuat: ' . $nama);
        return redirect()->to('/pemeliharaan-db')->with('sukses', 'Backup dibuat: ' . $nama . ' — unduh dari daftar Backup Tersimpan.');
    }

    public function unduh($nama)
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $aman = basename((string) $nama);
        $path = $this->folderBackup() . '/' . $aman;
        if (! str_ends_with($aman, '.sql') || ! is_file($path)) {
            return redirect()->to('/pemeliharaan-db')->with('galat', 'Berkas backup tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }

    public function hapusBackup()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $aman = basename((string) $this->request->getPost('nama'));
        $path = $this->folderBackup() . '/' . $aman;
        if (str_ends_with($aman, '.sql') && is_file($path)) {
            unlink($path);
            return redirect()->to('/pemeliharaan-db')->with('sukses', 'Berkas backup ' . $aman . ' dihapus.');
        }
        return redirect()->to('/pemeliharaan-db')->with('galat', 'Berkas backup tidak ditemukan.');
    }

    public function pulih()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $dariBackup = basename((string) $this->request->getPost('dari_backup'));
        $path = null;
        $asal = '';
        if ($dariBackup !== '' && str_ends_with($dariBackup, '.sql') && is_file($this->folderBackup() . '/' . $dariBackup)) {
            $path = $this->folderBackup() . '/' . $dariBackup;
            $asal = $dariBackup;
        } else {
            $berkas = $this->request->getFile('berkas');
            if ($berkas !== null && $berkas->isValid() && strtolower($berkas->getExtension()) === 'sql') {
                $path = $berkas->getTempName();
                $asal = $berkas->getClientName();
            }
        }
        if ($path === null) {
            return redirect()->to('/pemeliharaan-db')->with('galat', 'Pilih berkas .sql yang akan dipulihkan (unggah atau dari backup tersimpan).');
        }

        // Keadaan saat ini selalu dibackup dulu agar pemulihan bisa dibatalkan.
        $praNama = $this->buatBerkasBackup('pra-pulih');

        $sql = (string) file_get_contents($path);
        // Pulihkan selalu ke database yang sedang aktif: buang perintah ganti database dari dump.
        $sql = preg_replace('/^\s*CREATE DATABASE.*?;\s*$/mi', '', $sql);
        $sql = preg_replace('/^\s*USE\s+`?\w+`?\s*;\s*$/mi', '', $sql);

        $db = $this->db();
        $db->query('SELECT 1');
        $mysqli = $db->connID;
        $galat = null;
        if (! $mysqli->multi_query($sql)) {
            $galat = $mysqli->error;
        } else {
            do {
                if ($hasil = $mysqli->store_result()) {
                    $hasil->free();
                }
                if ($mysqli->error) {
                    $galat = $mysqli->error;
                    break;
                }
            } while ($mysqli->more_results() && $mysqli->next_result());
        }
        if ($galat !== null) {
            return redirect()->to('/pemeliharaan-db')->with('galat', 'Pemulihan berhenti karena galat: ' . $galat . ' — keadaan sebelum pemulihan tersimpan sebagai ' . $praNama . ', pulihkan berkas itu untuk mengembalikan.');
        }
        $this->catatAudit('pemeliharaan', null, 'pulih', 'Database dipulihkan dari ' . $asal . '; backup pra-pulih: ' . $praNama);
        return redirect()->to('/pemeliharaan-db')->with('sukses', 'Database dipulihkan dari ' . $asal . '. Backup keadaan sebelumnya tersimpan sebagai ' . $praNama . '.');
    }

    public function optimasi()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $db = $this->db();
        $n = 0;
        foreach ($db->query('SHOW TABLES')->getResultArray() as $r) {
            $t = (string) array_values($r)[0];
            $db->query('OPTIMIZE TABLE `' . $t . '`');
            $n++;
        }
        return redirect()->to('/pemeliharaan-db')->with('sukses', $n . ' tabel dioptimasi — ruang kosong sisa penghapusan dikembalikan ke database.');
    }

    public function sampahPulihkan()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $tabel = (string) $this->request->getPost('tabel');
        if (! $this->namaTabelValid($tabel)) {
            return redirect()->to('/pemeliharaan-db')->with('galat', 'Tabel tidak dikenal.');
        }
        $db = $this->db();
        $db->table($tabel)->where('is_deleted', 1)->update(['is_deleted' => 0, 'deleted_at' => null]);
        $n = $db->affectedRows();
        return redirect()->to('/pemeliharaan-db')->with('sukses', $n . ' baris pada tabel ' . $tabel . ' dipulihkan dari sampah.');
    }

    public function sampahHapus()
    {
        if (! $this->admin()) {
            return $this->tolak();
        }
        $tabel = (string) $this->request->getPost('tabel');
        if (! $this->namaTabelValid($tabel)) {
            return redirect()->to('/pemeliharaan-db')->with('galat', 'Tabel tidak dikenal.');
        }
        $db = $this->db();
        $db->table($tabel)->where('is_deleted', 1)->delete();
        $n = $db->affectedRows();
        return redirect()->to('/pemeliharaan-db')->with('sukses', $n . ' baris terhapus pada tabel ' . $tabel . ' dihapus permanen.');
    }
}

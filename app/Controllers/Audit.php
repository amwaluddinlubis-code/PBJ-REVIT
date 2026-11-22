<?php

namespace App\Controllers;

/** Jejak Audit — catatan aksi penting (uang & persetujuan). Khusus admin. */
class Audit extends BaseController
{
    public function index()
    {
        if (($this->pengguna['peran'] ?? '') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Halaman Jejak Audit khusus admin.');
        }
        $db = $this->db();
        $daftar = $db->tableExists('audit_events') ? $db->query(
            'SELECT * FROM audit_events WHERE sekolah_id = ? OR sekolah_id IS NULL ORDER BY id DESC LIMIT 500',
            [$this->sid()]
        )->getResultArray() : [];
        return $this->tampil('audit/index', ['daftar' => $daftar], 'Jejak Audit — Aplikasi PBJ Sekolah');
    }
}

<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PenggunaAwalSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('users')->countAllResults() === 0) {
            $this->db->table('users')->insert([
                'username'      => 'admin',
                'nama'          => 'Administrator',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'peran'         => 'admin',
                'harus_ganti_sandi' => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

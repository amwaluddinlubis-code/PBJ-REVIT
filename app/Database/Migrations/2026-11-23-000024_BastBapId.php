<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * BAST tertaut ke BAP (aturan 1 BAP = 1 BAST): tambah bast.bap_id.
 * BAST lama ditautkan ke BAP-nya bila SPK induk hanya memiliki satu BAP
 * (tautan pasti); selain itu bap_id dibiarkan NULL (BAST umum per SPK).
 */
class BastBapId extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('bast')) {
            return;
        }
        if (! $this->db->fieldExists('bap_id', 'bast')) {
            $this->forge->addColumn('bast', [
                'bap_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'spk_id'],
            ]);
        }
        if ($this->db->tableExists('bap')) {
            $this->db->query(
                'UPDATE bast bs JOIN (SELECT spk_id, MIN(id) AS bap_id, COUNT(*) AS j FROM bap WHERE is_deleted = 0 GROUP BY spk_id) x
                    ON x.spk_id = bs.spk_id AND x.j = 1
                 SET bs.bap_id = x.bap_id WHERE bs.bap_id IS NULL AND bs.is_deleted = 0'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bast') && $this->db->fieldExists('bap_id', 'bast')) {
            $this->forge->dropColumn('bast', 'bap_id');
        }
    }
}

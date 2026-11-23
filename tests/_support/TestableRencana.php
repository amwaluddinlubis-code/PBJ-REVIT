<?php

namespace Tests\Support;

use App\Controllers\Rencana;

/**
 * Proxy Rencana::hitungPilihan() untuk pengujian database:
 * kebutuhan bahan bersih dan kebutuhan tenaga dari pekerjaan terpilih.
 */
class TestableRencana extends Rencana
{
    public function pubHitungPilihan(array $volRencana, array $daftarPekerjaan, int $hari): array
    {
        return $this->hitungPilihan($volRencana, $daftarPekerjaan, $hari);
    }
}

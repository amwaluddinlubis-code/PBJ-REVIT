<?php

namespace Tests\Support;

use App\Controllers\Invoice;

/**
 * Proxy Invoice::sudahTerbayar() untuk pengujian database:
 * kunci invoice yang sudah memiliki jurnal pembayaran aktif.
 */
class TestableInvoice extends Invoice
{
    public function pubSudahTerbayar(int $invoiceId): bool
    {
        return $this->sudahTerbayar($invoiceId);
    }
}

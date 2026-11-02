<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Validasi token API kasir (header X-API-TOKEN).
     * Token wajib di-set lewat CASHIER_API_TOKEN; jika kosong, semua request ditolak.
     */
    protected function isValidCashierToken(Request $request): bool
    {
        $expectedToken = config('services.cashier.token');
        $apiToken = (string) $request->header('X-API-TOKEN', '');

        return filled($expectedToken) && hash_equals((string) $expectedToken, $apiToken);
    }
}

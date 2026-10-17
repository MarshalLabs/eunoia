<?php

namespace Eunoia\JoFotara\Services;

use Eunoia\JoFotara\Enums\SellerRegistration;
use Webkul\Account\Models\Move;

/**
 * JoFotara invoice type code "0PT" (JF-03): P = payment (1 cash, 2 receivable),
 * T = seller registration (1 income, 2 general sales, 3 special sales). Never hard-coded.
 */
class InvoiceTypeCodeResolver
{
    public function resolve(Move $move, SellerRegistration $registration, ?bool $cash = null): string
    {
        $cash ??= $this->isCash($move);

        return '0'.($cash ? '1' : '2').$registration->digit();
    }

    /**
     * Cash when payment is due on the invoice date (POS sales, immediate payment); receivable otherwise.
     * NEEDS ACCOUNTANT: confirm for partially paid invoices (open question Q12).
     */
    public function isCash(Move $move): bool
    {
        if (blank($move->invoice_date_due) || blank($move->invoice_date)) {
            return true;
        }

        return $move->invoice_date_due->startOfDay()->lessThanOrEqualTo($move->invoice_date->startOfDay());
    }
}

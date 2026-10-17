<?php

namespace Eunoia\LocalizationJo\Observers;

use Eunoia\Core\Support\PhoneNumber;
use Webkul\Partner\Models\Partner;

/**
 * Stores partner phones in E.164 (JO-08): "0791234567" → "+962791234567".
 */
class PartnerPhoneObserver
{
    public function saving(Partner $partner): void
    {
        $country = $partner->country?->code;

        if ($country !== null && ! array_key_exists($country, PhoneNumber::CALLING_CODES)) {
            return;
        }

        foreach (['phone', 'mobile'] as $field) {
            if ($partner->isDirty($field) && filled($partner->{$field})) {
                $partner->{$field} = PhoneNumber::toE164($partner->{$field}, $country ?? 'JO');
            }
        }
    }
}

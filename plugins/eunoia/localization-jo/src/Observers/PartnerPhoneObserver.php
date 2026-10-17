<?php

namespace Eunoia\LocalizationJo\Observers;

use Eunoia\Core\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;

/**
 * Stores partner phones in E.164 (JO-08): "0791234567" → "+962791234567" for Jordanian/Palestinian partners
 * (partner country, else its company's country). Other countries are left as typed.
 */
class PartnerPhoneObserver
{
    public function saving(Partner $partner): void
    {
        $countryId = $partner->country_id
            ?? ($partner->company_id ? DB::table('companies')->where('id', $partner->company_id)->value('country_id') : null);

        $country = $countryId ? DB::table('countries')->where('id', $countryId)->value('code') : null;

        if ($country === null || ! array_key_exists($country, PhoneNumber::CALLING_CODES)) {
            return;
        }

        foreach (['phone', 'mobile'] as $field) {
            if ($partner->isDirty($field) && filled($partner->{$field})) {
                $partner->{$field} = PhoneNumber::toE164($partner->{$field}, $country);
            }
        }
    }
}

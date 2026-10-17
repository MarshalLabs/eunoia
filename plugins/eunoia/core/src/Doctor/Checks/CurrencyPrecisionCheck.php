<?php

namespace Eunoia\Core\Doctor\Checks;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;
use Webkul\Support\Models\Currency;

/**
 * JOD must have 3 decimals and rounding 0.001 (finding F1, patch P1); no currency may have rounding <= 0.
 */
class CurrencyPrecisionCheck implements DoctorCheck
{
    public function label(): string
    {
        return __('eunoia-core::doctor.checks.currency');
    }

    public function run(): CheckResult
    {
        $jod = Currency::query()->where('name', 'JOD')->first();

        if (! $jod) {
            return CheckResult::fail(__('eunoia-core::doctor.messages.currency_missing', ['currency' => 'JOD']));
        }

        if ((int) $jod->decimal_places !== 3 || float_compare((float) $jod->rounding, 0.001, precisionDigits: 6) !== 0) {
            return CheckResult::fail(__('eunoia-core::doctor.messages.currency_wrong', [
                'currency' => 'JOD',
                'decimals' => $jod->decimal_places,
                'rounding' => $jod->rounding,
            ]));
        }

        $broken = Currency::query()->where('rounding', '<=', 0)->pluck('name');

        if ($broken->isNotEmpty()) {
            return CheckResult::fail(__('eunoia-core::doctor.messages.currency_zero_rounding', [
                'currencies' => $broken->implode(', '),
            ]));
        }

        return CheckResult::ok(__('eunoia-core::doctor.messages.currency_ok'));
    }
}

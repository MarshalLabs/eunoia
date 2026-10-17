<?php

namespace Eunoia\Core\Doctor\Checks;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;

class LocaleCheck implements DoctorCheck
{
    public function label(): string
    {
        return __('eunoia-core::doctor.checks.locale');
    }

    public function run(): CheckResult
    {
        $locale = (string) config('app.locale');
        $timezone = (string) config('app.timezone');

        if ($locale !== 'ar' || $timezone !== 'Asia/Amman') {
            return CheckResult::warn(__('eunoia-core::doctor.messages.locale_unexpected', [
                'locale'   => $locale,
                'timezone' => $timezone,
            ]));
        }

        return CheckResult::ok(__('eunoia-core::doctor.messages.locale_ok'));
    }
}

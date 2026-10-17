<?php

namespace Eunoia\Core\Doctor\Checks;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;

/**
 * Placeholder until eunoia/theme ships self-hosted fonts (E1-01), which replaces it with a real check.
 */
class FontsCheck implements DoctorCheck
{
    public function label(): string
    {
        return __('eunoia-core::doctor.checks.fonts');
    }

    public function run(): CheckResult
    {
        return CheckResult::skip(__('eunoia-core::doctor.messages.fonts_later'));
    }
}

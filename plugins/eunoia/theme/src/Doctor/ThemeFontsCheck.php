<?php

namespace Eunoia\Theme\Doctor;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;
use Eunoia\Theme\ThemeServiceProvider;

/**
 * Self-hosted fonts are published to public/ (php artisan filament:assets) and nothing loads Google Fonts.
 */
class ThemeFontsCheck implements DoctorCheck
{
    public function label(): string
    {
        return __('eunoia-core::doctor.checks.fonts');
    }

    public function run(): CheckResult
    {
        $stylesheet = public_path(ThemeServiceProvider::fontStylesheetPath());

        if (! is_file($stylesheet)) {
            return CheckResult::fail(__('eunoia-theme::theme.doctor.fonts_missing', ['command' => 'php artisan filament:assets']));
        }

        $fonts = glob(dirname($stylesheet).'/*.woff2') ?: [];

        if (count($fonts) < 12) {
            return CheckResult::fail(__('eunoia-theme::theme.doctor.fonts_incomplete', ['count' => count($fonts)]));
        }

        return CheckResult::ok(__('eunoia-theme::theme.doctor.fonts_ok', ['count' => count($fonts)]));
    }
}

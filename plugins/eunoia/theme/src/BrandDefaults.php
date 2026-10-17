<?php

namespace Eunoia\Theme;

use Webkul\Support\Settings\BrandSettings;

class BrandDefaults
{
    /**
     * Replace upstream default brand colours with Eunoia tokens. Returns the keys that changed.
     *
     * @return array<int, string>
     */
    public static function apply(): array
    {
        $settings = app(BrandSettings::class);
        $changed = [];

        $tokens = [
            'primary_color' => Tokens::PRIMARY,
            'gray_color'    => Tokens::GRAY,
            'danger_color'  => Tokens::DANGER,
            'info_color'    => Tokens::INFO,
            'success_color' => Tokens::SUCCESS,
            'warning_color' => Tokens::WARNING,
        ];

        foreach (Tokens::brandColors() as $key => $value) {
            $current = $settings->{$key} ?? null;
            $replaceable = [Tokens::UPSTREAM_BRAND_DEFAULTS[$key] ?? null, $tokens[$key] ?? null];

            $isDefault = blank($current) || collect($replaceable)->filter()->contains(fn (string $default): bool => strcasecmp($current, $default) === 0);

            if ($isDefault && strcasecmp((string) $current, $value) !== 0) {
                $settings->{$key} = $value;
                $changed[] = $key;
            }
        }

        if ($changed) {
            $settings->save();
        }

        return $changed;
    }
}

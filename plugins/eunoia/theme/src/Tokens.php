<?php

namespace Eunoia\Theme;

use Filament\Support\Colors\Color;

/**
 * Colour tokens from docs/eunoia/02-target/05a-design-tokens.json (light mode, shade 600).
 * The single PHP source for panel colours and brand defaults.
 */
class Tokens
{
    public const PRIMARY = '#0F766E';

    public const ACCENT = '#B45309';

    public const GRAY = '#57534E';

    public const SUCCESS = '#15803D';

    public const WARNING = '#C2410C';

    public const DANGER = '#B91C1C';

    public const INFO = '#1D4ED8';

    /**
     * Filament palettes: shade 600 is exactly our token; 500 (Filament's button hover) is the darker 800 step,
     * matching the design system's "primary.700 = hover/pressed" and keeping white text ≥ 4.5:1 so Filament
     * renders solid 600 buttons with white text (Color::hex() re-curves lightness and fails that check).
     *
     * @return array<string, array<int, string>>
     */
    public static function panelColors(): array
    {
        return [
            'primary' => static::palette(Color::Teal, self::PRIMARY),
            'gray'    => Color::Stone,
            'success' => static::palette(Color::Green, self::SUCCESS),
            'warning' => static::palette(Color::Orange, self::WARNING),
            'danger'  => static::palette(Color::Red, self::DANGER),
            'info'    => static::palette(Color::Blue, self::INFO),
        ];
    }

    /**
     * @param  array<int, string>  $base  Tailwind palette whose 700 shade is close to $token
     * @return array<int, string>
     */
    public static function palette(array $base, string $token): array
    {
        return [
            50  => $base[50],
            100 => $base[100],
            200 => $base[200],
            300 => $base[300],
            400 => $base[400],
            500 => $base[800],
            600 => Color::convertToOklch($token),
            700 => $base[800],
            800 => $base[900],
            900 => $base[950],
            950 => $base[950],
        ];
    }

    /**
     * Brand settings values equal to the panel's 600 shades, so upstream ApplyBrandSettings keeps our palette.
     *
     * @return array<string, string>
     */
    public static function brandColors(): array
    {
        return collect(static::panelColors())
            ->mapWithKeys(fn (array $palette, string $key): array => ["{$key}_color" => Color::convertToHex($palette[600])])
            ->all();
    }

    /**
     * Upstream BrandSettings defaults (Tailwind 4 *-600) that we replace on install; owner-chosen values are kept.
     * Also replaced: our own earlier defaults (exact token hex), so re-installing upgrades them.
     *
     * @var array<string, string>
     */
    public const UPSTREAM_BRAND_DEFAULTS = [
        'primary_color' => '#155dfc',
        'gray_color'    => '#52525c',
        'danger_color'  => '#e7000b',
        'info_color'    => '#155dfc',
        'success_color' => '#00a63e',
        'warning_color' => '#e17100',
    ];
}

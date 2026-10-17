<?php

namespace Eunoia\Core\Support;

/**
 * Which upstream and Eunoia plugins `eunoia:install --country=XX` installs, in order.
 * Eunoia plugins that don't exist yet are skipped, so this list can name the full target set.
 */
class CountryProfile
{
    /**
     * @var array<string, array{upstream: array<int, string>, eunoia: array<int, string>}>
     */
    protected const PROFILES = [
        'JO' => [
            'upstream' => ['point-of-sale'],
            'eunoia'   => [
                'eunoia-core',
                'eunoia-theme',
                'eunoia-arabic',
                'eunoia-localization-jo',
                'eunoia-jofotara',
                'eunoia-documents',
                'eunoia-perfumery',
                'eunoia-messaging',
            ],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function countries(): array
    {
        return array_keys(static::PROFILES);
    }

    public static function supports(string $country): bool
    {
        return isset(static::PROFILES[strtoupper($country)]);
    }

    /**
     * @return array<int, string>
     */
    public static function upstreamPlugins(string $country): array
    {
        return static::PROFILES[strtoupper($country)]['upstream'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function eunoiaPlugins(string $country): array
    {
        return static::PROFILES[strtoupper($country)]['eunoia'] ?? [];
    }
}

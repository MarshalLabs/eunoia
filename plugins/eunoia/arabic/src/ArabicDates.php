<?php

namespace Eunoia\Arabic;

use Carbon\CarbonInterface;

/**
 * Jordanian date conventions (design system §5): dd/mm/yyyy in tables, "17 تشرين الأول 2026" in documents.
 */
class ArabicDates
{
    public const SHORT_FORMAT = 'd/m/Y';

    public const SHORT_DATETIME_FORMAT = 'd/m/Y H:i';

    /**
     * Carbon locale used for Arabic: Levantine month names (كانون الثاني … كانون الأول), F13.
     */
    public const CARBON_LOCALE = 'ar_JO';

    public static function long(CarbonInterface $date, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $date->copy()->locale($locale === 'ar' ? static::CARBON_LOCALE : $locale)->translatedFormat('j F Y');
    }

    public static function short(CarbonInterface $date): string
    {
        return $date->format(static::SHORT_FORMAT);
    }
}

<?php

namespace Eunoia\Arabic;

use ArPHP\I18N\Arabic as ArPhp;
use InvalidArgumentException;
use NumberFormatter;

/**
 * Amount in words (تفقيط) for invoices and receipts — AR-05.
 * ar-php spells the integers; this class applies Arabic noun-count agreement for each currency unit.
 */
class AmountInWords
{
    /**
     * Unit forms: singular, dual, plural (3–10), accusative singular (11–99), feminine noun?
     *
     * @var array<string, array{decimals: int, major: array{0: string, 1: string, 2: string, 3: string, 4: bool}, minor: array{0: string, 1: string, 2: string, 3: string, 4: bool}, en: array{0: string, 1: string}}>
     */
    protected const CURRENCIES = [
        'JOD' => [
            'decimals' => 3,
            'major'    => ['دينار', 'ديناران', 'دنانير', 'ديناراً', false],
            'minor'    => ['فلس', 'فلسان', 'فلوس', 'فلساً', false],
            'en'       => ['Jordanian dinars', 'fils'],
        ],
        'ILS' => [
            'decimals' => 2,
            'major'    => ['شيكل', 'شيكلان', 'شواكل', 'شيكلاً', false],
            'minor'    => ['أغورة', 'أغورتان', 'أغورات', 'أغورة', true],
            'en'       => ['shekels', 'agorot'],
        ],
        'USD' => [
            'decimals' => 2,
            'major'    => ['دولار', 'دولاران', 'دولارات', 'دولاراً', false],
            'minor'    => ['سنت', 'سنتان', 'سنتات', 'سنتاً', false],
            'en'       => ['US dollars', 'cents'],
        ],
    ];

    public static function arabic(float $amount, string $currency): string
    {
        $config = static::config($currency);
        [$major, $minor] = static::split(abs($amount), $config['decimals']);

        $parts = [];

        if ($major > 0 || $minor === 0) {
            $parts[] = static::count($major, $config['major']);
        }

        if ($minor > 0) {
            $parts[] = static::count($minor, $config['minor']);
        }

        $words = implode(' و', $parts);

        return 'فقط '.($amount < 0 ? 'سالب ' : '').$words.' لا غير';
    }

    public static function english(float $amount, string $currency): string
    {
        $config = static::config($currency);
        [$major, $minor] = static::split(abs($amount), $config['decimals']);
        $speller = new NumberFormatter('en', NumberFormatter::SPELLOUT);

        $text = $speller->format($major).' '.$config['en'][0];

        if ($minor > 0) {
            $text .= ' and '.$speller->format($minor).' '.$config['en'][1];
        }

        return ($amount < 0 ? 'Minus ' : '').ucfirst($text).' only';
    }

    /**
     * @return array{decimals: int, major: array<int, string|bool>, minor: array<int, string|bool>, en: array<int, string>}
     */
    protected static function config(string $currency): array
    {
        return static::CURRENCIES[strtoupper($currency)]
            ?? throw new InvalidArgumentException("Amount in words: unsupported currency {$currency}.");
    }

    /**
     * @return array{0: int, 1: int} whole units and minor units, rounded half-up to the currency precision
     */
    protected static function split(float $amount, int $decimals): array
    {
        $factor = 10 ** $decimals;
        $total = (int) round($amount * $factor, 0, PHP_ROUND_HALF_UP);

        return [intdiv($total, $factor), $total % $factor];
    }

    /**
     * @param  array<int, string|bool>  $unit  [singular, dual, plural, accusative, feminine]
     */
    protected static function count(int $number, array $unit): string
    {
        [$singular, $dual, $plural, $accusative, $feminine] = $unit;

        if ($number === 0) {
            return 'صفر '.$singular;
        }

        if ($number === 1) {
            return $singular.' '.($feminine ? 'واحدة' : 'واحد');
        }

        if ($number === 2) {
            return $dual;
        }

        $spelled = static::spell($number, $feminine);
        $lastTwo = $number % 100;

        return match (true) {
            $lastTwo >= 3 && $lastTwo <= 10  => "{$spelled} {$plural}",
            $lastTwo >= 11                   => "{$spelled} {$accusative}",
            default                          => "{$spelled} {$singular}",
        };
    }

    protected static function spell(int $number, bool $feminineNoun): string
    {
        $arabic = new ArPhp;
        $arabic->setNumberFeminine($feminineNoun ? 2 : 1);

        /** Prefer "مائة" (common on Jordanian invoices) over "مئة". */
        return str_replace(['مئتان', 'مئتا', 'مئة'], ['مائتان', 'مائتا', 'مائة'], trim($arabic->int2str($number)));
    }
}

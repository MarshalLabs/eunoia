<?php

namespace Eunoia\Core\Support;

/**
 * Phone numbers in E.164 for Jordan (+962) and Palestine (+970) — JO-08.
 * Accepts local input ("0791234567", "079 123 4567", "791234567", "06 5xxxxxx", "00962…").
 */
class PhoneNumber
{
    /**
     * @var array<string, string>
     */
    public const CALLING_CODES = ['JO' => '962', 'PS' => '970'];

    public static function toE164(?string $input, string $country = 'JO'): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $input);
        $digits = preg_replace('/^00/', '+', (string) $digits);

        if (str_starts_with($digits, '+')) {
            return '+'.preg_replace('/\D/', '', $digits);
        }

        $code = static::CALLING_CODES[strtoupper($country)] ?? static::CALLING_CODES['JO'];

        if (str_starts_with($digits, $code) && strlen($digits) >= strlen($code) + 8) {
            return '+'.$digits;
        }

        return '+'.$code.ltrim($digits, '0');
    }

    /**
     * Local display, e.g. "+962791234567" → "079 123 4567".
     */
    public static function display(?string $e164): ?string
    {
        if (! $e164) {
            return null;
        }

        foreach (static::CALLING_CODES as $code) {
            if (str_starts_with($e164, '+'.$code)) {
                $local = '0'.substr($e164, strlen($code) + 1);

                return strlen($local) === 10
                    ? substr($local, 0, 3).' '.substr($local, 3, 3).' '.substr($local, 6)
                    : $local;
            }
        }

        return $e164;
    }
}

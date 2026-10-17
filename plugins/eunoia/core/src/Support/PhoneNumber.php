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

        /** Extensions ("ext 12", "x12", "#12") and short codes ("911") are left as typed. */
        if (preg_match('/(ext|x|#)\s*\d+\s*$/i', $input)) {
            return trim($input);
        }

        $digits = (string) preg_replace('/\(0\)/', '', $input);
        $digits = (string) preg_replace('/[^\d+]/', '', $digits);
        $digits = (string) preg_replace('/^00/', '+', $digits);

        if (strlen(ltrim($digits, '+')) < 7) {
            return trim($input);
        }

        if (str_starts_with($digits, '+')) {
            $digits = '+'.preg_replace('/\D/', '', $digits);

            foreach (static::CALLING_CODES as $code) {
                if (str_starts_with($digits, '+'.$code.'0')) {
                    return '+'.$code.substr($digits, strlen($code) + 2);
                }
            }

            return $digits;
        }

        $code = static::CALLING_CODES[strtoupper($country)] ?? static::CALLING_CODES['JO'];

        foreach (static::CALLING_CODES as $knownCode) {
            if (str_starts_with($digits, $knownCode) && in_array(strlen($digits) - strlen($knownCode), [8, 9], true)) {
                return '+'.$digits;
            }
        }

        $national = ltrim($digits, '0');

        /** A national number is 8 (landline) or 9 (mobile) digits; anything else is probably international. */
        if (! in_array(strlen($national), [8, 9], true)) {
            return '+'.$national;
        }

        /** 059x / 056x are Palestinian mobile prefixes (Jawwal, Ooredoo); Jordanian mobiles are 077/078/079. */
        if (strlen($national) === 9 && preg_match('/^5[69]/', $national)) {
            return '+'.static::CALLING_CODES['PS'].$national;
        }

        return '+'.$code.$national;
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

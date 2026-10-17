<?php

namespace Eunoia\Arabic;

/**
 * Latin digits by default (common in Jordanian business), Arabic-Indic on request — AR-07.
 */
class Digits
{
    public static function format(string $text): string
    {
        return config('eunoia-arabic.digits', 'latin') === 'arabic' ? static::toArabicIndic($text) : $text;
    }

    public static function toArabicIndic(string $text): string
    {
        $text = preg_replace('/(?<=\d)\.(?=\d)/', '٫', $text);

        return strtr($text, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }
}

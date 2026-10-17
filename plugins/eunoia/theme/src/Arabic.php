<?php

namespace Eunoia\Theme;

class Arabic
{
    public static function isArabic(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }
}

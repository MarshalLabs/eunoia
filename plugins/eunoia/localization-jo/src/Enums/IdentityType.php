<?php

namespace Eunoia\LocalizationJo\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Buyer/partner identification for JoFotara (JO-06, F22).
 */
enum IdentityType: string implements HasLabel
{
    case Tin = 'tin';

    case NationalId = 'nin';

    case CommercialRegistration = 'cr';

    case Passport = 'passport';

    public function getLabel(): string
    {
        return __('eunoia-localization-jo::identities.types.'.$this->value);
    }

    /**
     * Validation rule (regex) for Jordanian values. TIN length 8–12 per JoFotara guides (confirm with ISTD).
     */
    public function pattern(): string
    {
        return match ($this) {
            self::Tin                    => '/^\d{8,12}$/',
            self::NationalId             => '/^\d{10}$/',
            self::CommercialRegistration => '/^[0-9A-Za-z\/\-]{1,20}$/',
            self::Passport               => '/^[0-9A-Za-z]{6,20}$/',
        };
    }

    public function isValid(string $value): bool
    {
        return preg_match($this->pattern(), self::clean($value)) === 1;
    }

    public static function clean(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', strtr($value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
    }
}

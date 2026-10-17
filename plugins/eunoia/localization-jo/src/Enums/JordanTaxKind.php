<?php

namespace Eunoia\LocalizationJo\Enums;

/**
 * Jordanian General Sales Tax kinds (JO-02). Reduced-rate list NEEDS ACCOUNTANT (open question Q2).
 */
enum JordanTaxKind: string
{
    case General = 'general';

    case Reduced4 = 'reduced_4';

    case Reduced1 = 'reduced_1';

    case ZeroRated = 'zero_rated';

    case Exempt = 'exempt';

    case Special = 'special';

    public function rate(): float
    {
        return match ($this) {
            self::General                                => 16.0,
            self::Reduced4                               => 4.0,
            self::Reduced1                               => 1.0,
            self::ZeroRated, self::Exempt, self::Special => 0.0,
        };
    }

    public function category(): JoFotaraTaxCategory
    {
        return match ($this) {
            self::ZeroRated => JoFotaraTaxCategory::ZeroRated,
            self::Exempt    => JoFotaraTaxCategory::Exempt,
            default         => JoFotaraTaxCategory::Standard,
        };
    }

    public function isActiveByDefault(): bool
    {
        return $this !== self::Special;
    }

    public function nameAr(): string
    {
        return match ($this) {
            self::General   => 'ضريبة المبيعات العامة 16%',
            self::Reduced4  => 'ضريبة المبيعات العامة المخفضة 4%',
            self::Reduced1  => 'ضريبة المبيعات العامة المخفضة 1%',
            self::ZeroRated => 'ضريبة المبيعات العامة 0% (خاضع بنسبة صفر)',
            self::Exempt    => 'معفى من ضريبة المبيعات العامة',
            self::Special   => 'ضريبة المبيعات الخاصة',
        };
    }

    public function nameEn(): string
    {
        return match ($this) {
            self::General   => 'General Sales Tax 16%',
            self::Reduced4  => 'General Sales Tax reduced 4%',
            self::Reduced1  => 'General Sales Tax reduced 1%',
            self::ZeroRated => 'General Sales Tax 0% (zero-rated)',
            self::Exempt    => 'Exempt from General Sales Tax',
            self::Special   => 'Special Sales Tax',
        };
    }
}

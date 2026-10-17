<?php

namespace Eunoia\JoFotara\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The seller's ISTD registration ("track") — third digit of the JoFotara invoice type code.
 */
enum SellerRegistration: string implements HasLabel
{
    case Income = 'income';

    case GeneralSales = 'general_sales';

    case SpecialSales = 'special_sales';

    public function digit(): string
    {
        return match ($this) {
            self::Income       => '1',
            self::GeneralSales => '2',
            self::SpecialSales => '3',
        };
    }

    public function getLabel(): string
    {
        return __('eunoia-jofotara::jofotara.registration.'.$this->value);
    }
}

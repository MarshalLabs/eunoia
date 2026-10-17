<?php

namespace Eunoia\LocalizationJo\Enums;

/**
 * JoFotara tax category codes. Note: JoFotara's O/Z meaning is the opposite of Peppol's.
 */
enum JoFotaraTaxCategory: string
{
    case Standard = 'S';

    case ZeroRated = 'O';

    case Exempt = 'Z';
}

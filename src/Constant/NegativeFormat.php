<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum NegativeFormat: string
{
    case F_24X36 = '24x36';
    case F_6X45 = '6x45';
    case F_6X6 = '6x6';
    case F_6X7 = '6x7';
    case F_6X9 = '6x9';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}

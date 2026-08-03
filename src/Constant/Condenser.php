<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum Condenser: string
{
    case BIMACON_75 = 'bimacon_75';
    case BIMACON_80 = 'bimacon_80';
    case FEMOCON_50 = 'femocon_50';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}

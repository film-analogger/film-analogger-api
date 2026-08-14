<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

/**
 * Standard darkroom print sizes, in centimetres — matches the units used by
 * PrintWork::$paperWidthCm/$paperHeightCm.
 */
enum PaperFormat: string
{
    case F_9X13 = '9x13';
    case F_10X15 = '10x15';
    case F_13X18 = '13x18';
    case F_18X24 = '18x24';
    case F_20X25 = '20x25';
    case F_24X30 = '24x30';
    case F_30X40 = '30x40';
    case F_40X50 = '40x50';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}

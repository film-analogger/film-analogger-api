<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum ExposureKind: string
{
    case BASE = 'base';
    case BURN = 'burn';
    case DODGE = 'dodge';
}

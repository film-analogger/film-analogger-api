<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum PaperBrand: string
{
    case ILFORD = 'ilford';
    case FOMA = 'foma';
    case BERGGER = 'bergger';
    case OTHER = 'other';
}

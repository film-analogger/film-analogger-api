<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum PaperSurface: string
{
    case GLOSSY = 'glossy';
    case SATIN = 'satin';
    case PEARL = 'pearl';
    case MATT = 'matt';
    case OTHER = 'other';
}

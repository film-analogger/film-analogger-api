<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

enum CatalogStatus: string
{
    case PERSONAL = 'personal';
    case PENDING = 'pending';
    case OFFICIAL = 'official';
    case REJECTED = 'rejected';
}

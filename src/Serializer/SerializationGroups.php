<?php

namespace FilmAnalogger\FilmAnaloggerApi\Serializer;

final class SerializationGroups
{
    const FILM_READ_GROUP = 'read-film';
    const FILM_WRITE_GROUP = 'write-film';

    const MANUFACTURER_READ_GROUP = 'read-manufacturer';
    const MANUFACTURER_WRITE_GROUP = 'write-manufacturer';

    const CHEMISTRY_READ_GROUP = 'read-chemistry';
    const CHEMISTRY_WRITE_GROUP = 'write-chemistry';

    const CHEMISTRY_TYPE_READ_GROUP = 'read-chemistry-type';
    const CHEMISTRY_TYPE_WRITE_GROUP = 'write-chemistry-type';

    const TRANSLATABLE_READ_GROUP = 'translatable-read';

    const TIMESTAMPABLE_BLAMEABLE_READ_GROUP = 'timestampable-blameable-read';
}

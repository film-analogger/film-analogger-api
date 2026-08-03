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

    const CAMERA_READ_GROUP = 'read-camera';
    const CAMERA_WRITE_GROUP = 'write-camera';

    const TAG_READ_GROUP = 'read-tag';
    const TAG_WRITE_GROUP = 'write-tag';

    const DEVELOPMENT_LOG_READ_GROUP = 'read-development-log';
    const DEVELOPMENT_LOG_WRITE_GROUP = 'write-development-log';

    const TRANSLATABLE_READ_GROUP = 'translatable-read';

    const TIMESTAMPABLE_BLAMEABLE_READ_GROUP = 'timestampable-blameable-read';

    const CATALOG_STATUS_READ_GROUP = 'catalog-status-read';
    const CATALOG_STATUS_WRITE_GROUP = 'catalog-status-write';

    const APP_USER_READ_GROUP = 'read-app-user';
    const APP_USER_WRITE_GROUP = 'write-app-user';
    const APP_USER_AVATAR_UPLOAD_GROUP = 'avatar-upload-app-user';

    const PRINT_SESSION_READ_GROUP = 'read-print-session';
    const PRINT_SESSION_WRITE_GROUP = 'write-print-session';
    // Only included on item operations (Get/Post/Patch), not GetCollection,
    // so listing print sessions doesn't trigger one PrintWork query per
    // session (N+1).
    const PRINT_SESSION_ITEM_READ_GROUP = 'read-print-session-item';

    const PRINT_READ_GROUP = 'read-print';
    const PRINT_WRITE_GROUP = 'write-print';
}

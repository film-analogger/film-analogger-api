<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security;

final class KeycloakRoles
{
    public const DATA_READER = 'ROLE_data_reader';
    public const DATA_WRITER = 'ROLE_data_writer';
    public const USER = 'ROLE_user';
    public const ADMIN = 'ROLE_admin';

    public const ALL_ROLES = [self::DATA_READER, self::DATA_WRITER, self::USER, self::ADMIN];
}

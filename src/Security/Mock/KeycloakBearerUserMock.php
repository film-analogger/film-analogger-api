<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Mock;

use FilmAnalogger\FilmAnaloggerApi\Security\User\KeycloakBearerUser;

final class KeycloakBearerUserMock extends KeycloakBearerUser
{
    private array $mockedRoles = [];

    public function __construct(
        array $roles = [],
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'jc.bonnisseurdlb',
        array $attributes = [],
    ) {
        if ('test' !== $_ENV['APP_ENV'] || getEnv('APP_ENV' !== 'test')) {
            throw new \RuntimeException(
                'KeycloakBearerUserMock can only be used in test environment',
            );
        }

        $this->mockedRoles = $roles;
        parent::__construct(
            $sub,
            $name,
            $email,
            $given_name,
            $family_name,
            $preferred_username,
            $attributes,
            [
                'realm_access' => [],
                'resource_access' => [],
                'external_resource_access' => [],
            ],
            '',
        );
    }

    public function getRoles(): array
    {
        return $this->mockedRoles;
    }

    public function getPassword(): ?string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function getSalt(): ?string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function eraseCredentials(): void {}
}

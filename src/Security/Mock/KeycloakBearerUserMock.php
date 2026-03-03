<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Mock;

use FilmAnalogger\FilmAnaloggerApi\Security\User\KeycloakBearerUser;

final class KeycloakBearerUserMock extends KeycloakBearerUser
{
    private array $mockedRoles = [];

    public function __construct(array $roles = [], array $attributes = [])
    {
        if ('test' !== $_ENV['APP_ENV'] || getEnv('APP_ENV' !== 'test')) {
            throw new \RuntimeException(
                'KeycloakBearerUserMock can only be used in test environment',
            );
        }

        $this->mockedRoles = $roles;
        parent::__construct(
            '',
            '',
            '',
            '',
            '',
            '',
            $attributes,
            [
                'realm_access' => [],
                'resource_access' => [],
                'external_resource_access' => [],
            ],
            '',
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
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

    public function getUsername(): string
    {
        return 'test_user';
    }
}

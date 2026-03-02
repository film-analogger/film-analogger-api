<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Mock;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class KeycloakBearerUserProviderMock implements UserProviderInterface
{
    private static ?KeycloakBearerUserMock $currentUser = null;

    public static function setCurrentUser(?KeycloakBearerUserMock $user): void
    {
        self::$currentUser = $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return KeycloakBearerUserMock::class === $class;
    }

    public function loadUserByIdentifier(string $accessToken): UserInterface
    {
        return self::$currentUser ?? new KeycloakBearerUserMock();
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}

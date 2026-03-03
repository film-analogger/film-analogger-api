<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Mock;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class KeycloakBearerUserProviderMock implements UserProviderInterface
{
    public function __construct(private LoggerInterface $logger)
    {
        if ('test' !== $_ENV['APP_ENV'] ?? null) {
            throw new \RuntimeException(
                'KeycloakBearerUserProviderMock can only be used in test environment',
            );
        }
    }

    private static ?KeycloakBearerUserMock $currentUser = null;

    public static function setCurrentUser(?KeycloakBearerUserMock $user): void
    {
        self::$currentUser = $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $this->logger->debug('refreshUser called with user: ', ['user' => $user]);
        self::setCurrentUser($user);
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return KeycloakBearerUserMock::class === $class;
    }

    public function loadUserByIdentifier(string $accessToken): UserInterface
    {
        $this->logger->debug('loadUserByIdentifier current user: ', ['user' => self::$currentUser]);
        return self::$currentUser ?? new KeycloakBearerUserMock();
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}

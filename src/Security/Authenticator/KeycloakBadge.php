<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Authenticator;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\EventListener\UserProviderListener;

/**
 * Represents the user in the authentication process.
 *
 * It uses an identifier (e.g. email, or username) and
 * "user loader" to load the related User object.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class KeycloakBadge extends UserBadge
{
    public const MAX_USERNAME_LENGTH = 12288;
    private string $userIdentifier;
    /** @var callable|null */
    private $userLoader;
    private ?array $attributes;
    private ?UserInterface $user = null;

    public function __construct(
        string $userIdentifier,
        ?callable $userLoader = null,
        ?array $attributes = null,
    ) {
        if (\strlen($userIdentifier) > self::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Username too long.');
        }

        $this->userIdentifier = $userIdentifier;
        $this->userLoader = $userLoader;
        $this->attributes = $attributes;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @throws AuthenticationException when the user cannot be found
     */
    public function getUser(): UserInterface
    {
        if (isset($this->user)) {
            return $this->user;
        }

        if (null === $this->userLoader) {
            throw new \LogicException(
                sprintf(
                    'No user loader is configured, did you forget to register the "%s" listener?',
                    UserProviderListener::class,
                ),
            );
        }

        if (null === $this->getAttributes()) {
            $user = ($this->userLoader)($this->userIdentifier);
        } else {
            $user = ($this->userLoader)($this->userIdentifier, $this->getAttributes());
        }

        // No user has been found via the $this->userLoader callback
        if (null === $user) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($this->userIdentifier);

            throw $exception;
        }

        if (!$user instanceof UserInterface) {
            throw new AuthenticationServiceException(
                sprintf(
                    'The user provider must return a UserInterface object, "%s" given.',
                    get_debug_type($user),
                ),
            );
        }

        return $this->user = $user;
    }

    public function getUserLoader(): ?callable
    {
        return $this->userLoader;
    }

    public function setUserLoader(callable $userLoader): void
    {
        $this->userLoader = $userLoader;
    }

    public function isResolved(): bool
    {
        return true;
    }
}

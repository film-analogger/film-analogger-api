<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security;

use FilmAnalogger\FilmAnaloggerApi\Security\User\KeycloakBearerUser;
use FilmAnalogger\FilmAnaloggerApi\Security\OAuthClient\KeycloakClient;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakBearerUserProvider implements UserProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private readonly KeycloakClient $keycloakClient,
        private string $clientId,
    ) {}

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API, which is our case), this
     * method is not called. But it is implement it anyway.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof KeycloakBearerUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user)),
            );
        }

        $user = $this->loadUserByIdentifier($user->getAccessToken());

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return KeycloakBearerUser::class === $class;
    }

    /**
     * @param string $accessToken
     * @return KeycloakBearerUser&UserInterface
     */
    public function loadUserByIdentifier(string $accessToken): KeycloakBearerUser&UserInterface
    {
        try {
            $this->logger->info('decode token');
            $jwt = JWT::decode(
                $accessToken,
                JWK::parseKeySet($this->keycloakClient->fetchIssuerKeys()),
            );

            /** TODO:
             * Add test and test that :
             * After decoding/signature verification, the token is accepted without validating standard claims
             * like iss (issuer) and aud/azp (audience/authorized party) against the configured realm/client.
             * Since realm keys sign tokens for multiple clients, please validate these claims (and fail authentication)
             * to ensure only tokens intended for this API are accepted.
             */
        } catch (\InvalidArgumentException | \DomainException | \UnexpectedValueException $ex) {
            $this->logger->info('token validation failed', [
                'errorMessage' => $ex->getMessage(),
                'errorType' => get_class($ex),
            ]);
            throw new CustomUserMessageAuthenticationException(
                'The token does not exist, is malformed, invalid or expired',
            );
        } catch (ClientException | ServerException $ex) {
            $this->logger->error($ex->getMessage());
            throw new CustomUserMessageAuthenticationException(
                'An error has occurred during auth process, please try again later',
            );
        }

        return new KeycloakBearerUser(
            $jwt->sub,
            $jwt->name ?? '',
            $jwt->email ?? '',
            $jwt->given_name ?? '',
            $jwt->family_name ?? '',
            $jwt->preferred_username ?? '',
            $this->getUserAttributesFromToken($jwt),
            [
                'realm_access' => $jwt->realm_access,
                'resource_access' =>
                    ($jwt->resource_access ?? new \stdClass())->{$this->clientId} ?? [],
                'external_resource_access' => (array) $jwt->resource_access,
            ],
            $accessToken,
        );
    }

    /**
     * @param string $username
     * @return UserInterface
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    private function getUserAttributesFromToken(\stdClass $accessToken): array
    {
        $attributes = isset($accessToken->attributes) ? (array) $accessToken->attributes : [];

        return $attributes;
    }
}

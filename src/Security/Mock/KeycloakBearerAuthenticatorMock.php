<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\Mock;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class KeycloakBearerAuthenticatorMock extends AbstractAuthenticator
{
    public function __construct(private LoggerInterface $logger, private Security $security)
    {
        if ('test' !== $_ENV['APP_ENV'] || getEnv('APP_ENV' !== 'test')) {
            throw new \RuntimeException(
                'KeycloakBearerAuthenticatorMock can only be used in test environment',
            );
        }
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        if (is_null($this->security->getUser())) {
            throw new CustomUserMessageAuthenticationException(
                'Token is not present in the request headers',
            );
        }

        $this->logger->debug('KeycloakBearerAuthenticatorMock authenticate called');
        return new SelfValidatingPassport(new UserBadge('test_user'));
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        $this->logger->debug('KeycloakBearerAuthenticatorMock onAuthenticationSuccess called');
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        $this->logger->debug('KeycloakBearerAuthenticatorMock onAuthenticationFailure called', [
            'exception' => $exception,
        ]);
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}

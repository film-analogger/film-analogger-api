<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\OAuthClient;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KeycloakClient
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache,
        private string $issuer,
        private readonly string $issuer_dev_override,
        private readonly string $realm,
    ) {
        // if env = dev then disable ssl verification for http client
        if (getenv('APP_ENV') === 'dev') {
            $this->issuer = $this->issuer_dev_override;
        }
        $this->httpClient = HttpClient::create();
    }

    public function fetchIssuerKeys(): array
    {
        return $this->cache->get(sprintf('keycloak_keyset_%s', $this->realm), function (
            ItemInterface $item,
        ) {
            $this->logger->info('Fetching Keycloak issuer keys from Keycloak server');
            $item->expiresAfter(\DateInterval::createFromDateString('+30 days'));
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/realms/%s/protocol/openid-connect/certs', $this->issuer, $this->realm),
            );
            return $response->toArray();
        });
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;

class DevelopmentLogSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $developmentLog = $this->createDevelopmentLog();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/development_logs'],
                ['GET', '/development_logs/' . $developmentLog->getId()],
                ['PATCH', '/development_logs/' . $developmentLog->getId()],
                ['DELETE', '/development_logs/' . $developmentLog->getId()],
                ['POST', '/development_logs'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $film = $this->createFilm();

        $client = self::loggedClientDataReader();
        $client->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'film' => '/films/' . $film->getId(),
                'shotAt' => ['year' => 2024],
                'isoShotAt' => $film->getSensibility(),
                'process' => $film->getProcess(),
                'developedAt' => '2024-06-15',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Creates a log as 'owner_writer'. Pass an existing client to reuse the
     * same authenticated session (creating a fresh client via
     * loggedClientDataWriter() each time would silently become the "current"
     * client that the parameterless assert*() helpers read from, breaking
     * assertions made against an earlier client instance).
     */
    private function createLogAsOwner(?Client $client = null): array
    {
        $film = $this->createFilm();

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'film' => '/films/' . $film->getId(),
                'shotAt' => ['year' => 2024, 'month' => 6],
                'isoShotAt' => $film->getSensibility(),
                'process' => $film->getProcess(),
                'developedAt' => '2024-06-15',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnLog(): void
    {
        [$ownerClient, $id] = $this->createLogAsOwner();

        $ownerClient->request('GET', '/development_logs/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/development_logs/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['rating' => 5],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesLog(): void
    {
        [, $id] = $this->createLogAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/development_logs/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/development_logs/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['rating' => 1],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/development_logs/' . $id);
    }

    public function testAdminCanAccessAnyLog(): void
    {
        [, $id] = $this->createLogAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/development_logs/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/development_logs/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createLogAsOwner($ownerClient);
        $this->createLogAsOwner($ownerClient);

        $ownerClient->request('GET', '/development_logs');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/development_logs');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/development_logs');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PrintSessionSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $session = $this->createPrintSession();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/print_sessions'],
                ['GET', '/print_sessions/' . $session->getId()],
                ['PATCH', '/print_sessions/' . $session->getId()],
                ['DELETE', '/print_sessions/' . $session->getId()],
                ['POST', '/print_sessions'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    /**
     * Creates a session as 'owner_writer'. Pass an existing client to reuse
     * the same authenticated session (creating a fresh client via
     * loggedClientDataWriter() each time would silently become the "current"
     * client that the parametorless assert*() helpers read from, breaking
     * assertions made against an earlier client instance).
     */
    private function createSessionAsOwner(?Client $client = null, int $number = 1): array
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER');
        $stopBath = $this->createPaperChemistry('STOP');
        $fixer = $this->createPaperChemistry('FIXER');

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'date' => '2026-01-15',
                'lab' => 'Garage',
                'number' => $number,
                'enlarger' => 'Durst M805',
                'temperatureCelsius' => 20.0,
                'chemicalBaths' => [
                    ['chemistry' => '/chemistries/' . $developer->getId(), 'durationSeconds' => 60],
                    ['chemistry' => '/chemistries/' . $stopBath->getId(), 'durationSeconds' => 30],
                    ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnSession(): void
    {
        [$ownerClient, $id] = $this->createSessionAsOwner();

        $ownerClient->request('GET', '/print_sessions/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/print_sessions/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['lab' => 'Salle de bain'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesSession(): void
    {
        [, $id] = $this->createSessionAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/print_sessions/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/print_sessions/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['lab' => 'Hack'],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/print_sessions/' . $id);
    }

    public function testAdminCanAccessAnySession(): void
    {
        [, $id] = $this->createSessionAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/print_sessions/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/print_sessions/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createSessionAsOwner($ownerClient, 1);
        $this->createSessionAsOwner($ownerClient, 2);

        $ownerClient->request('GET', '/print_sessions');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/print_sessions');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/print_sessions');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

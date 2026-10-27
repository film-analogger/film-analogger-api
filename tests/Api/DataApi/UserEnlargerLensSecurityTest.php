<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserEnlargerLensSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $userEnlargerLens = $this->createUserEnlargerLens();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/user_enlarger_lenses'],
                ['GET', '/user_enlarger_lenses/' . $userEnlargerLens->getId()],
                ['PATCH', '/user_enlarger_lenses/' . $userEnlargerLens->getId()],
                ['DELETE', '/user_enlarger_lenses/' . $userEnlargerLens->getId()],
                ['POST', '/user_enlarger_lenses'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $enlargerLens = $this->createEnlargerLens();

        $client = self::loggedClientDataReader();
        $client->request('POST', '/user_enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlargerLens' => '/enlarger_lenses/' . $enlargerLens->getId()],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createUserEnlargerLensAsOwner(?Client $client = null): array
    {
        $enlargerLens = $this->createEnlargerLens();

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/user_enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlargerLens' => '/enlarger_lenses/' . $enlargerLens->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnUserEnlargerLens(): void
    {
        [$ownerClient, $id] = $this->createUserEnlargerLensAsOwner();

        $ownerClient->request('GET', '/user_enlarger_lenses/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/user_enlarger_lenses/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 3],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesUserEnlargerLens(): void
    {
        [, $id] = $this->createUserEnlargerLensAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/user_enlarger_lenses/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/user_enlarger_lenses/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 1],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/user_enlarger_lenses/' . $id);
    }

    public function testAdminCanAccessAnyUserEnlargerLens(): void
    {
        [, $id] = $this->createUserEnlargerLensAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_enlarger_lenses/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/user_enlarger_lenses/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createUserEnlargerLensAsOwner($ownerClient);
        $this->createUserEnlargerLensAsOwner($ownerClient);

        $ownerClient->request('GET', '/user_enlarger_lenses');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/user_enlarger_lenses');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_enlarger_lenses');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

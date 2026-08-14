<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserEnlargerSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $userEnlarger = $this->createUserEnlarger();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/user_enlargers'],
                ['GET', '/user_enlargers/' . $userEnlarger->getId()],
                ['PATCH', '/user_enlargers/' . $userEnlarger->getId()],
                ['DELETE', '/user_enlargers/' . $userEnlarger->getId()],
                ['POST', '/user_enlargers'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $enlarger = $this->createEnlarger();

        $client = self::loggedClientDataReader();
        $client->request('POST', '/user_enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlarger' => '/enlargers/' . $enlarger->getId()],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createUserEnlargerAsOwner(?Client $client = null): array
    {
        $enlarger = $this->createEnlarger();

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/user_enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlarger' => '/enlargers/' . $enlarger->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnUserEnlarger(): void
    {
        [$ownerClient, $id] = $this->createUserEnlargerAsOwner();

        $ownerClient->request('GET', '/user_enlargers/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/user_enlargers/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 3],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesUserEnlarger(): void
    {
        [, $id] = $this->createUserEnlargerAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/user_enlargers/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/user_enlargers/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 1],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/user_enlargers/' . $id);
    }

    public function testAdminCanAccessAnyUserEnlarger(): void
    {
        [, $id] = $this->createUserEnlargerAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_enlargers/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/user_enlargers/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createUserEnlargerAsOwner($ownerClient);
        $this->createUserEnlargerAsOwner($ownerClient);

        $ownerClient->request('GET', '/user_enlargers');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/user_enlargers');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_enlargers');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserCameraSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $userCamera = $this->createUserCamera();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/user_cameras'],
                ['GET', '/user_cameras/' . $userCamera->getId()],
                ['PATCH', '/user_cameras/' . $userCamera->getId()],
                ['DELETE', '/user_cameras/' . $userCamera->getId()],
                ['POST', '/user_cameras'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $camera = $this->createCamera();

        $client = self::loggedClientDataReader();
        $client->request('POST', '/user_cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['camera' => '/cameras/' . $camera->getId()],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createUserCameraAsOwner(?Client $client = null): array
    {
        $camera = $this->createCamera();

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/user_cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['camera' => '/cameras/' . $camera->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnUserCamera(): void
    {
        [$ownerClient, $id] = $this->createUserCameraAsOwner();

        $ownerClient->request('GET', '/user_cameras/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/user_cameras/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 3],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesUserCamera(): void
    {
        [, $id] = $this->createUserCameraAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/user_cameras/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/user_cameras/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 1],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/user_cameras/' . $id);
    }

    public function testAdminCanAccessAnyUserCamera(): void
    {
        [, $id] = $this->createUserCameraAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_cameras/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/user_cameras/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createUserCameraAsOwner($ownerClient);
        $this->createUserCameraAsOwner($ownerClient);

        $ownerClient->request('GET', '/user_cameras');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/user_cameras');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/user_cameras');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

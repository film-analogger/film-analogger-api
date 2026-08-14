<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserCameraTest extends AbstractFilmTestCase
{
    public function testCreateUserCamera(): void
    {
        $camera = $this->createCamera(['name' => 'F100']);

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'camera' => '/cameras/' . $camera->getId(),
                'quantity' => 2,
                'acquiredAt' => '2026-01-15',
                'notes' => 'Bought used, works great.',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(2, $data['quantity']);
        $this->assertSame('2026-01-15', substr($data['acquiredAt'], 0, 10));
        $this->assertSame('test_user_admin', $data['createdBy']);
    }

    public function testCreateUserCameraDefaultsQuantityToOne(): void
    {
        $camera = $this->createCamera();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['camera' => '/cameras/' . $camera->getId()],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(1, $response->toArray()['quantity']);
    }

    public function testCreateUserCameraWithoutCameraFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/user_cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetUserCameraDeepEmbedsCamera(): void
    {
        $manufacturer = $this->createManufacturer('Nikon');
        $camera = $this->createCamera(['name' => 'F100', 'manufacturer' => $manufacturer]);
        $userCamera = $this->createUserCamera(['camera' => $camera, 'createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_cameras/' . $userCamera->getId());
        $data = $response->toArray();

        $this->assertSame('F100', $data['camera']['name']);
        $this->assertSame('Nikon', $data['camera']['manufacturer']['name']);
    }

    public function testGetCollectionDoesNotDeepEmbedCamera(): void
    {
        $this->createUserCamera(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_cameras');
        $data = $response->toArray()['hydra:member'][0];

        $this->assertArrayNotHasKey('name', $data['camera']);
    }
}

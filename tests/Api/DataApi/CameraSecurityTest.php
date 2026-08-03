<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class CameraSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $camera = $this->createCamera();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/cameras'],
                ['GET', '/cameras/' . $camera->getId()],
                ['PATCH', '/cameras/' . $camera->getId()],
                ['DELETE', '/cameras/' . $camera->getId()],
                ['POST', '/cameras'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertCameraSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertCameraSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertCameraSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'AE-1', 'manufacturer' => '/manufacturers/' . $manufacturer->getId()],
        ]);
    }

    public function testUserCannotWriteOfficialCameraOfSomeoneElse(): void
    {
        $camera = $this->createCamera(['status' => CatalogStatus::OFFICIAL, 'createdBy' => 'other_user']);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/cameras/' . $camera->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/cameras/' . $camera->getId());
    }

    public function testUserCanCreatePersonalCameraByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Pinhole',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialCameraDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Pinhole',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalCameraAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $camera = $this->createCamera(['status' => CatalogStatus::PERSONAL, 'createdBy' => 'plain_user']);

        $client->request('PATCH', '/cameras/' . $camera->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalCamera(): void
    {
        $camera = $this->createCamera(['status' => CatalogStatus::PERSONAL, 'createdBy' => 'other_user']);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/cameras/' . $camera->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $camera = $this->createCamera(['status' => CatalogStatus::PENDING, 'createdBy' => 'plain_user']);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/cameras?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/cameras/' . $camera->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertCameraSecurityByRole($client, bool $canWrite): void
    {
        $camera = $this->createCamera();
        $manufacturer = $this->createManufacturer();

        $this->assertSuccessfulStatus($client, 'GET', '/cameras', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/cameras/' . $camera->getId(), 200);

        $patchCameraOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'F100 Updated',
            ],
        ];

        $postCameraOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'AE-1',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/cameras/' . $camera->getId(),
                200,
                $patchCameraOptions,
            );
            $this->assertSuccessfulStatus($client, 'DELETE', '/cameras/' . $camera->getId(), 204);
            $this->assertSuccessfulStatus($client, 'POST', '/cameras', 201, $postCameraOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/cameras/' . $camera->getId(),
            $patchCameraOptions,
        );
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/cameras/' . $camera->getId());
        $this->assertForbiddenAccessDenied($client, 'POST', '/cameras', $postCameraOptions);
    }
}

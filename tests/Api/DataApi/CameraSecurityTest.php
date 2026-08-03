<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

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

    public function testUserCanReadDataOnly(): void
    {
        $this->assertCameraSecurityByRole(self::loggedClientUser(), false);
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

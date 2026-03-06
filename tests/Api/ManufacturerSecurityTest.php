<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ManufacturerSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/manufacturers'],
                ['GET', '/manufacturers/' . $manufacturer->getId()],
                ['PATCH', '/manufacturers/' . $manufacturer->getId()],
                ['DELETE', '/manufacturers/' . $manufacturer->getId()],
                ['POST', '/manufacturers'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertManufacturerSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertManufacturerSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertManufacturerSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testUserCanReadDataOnly(): void
    {
        $this->assertManufacturerSecurityByRole(self::loggedClientUser(), false);
    }

    private function assertManufacturerSecurityByRole($client, bool $canWrite): void
    {
        $manufacturer = $this->createManufacturer();

        $this->assertSuccessfulStatus($client, 'GET', '/manufacturers', 200);
        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/manufacturers/' . $manufacturer->getId(),
            200,
        );

        $patchOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Kodak Alaris',
            ],
        ];

        $postOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Fujifilm',
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/manufacturers/' . $manufacturer->getId(),
                200,
                $patchOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/manufacturers/' . $manufacturer->getId(),
                204,
            );
            $this->assertSuccessfulStatus($client, 'POST', '/manufacturers', 201, $postOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/manufacturers/' . $manufacturer->getId(),
            $patchOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/manufacturers/' . $manufacturer->getId(),
        );
        $this->assertForbiddenAccessDenied($client, 'POST', '/manufacturers', $postOptions);
    }
}

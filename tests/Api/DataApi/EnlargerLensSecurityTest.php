<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class EnlargerLensSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $enlargerLens = $this->createEnlargerLens();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/enlarger_lenses'],
                ['GET', '/enlarger_lenses/' . $enlargerLens->getId()],
                ['PATCH', '/enlarger_lenses/' . $enlargerLens->getId()],
                ['DELETE', '/enlarger_lenses/' . $enlargerLens->getId()],
                ['POST', '/enlarger_lenses'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertEnlargerLensSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertEnlargerLensSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertEnlargerLensSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Rodagon 50mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 2.8,
            ],
        ]);
    }

    public function testUserCannotWriteOfficialEnlargerLensOfSomeoneElse(): void
    {
        $enlargerLens = $this->createEnlargerLens([
            'status' => CatalogStatus::OFFICIAL,
            'createdBy' => 'other_user',
        ]);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/enlarger_lenses/' . $enlargerLens->getId(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['name' => 'Renamed'],
            ],
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/enlarger_lenses/' . $enlargerLens->getId(),
        );
    }

    public function testUserCanCreatePersonalEnlargerLensByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Lens',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 2.8,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialEnlargerLensDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Lens',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 2.8,
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalEnlargerLensAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $enlargerLens = $this->createEnlargerLens([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);

        $client->request('PATCH', '/enlarger_lenses/' . $enlargerLens->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalEnlargerLens(): void
    {
        $enlargerLens = $this->createEnlargerLens([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied(
            $client,
            'GET',
            '/enlarger_lenses/' . $enlargerLens->getId(),
        );
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $enlargerLens = $this->createEnlargerLens([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/enlarger_lenses?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/enlarger_lenses/' . $enlargerLens->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertEnlargerLensSecurityByRole($client, bool $canWrite): void
    {
        $enlargerLens = $this->createEnlargerLens();
        $manufacturer = $this->createManufacturer();

        $this->assertSuccessfulStatus($client, 'GET', '/enlarger_lenses', 200);
        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/enlarger_lenses/' . $enlargerLens->getId(),
            200,
        );

        $patchEnlargerLensOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm Updated',
            ],
        ];

        $postEnlargerLensOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Rodagon 50mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 2.8,
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/enlarger_lenses/' . $enlargerLens->getId(),
                200,
                $patchEnlargerLensOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/enlarger_lenses/' . $enlargerLens->getId(),
                204,
            );
            $this->assertSuccessfulStatus(
                $client,
                'POST',
                '/enlarger_lenses',
                201,
                $postEnlargerLensOptions,
            );

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/enlarger_lenses/' . $enlargerLens->getId(),
            $patchEnlargerLensOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/enlarger_lenses/' . $enlargerLens->getId(),
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'POST',
            '/enlarger_lenses',
            $postEnlargerLensOptions,
        );
    }
}

<?php
namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

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

    public function testDataReaderAloneCannotCreate(): void
    {
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Fujifilm'],
        ]);
    }

    public function testUserCannotWriteOfficialManufacturerOfSomeoneElse(): void
    {
        $manufacturer = $this->createManufacturer('Kodak', CatalogStatus::OFFICIAL);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/manufacturers/' . $manufacturer->getId());
    }

    public function testUserCanCreatePersonalManufacturerByDefault(): void
    {
        $client = self::loggedClientUser();

        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Homemade Brand'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialManufacturerDirectly(): void
    {
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Homemade Brand', 'status' => 'official'],
        ]);
    }

    public function testUserCanEditOwnPersonalManufacturerAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $manufacturer = $this->createManufacturer('Draft Brand', CatalogStatus::PERSONAL, 'plain_user');

        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalManufacturer(): void
    {
        $manufacturer = $this->createManufacturer(
            'Someone else\'s brand',
            CatalogStatus::PERSONAL,
            'other_user',
        );

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/manufacturers/' . $manufacturer->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $manufacturer = $this->createManufacturer('Draft Brand', CatalogStatus::PENDING, 'plain_user');

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/manufacturers?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
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

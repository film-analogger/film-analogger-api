<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class ChemistrySecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $chemistry = $this->createChemistry();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/chemistries'],
                ['GET', '/chemistries/' . $chemistry->getId()],
                ['PATCH', '/chemistries/' . $chemistry->getId()],
                ['DELETE', '/chemistries/' . $chemistry->getId()],
                ['POST', '/chemistries'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'ID-11',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
    }

    public function testUserCannotWriteOfficialChemistryOfSomeoneElse(): void
    {
        $chemistry = $this->createChemistry([
            'status' => CatalogStatus::OFFICIAL,
            'createdBy' => 'other_user',
        ]);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/chemistries/' . $chemistry->getId());
    }

    public function testUserCanCreatePersonalChemistryByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();
        $client = self::loggedClientUser();

        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Developer',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialChemistryDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Developer',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalChemistryAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $chemistry = $this->createChemistry([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);

        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalChemistry(): void
    {
        $chemistry = $this->createChemistry([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/chemistries/' . $chemistry->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $chemistry = $this->createChemistry([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/chemistries?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertChemistrySecurityByRole($client, bool $canWrite): void
    {
        $chemistry = $this->createChemistry();
        $manufacturer = $this->createManufacturer();
        $chemistryTypeForChemistry = $this->createChemistryType();

        $this->assertSuccessfulStatus($client, 'GET', '/chemistries', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/chemistries/' . $chemistry->getId(), 200);

        $patchChemistryOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'D-76 Updated',
            ],
        ];

        $postChemistryOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'ID-11',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryTypeForChemistry->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/chemistries/' . $chemistry->getId(),
                200,
                $patchChemistryOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/chemistries/' . $chemistry->getId(),
                204,
            );
            $this->assertSuccessfulStatus(
                $client,
                'POST',
                '/chemistries',
                201,
                $postChemistryOptions,
            );

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/chemistries/' . $chemistry->getId(),
            $patchChemistryOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/chemistries/' . $chemistry->getId(),
        );
        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistries', $postChemistryOptions);
    }
}

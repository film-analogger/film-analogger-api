<?php
namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class ChemistryTypeSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/chemistry_types'],
                ['GET', '/chemistry_types/' . $chemistryType->getId()],
                ['PATCH', '/chemistry_types/' . $chemistryType->getId()],
                ['DELETE', '/chemistry_types/' . $chemistryType->getId()],
                ['POST', '/chemistry_types'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['process' => 'B&W', 'typeCode' => 'FIXER', 'typeLabel' => 'Fixer'],
        ]);
    }

    public function testUserCannotWriteOfficialChemistryTypeOfSomeoneElse(): void
    {
        $chemistryType = $this->createChemistryType(
            status: CatalogStatus::OFFICIAL,
            createdBy: 'other_user',
        );
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/chemistry_types/' . $chemistryType->getId(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['typeLabel' => 'Renamed'],
            ],
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/chemistry_types/' . $chemistryType->getId(),
        );
    }

    public function testUserCanCreatePersonalChemistryTypeByDefault(): void
    {
        $client = self::loggedClientUser();

        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['process' => 'B&W', 'typeCode' => 'FIXER', 'typeLabel' => 'Fixer'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialChemistryTypeDirectly(): void
    {
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalChemistryTypeAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $chemistryType = $this->createChemistryType(
            status: CatalogStatus::PERSONAL,
            createdBy: 'plain_user',
        );

        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalChemistryType(): void
    {
        $chemistryType = $this->createChemistryType(
            status: CatalogStatus::PERSONAL,
            createdBy: 'other_user',
        );

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied(
            $client,
            'GET',
            '/chemistry_types/' . $chemistryType->getId(),
        );
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $chemistryType = $this->createChemistryType(
            status: CatalogStatus::PENDING,
            createdBy: 'plain_user',
        );

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/chemistry_types?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertChemistryTypeSecurityByRole($client, bool $canWrite): void
    {
        $chemistryType = $this->createChemistryType('B&W', 'FIXER', 'Fixer');

        $this->assertSuccessfulStatus($client, 'GET', '/chemistry_types', 200);
        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/chemistry_types/' . $chemistryType->getId(),
            200,
        );

        $patchOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'typeLabel' => 'Fixer Updated',
            ],
        ];

        $postOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/chemistry_types/' . $chemistryType->getId(),
                200,
                $patchOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/chemistry_types/' . $chemistryType->getId(),
                204,
            );
            $this->assertSuccessfulStatus($client, 'POST', '/chemistry_types', 201, $postOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/chemistry_types/' . $chemistryType->getId(),
            $patchOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/chemistry_types/' . $chemistryType->getId(),
        );
        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistry_types', $postOptions);
    }
}

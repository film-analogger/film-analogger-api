<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class EnlargerSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $enlarger = $this->createEnlarger();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/enlargers'],
                ['GET', '/enlargers/' . $enlarger->getId()],
                ['PATCH', '/enlargers/' . $enlarger->getId()],
                ['DELETE', '/enlargers/' . $enlarger->getId()],
                ['POST', '/enlargers'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertEnlargerSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertEnlargerSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertEnlargerSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Anaret',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
    }

    public function testUserCannotWriteOfficialEnlargerOfSomeoneElse(): void
    {
        $enlarger = $this->createEnlarger([
            'status' => CatalogStatus::OFFICIAL,
            'createdBy' => 'other_user',
        ]);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/enlargers/' . $enlarger->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/enlargers/' . $enlarger->getId());
    }

    public function testUserCanCreatePersonalEnlargerByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Enlarger',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialEnlargerDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Enlarger',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalEnlargerAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $enlarger = $this->createEnlarger([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);

        $client->request('PATCH', '/enlargers/' . $enlarger->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalEnlarger(): void
    {
        $enlarger = $this->createEnlarger([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/enlargers/' . $enlarger->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $enlarger = $this->createEnlarger([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/enlargers?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/enlargers/' . $enlarger->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertEnlargerSecurityByRole($client, bool $canWrite): void
    {
        $enlarger = $this->createEnlarger();
        $manufacturer = $this->createManufacturer();

        $this->assertSuccessfulStatus($client, 'GET', '/enlargers', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/enlargers/' . $enlarger->getId(), 200);

        $patchEnlargerOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'M805 Updated',
            ],
        ];

        $postEnlargerOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Anaret',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/enlargers/' . $enlarger->getId(),
                200,
                $patchEnlargerOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/enlargers/' . $enlarger->getId(),
                204,
            );
            $this->assertSuccessfulStatus($client, 'POST', '/enlargers', 201, $postEnlargerOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/enlargers/' . $enlarger->getId(),
            $patchEnlargerOptions,
        );
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/enlargers/' . $enlarger->getId());
        $this->assertForbiddenAccessDenied($client, 'POST', '/enlargers', $postEnlargerOptions);
    }
}

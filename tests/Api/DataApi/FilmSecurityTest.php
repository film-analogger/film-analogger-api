<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class FilmSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $this->createFilm();
        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = static::createClient();

        foreach (
            [
                ['GET', '/films'],
                ['GET', '/films/some-id'],
                ['PATCH', '/films/some-id'],
                ['DELETE', '/films/some-id'],
                ['POST', '/films'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientDataReader(), false);
    }

    /**
     * ROLE_data_reader alone (no ROLE_user) has no write access at all,
     * including creating personal/pending entries.
     */
    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
    }

    public function testUserCannotWriteOfficialFilmOfSomeoneElse(): void
    {
        $film = $this->createFilm(['status' => CatalogStatus::OFFICIAL, 'createdBy' => 'other_user']);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/films/' . $film->getId());
    }

    public function testUserCanCreatePersonalFilmByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Film',
                'description' => 'A personal contribution.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCanCreatePendingFilmExplicitly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Film',
                'description' => 'A personal contribution.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'pending',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotCreateOfficialFilmDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Homemade Film',
                'description' => 'A personal contribution.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'official',
            ],
        ]);
    }

    public function testDataWriterDefaultsToPersonalButCanPostOfficialDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataWriter();

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Draft film',
                'description' => 'A draft.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'New Reference Film',
                'description' => 'Straight to the official catalog.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'status' => 'official',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'official']);
    }

    public function testUserCanEditOwnPersonalFilmAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $film = $this->createFilm([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed draft', 'status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['name' => 'Renamed draft', 'status' => 'pending']);
    }

    public function testUserCannotEditOwnFilmWhilePending(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $film = $this->createFilm([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
    }

    public function testUserCannotEditOwnFilmOnceOfficial(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $film = $this->createFilm([
            'status' => CatalogStatus::OFFICIAL,
            'createdBy' => 'plain_user',
        ]);

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
    }

    public function testUserCanDeleteOwnPendingFilm(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $film = $this->createFilm([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $this->assertSuccessfulStatus($client, 'DELETE', '/films/' . $film->getId(), 204);
    }

    public function testUserCannotSeeOthersPersonalFilm(): void
    {
        $film = $this->createFilm([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/films/' . $film->getId());
    }

    public function testUserSeesOfficialAndOwnFilmsInCollectionOnly(): void
    {
        $this->createFilm(['name' => 'Official one', 'status' => CatalogStatus::OFFICIAL]);
        $this->createFilm([
            'name' => 'My personal one',
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);
        $this->createFilm([
            'name' => "Someone else's personal one",
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $client->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testDataWriterSeesEveryStatusAndCanApproveOrRejectPending(): void
    {
        $film = $this->createFilm([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/films');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'rejected'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'rejected']);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    public function testStatusFilterScopesPendingQueueToOwnForRegularUsers(): void
    {
        $this->createFilm([
            'name' => 'My pending film',
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);
        $this->createFilm([
            'name' => "Someone else's pending film",
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $client->request('GET', '/films?status=pending');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $dataWriter = self::loggedClientDataWriter();
        $dataWriter->request('GET', '/films?status=pending');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    private function assertFilmSecurityByRole($client, bool $canWrite): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $this->assertSuccessfulStatus($client, 'GET', '/films', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/films/' . $film->getId(), 200);

        $patchOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ];

        $postOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/films/' . $film->getId(),
                200,
                $patchOptions,
            );
            $this->assertSuccessfulStatus($client, 'DELETE', '/films/' . $film->getId(), 204);
            $this->assertSuccessfulStatus($client, 'POST', '/films', 201, $postOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/films/' . $film->getId(),
            $patchOptions,
        );
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/films/' . $film->getId());
        $this->assertForbiddenAccessDenied($client, 'POST', '/films', $postOptions);
    }
}

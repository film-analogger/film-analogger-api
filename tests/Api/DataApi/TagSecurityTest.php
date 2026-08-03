<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class TagSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $tag = $this->createTag();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/tags'],
                ['GET', '/tags/' . $tag->getId()],
                ['PATCH', '/tags/' . $tag->getId()],
                ['DELETE', '/tags/' . $tag->getId()],
                ['POST', '/tags'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderCannotWrite(): void
    {
        $tag = $this->createTag();

        $client = self::loggedClientDataReader();

        $this->assertSuccessfulStatus($client, 'GET', '/tags', 200);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/tags/' . $tag->getId());
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/tags', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Halation'],
        ]);
    }

    public function testDataWriterCanWrite(): void
    {
        $tag = $this->createTag();

        $client = self::loggedClientDataWriter();

        $this->assertSuccessfulStatus($client, 'DELETE', '/tags/' . $tag->getId(), 204);
    }

    public function testUserCannotWriteOfficialTagOfSomeoneElse(): void
    {
        $tag = $this->createTag(['status' => CatalogStatus::OFFICIAL, 'createdBy' => 'other_user']);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied($client, 'PATCH', '/tags/' . $tag->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/tags/' . $tag->getId());
    }

    public function testUserCanCreatePersonalTagByDefault(): void
    {
        $client = self::loggedClientUser();

        $client->request('POST', '/tags', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Halation'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialTagDirectly(): void
    {
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/tags', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Halation', 'status' => 'official'],
        ]);
    }

    public function testUserCanEditOwnPersonalTagAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $tag = $this->createTag(['status' => CatalogStatus::PERSONAL, 'createdBy' => 'plain_user']);

        $client->request('PATCH', '/tags/' . $tag->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalTag(): void
    {
        $tag = $this->createTag(['status' => CatalogStatus::PERSONAL, 'createdBy' => 'other_user']);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/tags/' . $tag->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $tag = $this->createTag(['status' => CatalogStatus::PENDING, 'createdBy' => 'plain_user']);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/tags?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/tags/' . $tag->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PhotoPaperStockSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $stock = $this->createPhotoPaperStock();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/photo_paper_stocks'],
                ['GET', '/photo_paper_stocks/' . $stock->getId()],
                ['PATCH', '/photo_paper_stocks/' . $stock->getId()],
                ['DELETE', '/photo_paper_stocks/' . $stock->getId()],
                ['POST', '/photo_paper_stocks'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $photoPaper = $this->createPhotoPaper();

        $client = self::loggedClientDataReader();
        $client->request('POST', '/photo_paper_stocks', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'photoPaper' => '/photo_papers/' . $photoPaper->getId(),
                'format' => '18x24',
                'quantity' => 5,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createStockAsOwner(?Client $client = null): array
    {
        $photoPaper = $this->createPhotoPaper();

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $response = $ownerClient->request('POST', '/photo_paper_stocks', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'photoPaper' => '/photo_papers/' . $photoPaper->getId(),
                'format' => '18x24',
                'quantity' => 5,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $response->toArray()['id']];
    }

    public function testOwnerCanAccessOwnStock(): void
    {
        [$ownerClient, $id] = $this->createStockAsOwner();

        $ownerClient->request('GET', '/photo_paper_stocks/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/photo_paper_stocks/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 3],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesStock(): void
    {
        [, $id] = $this->createStockAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/photo_paper_stocks/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'PATCH', '/photo_paper_stocks/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['quantity' => 1],
        ]);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/photo_paper_stocks/' . $id);
    }

    public function testAdminCanAccessAnyStock(): void
    {
        [, $id] = $this->createStockAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/photo_paper_stocks/' . $id);
        $this->assertResponseIsSuccessful();

        $admin->request('DELETE', '/photo_paper_stocks/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createStockAsOwner($ownerClient);
        $this->createStockAsOwner($ownerClient);

        $ownerClient->request('GET', '/photo_paper_stocks');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/photo_paper_stocks');
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/photo_paper_stocks');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

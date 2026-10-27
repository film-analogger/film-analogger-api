<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PhotoPaperStockTest extends AbstractFilmTestCase
{
    public function testCreatePhotoPaperStock(): void
    {
        $photoPaper = $this->createPhotoPaper(['name' => 'Multigrade RC Pearl']);

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/photo_paper_stocks', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'photoPaper' => '/photo_papers/' . $photoPaper->getId(),
                'format' => '18x24',
                'quantity' => 25,
                'purchasedAt' => '2026-01-15',
                'expiresAt' => '2028-01-15',
                'notes' => 'Bought on sale.',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('18x24', $data['format']);
        $this->assertSame(25, $data['quantity']);
        $this->assertSame('2026-01-15', substr($data['purchasedAt'], 0, 10));
        $this->assertSame('test_user_admin', $data['createdBy']);
    }

    public function testCreatePhotoPaperStockWithoutPhotoPaperFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_paper_stocks', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['format' => '18x24', 'quantity' => 5],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePhotoPaperStockWithNegativeQuantityFails(): void
    {
        $photoPaper = $this->createPhotoPaper();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_paper_stocks', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'photoPaper' => '/photo_papers/' . $photoPaper->getId(),
                'format' => '18x24',
                'quantity' => -1,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetPhotoPaperStockDeepEmbedsPhotoPaper(): void
    {
        $manufacturer = $this->createManufacturer('Ilford');
        $photoPaper = $this->createPhotoPaper(['name' => 'MGRC', 'manufacturer' => $manufacturer]);
        $stock = $this->createPhotoPaperStock([
            'photoPaper' => $photoPaper,
            'createdBy' => 'test_user_admin',
        ]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/photo_paper_stocks/' . $stock->getId());
        $data = $response->toArray();

        $this->assertSame('MGRC', $data['photoPaper']['name']);
        $this->assertSame('Ilford', $data['photoPaper']['manufacturer']['name']);
    }

    public function testGetCollectionDoesNotDeepEmbedPhotoPaper(): void
    {
        $this->createPhotoPaperStock(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/photo_paper_stocks');
        $data = $response->toArray()['hydra:member'][0];

        $this->assertArrayNotHasKey('name', $data['photoPaper']);
    }
}

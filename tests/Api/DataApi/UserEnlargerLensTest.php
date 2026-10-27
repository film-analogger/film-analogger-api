<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserEnlargerLensTest extends AbstractFilmTestCase
{
    public function testCreateUserEnlargerLens(): void
    {
        $enlargerLens = $this->createEnlargerLens(['name' => 'EL-Nikkor 50mm']);

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'enlargerLens' => '/enlarger_lenses/' . $enlargerLens->getId(),
                'quantity' => 1,
                'acquiredAt' => '2026-01-15',
                'notes' => 'Sharp wide open.',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(1, $data['quantity']);
        $this->assertSame('2026-01-15', substr($data['acquiredAt'], 0, 10));
        $this->assertSame('test_user_admin', $data['createdBy']);
    }

    public function testCreateUserEnlargerLensDefaultsQuantityToOne(): void
    {
        $enlargerLens = $this->createEnlargerLens();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlargerLens' => '/enlarger_lenses/' . $enlargerLens->getId()],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(1, $response->toArray()['quantity']);
    }

    public function testCreateUserEnlargerLensWithoutEnlargerLensFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/user_enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetUserEnlargerLensDeepEmbedsEnlargerLens(): void
    {
        $manufacturer = $this->createManufacturer('Nikon');
        $enlargerLens = $this->createEnlargerLens([
            'name' => 'EL-Nikkor 50mm',
            'manufacturer' => $manufacturer,
        ]);
        $userEnlargerLens = $this->createUserEnlargerLens([
            'enlargerLens' => $enlargerLens,
            'createdBy' => 'test_user_admin',
        ]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_enlarger_lenses/' . $userEnlargerLens->getId());
        $data = $response->toArray();

        $this->assertSame('EL-Nikkor 50mm', $data['enlargerLens']['name']);
        $this->assertSame('Nikon', $data['enlargerLens']['manufacturer']['name']);
    }

    public function testGetCollectionDoesNotDeepEmbedEnlargerLens(): void
    {
        $this->createUserEnlargerLens(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_enlarger_lenses');
        $data = $response->toArray()['hydra:member'][0];

        $this->assertArrayNotHasKey('name', $data['enlargerLens']);
    }
}

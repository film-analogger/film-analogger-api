<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class UserEnlargerTest extends AbstractFilmTestCase
{
    public function testCreateUserEnlarger(): void
    {
        $enlarger = $this->createEnlarger(['name' => 'M805']);

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'enlarger' => '/enlargers/' . $enlarger->getId(),
                'quantity' => 1,
                'acquiredAt' => '2026-01-15',
                'notes' => 'Inherited from a friend.',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(1, $data['quantity']);
        $this->assertSame('2026-01-15', substr($data['acquiredAt'], 0, 10));
        $this->assertSame('test_user_admin', $data['createdBy']);
    }

    public function testCreateUserEnlargerDefaultsQuantityToOne(): void
    {
        $enlarger = $this->createEnlarger();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/user_enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['enlarger' => '/enlargers/' . $enlarger->getId()],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(1, $response->toArray()['quantity']);
    }

    public function testCreateUserEnlargerWithoutEnlargerFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/user_enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetUserEnlargerDeepEmbedsEnlarger(): void
    {
        $manufacturer = $this->createManufacturer('Durst');
        $enlarger = $this->createEnlarger(['name' => 'M805', 'manufacturer' => $manufacturer]);
        $userEnlarger = $this->createUserEnlarger([
            'enlarger' => $enlarger,
            'createdBy' => 'test_user_admin',
        ]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_enlargers/' . $userEnlarger->getId());
        $data = $response->toArray();

        $this->assertSame('M805', $data['enlarger']['name']);
        $this->assertSame('Durst', $data['enlarger']['manufacturer']['name']);
    }

    public function testGetCollectionDoesNotDeepEmbedEnlarger(): void
    {
        $this->createUserEnlarger(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/user_enlargers');
        $data = $response->toArray()['hydra:member'][0];

        $this->assertArrayNotHasKey('name', $data['enlarger']);
    }
}

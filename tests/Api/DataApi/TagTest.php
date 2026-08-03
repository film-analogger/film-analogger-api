<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class TagTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createTag(['name' => 'Fogged']);
        $this->createTag(['name' => 'Scratches']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/tags');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Tag',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testCreateTag(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/tags', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Reticulation',
                'description' => 'Cracked grain caused by a temperature shock.',
                'primaryColor' => '#0898D0',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Reticulation',
            'primaryColor' => '#0898D0',
        ]);
    }

    public function testCreateTagWithoutNameFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/tags', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateTag(): void
    {
        $tag = $this->createTag(['name' => 'Fogged']);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/tags/' . $tag->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Light-fogged'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['name' => 'Light-fogged']);
    }

    public function testDeleteTag(): void
    {
        $tag = $this->createTag();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/tags/' . $tag->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/tags/' . $tag->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

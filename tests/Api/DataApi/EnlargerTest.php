<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\EnlargerLightSource;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class EnlargerTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createEnlarger(['name' => 'M805']);
        $this->createEnlarger(['name' => 'M605']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlargers');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Enlarger',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetEnlarger(): void
    {
        $manufacturer = $this->createManufacturer('Durst');
        $enlarger = $this->createEnlarger([
            'name' => 'M805',
            'manufacturer' => $manufacturer,
            'lightSource' => EnlargerLightSource::CONDENSER,
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlargers/' . $enlarger->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'M805',
            'lightSource' => 'condenser',
        ]);
    }

    public function testCreateEnlarger(): void
    {
        $manufacturer = $this->createManufacturer('Meopta');

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Anaret',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'lightSource' => 'condenser',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Anaret',
            'lightSource' => 'condenser',
        ]);
    }

    public function testCreateEnlargerWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateEnlargerWithoutManufacturerFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'M805',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateEnlargerWithInvalidLightSourceFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlargers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'M805',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'lightSource' => 'not-a-light-source',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateEnlarger(): void
    {
        $enlarger = $this->createEnlarger(['name' => 'M805']);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/enlargers/' . $enlarger->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'M805 Updated',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'M805 Updated',
        ]);
    }

    public function testDeleteEnlarger(): void
    {
        $enlarger = $this->createEnlarger();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/enlargers/' . $enlarger->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlargers/' . $enlarger->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

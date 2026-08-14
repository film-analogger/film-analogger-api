<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\FocalLength;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class EnlargerLensTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createEnlargerLens(['name' => 'EL-Nikkor 50mm']);
        $this->createEnlargerLens(['name' => 'Rodagon 80mm']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlarger_lenses');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/EnlargerLens',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetEnlargerLens(): void
    {
        $manufacturer = $this->createManufacturer('Rodenstock');
        $enlargerLens = $this->createEnlargerLens([
            'name' => 'Rodagon 50mm',
            'manufacturer' => $manufacturer,
            'focalLength' => FocalLength::F50,
            'maxAperture' => 2.8,
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlarger_lenses/' . $enlargerLens->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Rodagon 50mm',
            'focalLength' => 50,
            'maxAperture' => 2.8,
        ]);
    }

    public function testCreateEnlargerLens(): void
    {
        $manufacturer = $this->createManufacturer('Schneider Kreuznach');

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Componon-S 80mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 80,
                'maxAperture' => 4.5,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Componon-S 80mm',
            'focalLength' => 80,
            'maxAperture' => 4.5,
        ]);
    }

    public function testCreateEnlargerLensWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 2.8,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateEnlargerLensWithoutManufacturerFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm',
                'focalLength' => 50,
                'maxAperture' => 2.8,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateEnlargerLensWithInvalidFocalLengthFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 999,
                'maxAperture' => 2.8,
            ],
        ]);

        // A value outside the FocalLength backed enum's cases fails during
        // deserialization itself (not validation), so this is a plain 400
        // rather than the 422 a Symfony/Assert\Choice violation would give.
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateEnlargerLensWithoutMaxApertureFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateEnlargerLensWithNonPositiveMaxApertureFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/enlarger_lenses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'focalLength' => 50,
                'maxAperture' => 0,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateEnlargerLens(): void
    {
        $enlargerLens = $this->createEnlargerLens(['name' => 'EL-Nikkor 50mm']);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/enlarger_lenses/' . $enlargerLens->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'EL-Nikkor 50mm Updated',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'EL-Nikkor 50mm Updated',
        ]);
    }

    public function testDeleteEnlargerLens(): void
    {
        $enlargerLens = $this->createEnlargerLens();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/enlarger_lenses/' . $enlargerLens->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/enlarger_lenses/' . $enlargerLens->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

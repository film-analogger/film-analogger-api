<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class CameraTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createCamera(['name' => 'F100']);
        $this->createCamera(['name' => 'AE-1']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/cameras');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Camera',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetCamera(): void
    {
        $manufacturer = $this->createManufacturer('Nikon');
        $camera = $this->createCamera([
            'name' => 'F100',
            'manufacturer' => $manufacturer,
            'filmFormat' => '135',
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/cameras/' . $camera->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'F100',
            'filmFormat' => '135',
        ]);
    }

    public function testCreateCamera(): void
    {
        $manufacturer = $this->createManufacturer('Hasselblad');

        $client = self::loggedClientAdmin();
        $client->request('POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => '500 C/M',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'filmFormat' => '120',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => '500 C/M',
            'filmFormat' => '120',
        ]);
    }

    public function testCreateCameraWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateCameraWithoutManufacturerFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'F100',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateCameraWithInvalidFilmFormatFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/cameras', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'F100',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'filmFormat' => 'not-a-format',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateCamera(): void
    {
        $camera = $this->createCamera(['name' => 'F100']);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/cameras/' . $camera->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'F100 Updated',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'F100 Updated',
        ]);
    }

    public function testDeleteCamera(): void
    {
        $camera = $this->createCamera();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/cameras/' . $camera->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/cameras/' . $camera->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

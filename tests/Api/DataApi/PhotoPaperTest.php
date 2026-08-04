<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBase;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperSurface;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PhotoPaperTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createPhotoPaper(['name' => 'Multigrade RC Pearl']);
        $this->createPhotoPaper(['name' => 'Multigrade FB Glossy']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/photo_papers');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PhotoPaper',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetPhotoPaper(): void
    {
        $manufacturer = $this->createManufacturer('Ilford');
        $photoPaper = $this->createPhotoPaper([
            'name' => 'Multigrade RC Pearl',
            'manufacturer' => $manufacturer,
            'paperBase' => PaperBase::RC,
            'paperSurface' => PaperSurface::PEARL,
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/photo_papers/' . $photoPaper->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Multigrade RC Pearl',
            'paperBase' => 'rc',
            'paperSurface' => 'pearl',
        ]);
    }

    public function testCreatePhotoPaper(): void
    {
        $manufacturer = $this->createManufacturer('Foma');

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Fomabrom RC Matt',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'matt',
                'variableContrast' => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Fomabrom RC Matt',
            'paperBase' => 'rc',
            'paperSurface' => 'matt',
            'variableContrast' => false,
        ]);
    }

    public function testCreatePhotoPaperWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'glossy',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePhotoPaperWithoutManufacturerFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Multigrade RC Pearl',
                'paperBase' => 'rc',
                'paperSurface' => 'pearl',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePhotoPaperWithoutPaperBaseFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Multigrade RC Pearl',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperSurface' => 'pearl',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePhotoPaperWithOtherSurfaceWithoutDetailFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Papier maison',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'other',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePhotoPaperWithOtherSurfaceAndDetailSucceeds(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Papier maison',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'other',
                'paperSurfaceOther' => 'semi-matte maison',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testUpdatePhotoPaper(): void
    {
        $photoPaper = $this->createPhotoPaper(['name' => 'Multigrade RC Pearl']);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/photo_papers/' . $photoPaper->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Multigrade RC Pearl Updated',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Multigrade RC Pearl Updated',
        ]);
    }

    public function testDeletePhotoPaper(): void
    {
        $photoPaper = $this->createPhotoPaper();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/photo_papers/' . $photoPaper->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/photo_papers/' . $photoPaper->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

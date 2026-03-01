<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use Doctrine\ODM\MongoDB\DocumentManager;

class FilmTest extends ApiTestCase
{
    protected static null|bool $alwaysBootKernel = false;

    private DocumentManager $documentManager;

    protected function setUp(): void
    {
        $this->documentManager = static::getContainer()->get(DocumentManager::class);
        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $this->documentManager->getDocumentCollection(Film::class)->drop();
        $this->documentManager->getDocumentCollection(Manufacturer::class)->drop();
    }

    private function createManufacturer(string $name = 'Kodak'): Manufacturer
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setName($name);
        $this->documentManager->persist($manufacturer);
        $this->documentManager->flush();

        return $manufacturer;
    }

    private function createFilm(array $overrides = []): Film
    {
        $manufacturer = $overrides['manufacturer'] ?? $this->createManufacturer();

        $film = new Film();
        $film->setName($overrides['name'] ?? 'Portra 400');
        $film->setDescription($overrides['description'] ?? 'A professional color negative film.');
        $film->setProcess($overrides['process'] ?? 'C-41');
        $film->setSensibility($overrides['sensibility'] ?? 400);
        $film->setManufacturer($manufacturer);

        if (isset($overrides['emulsionType'])) {
            $film->setEmulsionType($overrides['emulsionType']);
        }
        if (isset($overrides['inversible'])) {
            $film->setInversible($overrides['inversible']);
        }
        if (isset($overrides['officialDocumentationUrl'])) {
            $film->setOfficialDocumentationUrl($overrides['officialDocumentationUrl']);
        }
        if (isset($overrides['primaryColor'])) {
            $film->setPrimaryColor($overrides['primaryColor']);
        }
        if (isset($overrides['secondaryColor'])) {
            $film->setSecondaryColor($overrides['secondaryColor']);
        }
        if (isset($overrides['tertiaryColor'])) {
            $film->setTertiaryColor($overrides['tertiaryColor']);
        }

        $this->documentManager->persist($film);
        $this->documentManager->flush();

        return $film;
    }

    public function testGetCollection(): void
    {
        $this->createFilm();
        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $response = static::createClient()->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Film',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetFilm(): void
    {
        $film = $this->createFilm();

        $response = static::createClient()->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'process' => 'C-41',
            'sensibility' => 400,
        ]);
    }

    public function testCreateFilm(): void
    {
        $manufacturer = $this->createManufacturer();

        $response = static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Gold 200',
            'description' => 'A consumer color negative film.',
            'process' => 'C-41',
            'sensibility' => 200,
        ]);
    }

    public function testCreateFilmWithAllFields(): void
    {
        $manufacturer = $this->createManufacturer();

        $response = static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Ektachrome E100',
                'description' => 'A professional color reversal film.',
                'process' => 'E-6',
                'sensibility' => 100,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'emulsionType' => 'panchromatic',
                'inversible' => true,
                'officialDocumentationUrl' => 'https://www.kodak.com/ektachrome',
                'primaryColor' => '#FF5733',
                'secondaryColor' => '#33FF57',
                'tertiaryColor' => '#3357FF',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Ektachrome E100',
            'description' => 'A professional color reversal film.',
            'process' => 'E-6',
            'sensibility' => 100,
            'emulsionType' => 'panchromatic',
            'inversible' => true,
            'officialDocumentationUrl' => 'https://www.kodak.com/ektachrome',
            'primaryColor' => '#FF5733',
            'secondaryColor' => '#33FF57',
            'tertiaryColor' => '#3357FF',
        ]);
    }

    public function testCreateFilmWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'description' => 'A film without a name.',
                'process' => 'C-41',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithoutDescriptionFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Portra 400',
                'process' => 'C-41',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithoutProcessFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Portra 400',
                'description' => 'A professional film.',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithoutSensibilityFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Portra 400',
                'description' => 'A professional film.',
                'process' => 'C-41',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithInvalidProcessFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'INVALID-PROCESS',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithValidProcessC41(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'C41 Film',
                'description' => 'A C-41 film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateFilmWithValidProcessE6(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'E6 Film',
                'description' => 'An E-6 film.',
                'process' => 'E-6',
                'sensibility' => 100,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateFilmWithValidProcessBW(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'BW Film',
                'description' => 'A black and white film.',
                'process' => 'B&W',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateFilmWithValidProcessECN2(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'ECN2 Film',
                'description' => 'A cinema film.',
                'process' => 'ECN-2',
                'sensibility' => 500,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateFilmWithInvalidEmulsionTypeFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'emulsionType' => 'invalid-emulsion',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithValidEmulsionTypePanchromatic(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Panchromatic Film',
                'description' => 'A panchromatic film.',
                'process' => 'B&W',
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['emulsionType' => 'panchromatic']);
    }

    public function testCreateFilmWithValidEmulsionTypeOrthochromatic(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Ortho Film',
                'description' => 'An orthochromatic film.',
                'process' => 'B&W',
                'sensibility' => 80,
                'emulsionType' => 'orthochromatic',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['emulsionType' => 'orthochromatic']);
    }

    public function testCreateFilmWithValidEmulsionTypeChromogene(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Chromogene Film',
                'description' => 'A chromogene film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['emulsionType' => 'chromogene']);
    }

    public function testCreateFilmWithInvalidUrlFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'officialDocumentationUrl' => 'not-a-valid-url',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithInvalidCssColorFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'primaryColor' => 'not-a-color',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithInvalidSecondaryColorFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'secondaryColor' => 'invalid',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithInvalidTertiaryColorFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'tertiaryColor' => 'invalid',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateFilm(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 800',
            'sensibility' => 800,
        ]);
    }

    public function testDeleteFilm(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('DELETE', '/films/' . $film->getId());

        $this->assertResponseStatusCodeSame(204);

        static::createClient()->request('GET', '/films/' . $film->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentFilmReturns404(): void
    {
        static::createClient()->request('GET', '/films/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testFilmContainsManufacturerReference(): void
    {
        $manufacturer = $this->createManufacturer('Fujifilm');
        $film = $this->createFilm(['manufacturer' => $manufacturer, 'name' => 'Superia 400']);

        $response = static::createClient()->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Superia 400',
        ]);
    }

    public function testCreateFilmWithNullOptionalFields(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Minimal Film',
                'description' => 'A minimal film entry.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'emulsionType' => null,
                'inversible' => null,
                'officialDocumentationUrl' => null,
                'primaryColor' => null,
                'secondaryColor' => null,
                'tertiaryColor' => null,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Minimal Film',
            'emulsionType' => null,
            'inversible' => null,
            'officialDocumentationUrl' => null,
            'primaryColor' => null,
            'secondaryColor' => null,
            'tertiaryColor' => null,
        ]);
    }

    public function testUpdateFilmProcess(): void
    {
        $film = $this->createFilm(['process' => 'C-41']);

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'process' => 'E-6',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['process' => 'E-6']);
    }

    public function testUpdateFilmWithInvalidProcessFails(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'process' => 'INVALID',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetCollectionReturnsEmptyWhenNoFilms(): void
    {
        $response = static::createClient()->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testCreateFilmWithBlankNameFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => '',
                'description' => 'A test film.',
                'process' => 'C-41',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithBlankDescriptionFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => '',
                'process' => 'C-41',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithBlankProcessFails(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Test Film',
                'description' => 'A test film.',
                'process' => '',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateFilmWithInversibleTrue(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Ektachrome E100',
                'description' => 'A slide film.',
                'process' => 'E-6',
                'sensibility' => 100,
                'inversible' => true,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['inversible' => true]);
    }

    public function testCreateFilmWithInversibleFalse(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Portra 160',
                'description' => 'A negative film.',
                'process' => 'C-41',
                'sensibility' => 160,
                'inversible' => false,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['inversible' => false]);
    }

    public function testUpdateFilmColors(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'primaryColor' => '#AABBCC',
                'secondaryColor' => '#112233',
                'tertiaryColor' => '#445566',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'primaryColor' => '#AABBCC',
            'secondaryColor' => '#112233',
            'tertiaryColor' => '#445566',
        ]);
    }

    public function testUpdateFilmOfficialDocumentationUrl(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);
    }

    public function testUpdateFilmWithInvalidUrlFails(): void
    {
        $film = $this->createFilm();

        static::createClient()->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'officialDocumentationUrl' => 'not-a-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testFilmIdIsReadOnly(): void
    {
        $film = $this->createFilm();
        $originalId = $film->getId();

        $response = static::createClient()->request('GET', '/films/' . $originalId);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($originalId, $data['id']);
    }

    public function testCreateMultipleFilmsWithSameManufacturer(): void
    {
        $manufacturer = $this->createManufacturer('Ilford');

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'HP5 Plus',
                'description' => 'A classic B&W film.',
                'process' => 'B&W',
                'sensibility' => 400,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Delta 3200',
                'description' => 'A high-speed B&W film.',
                'process' => 'B&W',
                'sensibility' => 3200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $response = static::createClient()->request('GET', '/films');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testCreateFilmWithValidCssColorNames(): void
    {
        $manufacturer = $this->createManufacturer();

        static::createClient()->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Color Film',
                'description' => 'A colorful film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'primaryColor' => 'red',
                'secondaryColor' => 'blue',
                'tertiaryColor' => 'green',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'primaryColor' => 'red',
            'secondaryColor' => 'blue',
            'tertiaryColor' => 'green',
        ]);
    }
}

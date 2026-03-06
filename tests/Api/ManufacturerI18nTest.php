<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class ManufacturerI18nTest extends AbstractFilmTestCase
{
    public function testGetManufacturerWithFrenchLocale(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $this->documentManager->clear();
        $manufacturer = $this->documentManager->find(Manufacturer::class, $manufacturer->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'X-LOCALE' => 'fr'],
            'json' => [
                'website' => 'https://www.kodak.fr',
            ],
        ]);

        // default (en) via X-LOCALE
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['X-LOCALE' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Kodak',
            'website' => null,
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // default (en) with no locale header
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Kodak',
            'website' => null,
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // French via X-LOCALE
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['X-LOCALE' => 'fr', 'Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Kodak',
            'website' => 'https://www.kodak.fr',
            'translations' => [['locale' => 'fr', 'field' => 'website']],
            'isTranslated' => true,
        ]);
    }

    public function testGetManufacturerWithUnsupportedLocaleFallsBackToDefault(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['X-LOCALE' => 'ja'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Kodak',
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);
    }

    public function testGetManufacturerWithAcceptLanguageHeader(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $this->documentManager->clear();
        $manufacturer = $this->documentManager->find(Manufacturer::class, $manufacturer->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr',
            ],
            'json' => [
                'website' => 'https://www.kodak.fr',
            ],
        ]);

        // English via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['name' => 'Kodak', 'website' => null]);

        // no header — falls back to default
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['name' => 'Kodak', 'website' => null]);

        // French via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['name' => 'Kodak', 'website' => 'https://www.kodak.fr']);
    }

    public function testGetManufacturerWithAcceptLanguageFallback(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $this->documentManager->clear();
        $manufacturer = $this->documentManager->find(Manufacturer::class, $manufacturer->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'website' => 'https://www.kodak.fr',
            ],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['website' => null]);

        // French with quality factor
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Accept-Language' => 'fr, en;q=0.9'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['website' => 'https://www.kodak.fr']);

        // reverse order
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Accept-Language' => 'en;q=0.9, fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['website' => 'https://www.kodak.fr']);
    }

    public function testGetManufacturerCollectionWithFrenchLocale(): void
    {
        $kodak = $this->createManufacturer('Kodak');
        $ilford = $this->createManufacturer('Ilford');

        $this->documentManager->clear();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $kodak->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => ['website' => 'https://www.kodak.fr'],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $ilford->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => ['website' => 'https://www.ilfordphoto.fr'],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers', [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                ['name' => 'Kodak', 'website' => null, 'isTranslated' => false],
                ['name' => 'Ilford', 'website' => null, 'isTranslated' => false],
            ],
        ]);

        // French
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers', [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                ['name' => 'Kodak', 'website' => 'https://www.kodak.fr', 'isTranslated' => true],
                [
                    'name' => 'Ilford',
                    'website' => 'https://www.ilfordphoto.fr',
                    'isTranslated' => true,
                ],
            ],
        ]);
    }
}

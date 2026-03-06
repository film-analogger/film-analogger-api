<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;

class ChemistryTypeI18nTest extends AbstractFilmTestCase
{
    public function testGetChemistryTypeWithFrenchLocale(): void
    {
        $chemistryType = $this->createChemistryType('B&W', 'BW_FILM_DEVELOPER', 'Film Developer');

        $this->documentManager->clear();
        $chemistryType = $this->documentManager->find(
            ChemistryType::class,
            $chemistryType->getId(),
        );

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'X-LOCALE' => 'fr'],
            'json' => [
                'typeLabel' => 'Développeur de film',
            ],
        ]);

        // default (en) via X-LOCALE
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['X-LOCALE' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // default (en) with no locale header
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // French via X-LOCALE
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['X-LOCALE' => 'fr', 'Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Développeur de film',
            'translations' => [['locale' => 'fr', 'field' => 'typeLabel']],
            'isTranslated' => true,
        ]);
    }

    public function testGetChemistryTypeWithUnsupportedLocaleFallsBackToDefault(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['X-LOCALE' => 'ja'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);
    }

    public function testGetChemistryTypeWithAcceptLanguageHeader(): void
    {
        $chemistryType = $this->createChemistryType('B&W', 'BW_FILM_DEVELOPER', 'Film Developer');

        $this->documentManager->clear();
        $chemistryType = $this->documentManager->find(
            ChemistryType::class,
            $chemistryType->getId(),
        );

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr',
            ],
            'json' => [
                'typeLabel' => 'Développeur de film',
            ],
        ]);

        // English via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
        ]);

        // no header — falls back to default
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
        ]);

        // French via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Développeur de film',
        ]);
    }

    public function testGetChemistryTypeWithAcceptLanguageFallback(): void
    {
        $chemistryType = $this->createChemistryType('B&W', 'BW_FILM_DEVELOPER', 'Film Developer');

        $this->documentManager->clear();
        $chemistryType = $this->documentManager->find(
            ChemistryType::class,
            $chemistryType->getId(),
        );

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'typeLabel' => 'Développeur de film',
            ],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Developer',
        ]);

        // French with quality factor
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Accept-Language' => 'fr, en;q=0.9'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Développeur de film',
        ]);

        // reverse order
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Accept-Language' => 'en;q=0.9, fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Développeur de film',
        ]);
    }

    public function testGetChemistryTypeCollectionWithFrenchLocale(): void
    {
        $type1 = $this->createChemistryType('B&W', 'BW_FILM_DEVELOPER', 'Film Developer');
        $type2 = $this->createChemistryType('B&W', 'FIXER', 'Fixer');

        $this->documentManager->clear();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $type1->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'typeLabel' => 'Développeur de film',
            ],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $type2->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'typeLabel' => 'Fixateur',
            ],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types', [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                ['typeLabel' => 'Film Developer', 'isTranslated' => false],
                ['typeLabel' => 'Fixer', 'isTranslated' => false],
            ],
        ]);

        // French
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types', [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                ['typeLabel' => 'Développeur de film', 'isTranslated' => true],
                ['typeLabel' => 'Fixateur', 'isTranslated' => true],
            ],
        ]);
    }
}

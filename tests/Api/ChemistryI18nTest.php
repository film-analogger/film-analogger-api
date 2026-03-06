<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;

class ChemistryI18nTest extends AbstractFilmTestCase
{
    public function testGetChemistryWithFrenchLocale(): void
    {
        $chemistry = $this->createChemistry([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        $this->documentManager->clear();
        $chemistry = $this->documentManager->find(Chemistry::class, $chemistry->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'X-LOCALE' => 'fr'],
            'json' => [
                'description' => 'Développeur classique à grain fin.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
            ],
        ]);

        // default (en) via X-LOCALE
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['X-LOCALE' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'D-76',
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // default (en) with no locale header
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'D-76',
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // French via X-LOCALE
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['X-LOCALE' => 'fr', 'Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'D-76',
            'description' => 'Développeur classique à grain fin.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
            'translations' => [
                ['locale' => 'fr', 'field' => 'description'],
                ['locale' => 'fr', 'field' => 'officialDocumentationUrl'],
            ],
            'isTranslated' => true,
        ]);
    }

    public function testGetChemistryWithUnsupportedLocaleFallsBackToDefault(): void
    {
        $chemistry = $this->createChemistry([
            'description' => 'Classic fine-grain developer.',
        ]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['X-LOCALE' => 'ja'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'D-76',
            'description' => 'Classic fine-grain developer.',
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);
    }

    public function testGetChemistryWithAcceptLanguageHeader(): void
    {
        $chemistry = $this->createChemistry([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        $this->documentManager->clear();
        $chemistry = $this->documentManager->find(Chemistry::class, $chemistry->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr',
            ],
            'json' => [
                'description' => 'Développeur classique à grain fin.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
            ],
        ]);

        // English via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        // no header — falls back to default
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        // French via Accept-Language
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Développeur classique à grain fin.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
        ]);
    }

    public function testGetChemistryWithAcceptLanguageFallback(): void
    {
        $chemistry = $this->createChemistry([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        $this->documentManager->clear();
        $chemistry = $this->documentManager->find(Chemistry::class, $chemistry->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Développeur classique à grain fin.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
            ],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);

        // French with quality factor
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Accept-Language' => 'fr, en;q=0.9'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Développeur classique à grain fin.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
        ]);

        // reverse order
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Accept-Language' => 'en;q=0.9, fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'description' => 'Développeur classique à grain fin.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
        ]);
    }

    public function testGetChemistryCollectionWithFrenchLocale(): void
    {
        $chemistry1 = $this->createChemistry([
            'name' => 'D-76',
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);
        $chemistry2 = $this->createChemistry([
            'name' => 'HC-110',
            'description' => 'Concentrated liquid developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/hc110',
        ]);

        $this->documentManager->clear();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry1->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Développeur classique à grain fin.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/d76',
            ],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry2->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Développeur liquide concentré.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/hc110',
            ],
        ]);

        // English
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries', [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                [
                    'name' => 'D-76',
                    'description' => 'Classic fine-grain developer.',
                    'isTranslated' => false,
                ],
                [
                    'name' => 'HC-110',
                    'description' => 'Concentrated liquid developer.',
                    'isTranslated' => false,
                ],
            ],
        ]);

        // French
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries', [
            'headers' => ['Accept-Language' => 'fr'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                [
                    'name' => 'D-76',
                    'description' => 'Développeur classique à grain fin.',
                    'isTranslated' => true,
                ],
                [
                    'name' => 'HC-110',
                    'description' => 'Développeur liquide concentré.',
                    'isTranslated' => true,
                ],
            ],
        ]);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Security\Mock\KeycloakBearerUserMock;
use Gedmo\Translatable\Document\Translation;

class FilmI18nTest extends AbstractFilmTestCase
{
    public function testGetFilmWithFrenchLocale(): void
    {
        $film = $this->createFilm([
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // Update the film with a French translation
        $this->documentManager->clear();
        $film = $this->documentManager->find(Film::class, $film->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'X-LOCALE' => 'fr'],
            'json' => [
                'description' => 'Un film négatif couleur professionnel.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
            ],
        ]);

        // check default translation is returned when X-LOCALE is set to "en"
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['X-LOCALE' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // check default translation is returned when X-LOCALE is not set
        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/films/' . $film->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
            'translations' => [],
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);

        // check French translation is returned when X-LOCALE is set to "fr"
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['X-LOCALE' => 'fr', 'Accept-Language' => 'fr'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'Un film négatif couleur professionnel.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
            'translations' => [
                [
                    'locale' => 'fr',
                    'field' => 'description',
                ],
                [
                    'locale' => 'fr',
                    'field' => 'officialDocumentationUrl',
                ],
            ],
            'isTranslated' => true,
        ]);
    }

    public function testGetFilmWithUnsupportedLocaleFallsBackToDefault(): void
    {
        $film = $this->createFilm([
            'description' => 'A professional color negative film.',
        ]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['X-LOCALE' => 'ja'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'isTranslated' => false,
        ]);
        $this->assertArraysHaveIdenticalValues($response->toArray()['translations'], []);
    }

    public function testGetFilmWithAcceptLanguageHeader(): void
    {
        $film = $this->createFilm([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // Update the film with a French translation
        $this->documentManager->clear();
        $film = $this->documentManager->find(Film::class, $film->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr',
            ],
            'json' => [
                'description' => 'Un film négatif couleur professionnel.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
            ],
        ]);

        // check default translation is returned when Accept-Language is set to "en"
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // check default translation is returned when Accept-Language is not set
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => [],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // check French translation is returned when Accept-Language is set to "fr"
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['Accept-Language' => 'fr'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'Un film négatif couleur professionnel.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
        ]);
    }

    public function testGetFilmWithAcceptLanguageFallback(): void
    {
        $film = $this->createFilm([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // Update the film with a French translation
        $this->documentManager->clear();
        $film = $this->documentManager->find(Film::class, $film->getId());

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Un film négatif couleur professionnel.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
            ],
        ]);

        // check default translation is returned when Accept-Language is set to "en"
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['Accept-Language' => 'en'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'A professional color negative film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);

        // check French translation is returned when Accept-Language is set to "fr" with quality factor
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['Accept-Language' => 'fr, en;q=0.9'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'Un film négatif couleur professionnel.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
        ]);

        // check French translation is returned when Accept-Language is set to "fr" with quality factor ( same but reverse order )
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films/' . $film->getId(), [
            'headers' => ['Accept-Language' => 'en;q=0.9, fr'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Portra 400',
            'description' => 'Un film négatif couleur professionnel.',
            'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
        ]);
    }

    public function testGetFilmCollectionWithFrenchLocale(): void
    {
        $film1 = $this->createFilm([
            'name' => 'Portra 400',
            'description' => 'A professional film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
        ]);
        $film2 = $this->createFilm([
            'name' => 'Ektar 100',
            'description' => 'A vivid color film.',
            'officialDocumentationUrl' => 'https://www.kodak.com/ektar100',
        ]);

        $this->documentManager->clear();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/films/' . $film1->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Un film négatif couleur professionnel.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
            ],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/films/' . $film2->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept-Language' => 'fr, en;q=0.9',
            ],
            'json' => [
                'description' => 'Un film négatif couleur aux couleurs vives.',
                'officialDocumentationUrl' => 'https://www.kodak.fr/ektar100',
            ],
        ]);

        // check en
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films', [
            'headers' => ['Accept-Language' => 'en'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                [
                    'name' => 'Portra 400',
                    'description' => 'A professional film.',
                    'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
                    'isTranslated' => false,
                ],
                [
                    'name' => 'Ektar 100',
                    'description' => 'A vivid color film.',
                    'officialDocumentationUrl' => 'https://www.kodak.com/ektar100',
                    'isTranslated' => false,
                ],
            ],
        ]);

        // check fallback
        $client = self::loggedClientAdmin();
        $client->request('GET', '/films', [
            'headers' => [],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                [
                    'name' => 'Portra 400',
                    'description' => 'A professional film.',
                    'officialDocumentationUrl' => 'https://www.kodak.com/portra400',
                    'isTranslated' => false,
                ],
                [
                    'name' => 'Ektar 100',
                    'description' => 'A vivid color film.',
                    'officialDocumentationUrl' => 'https://www.kodak.com/ektar100',
                    'isTranslated' => false,
                ],
            ],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/films', [
            'headers' => ['Accept-Language' => 'fr'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
        $this->assertJsonContains([
            'hydra:member' => [
                [
                    'name' => 'Portra 400',
                    'description' => 'Un film négatif couleur professionnel.',
                    'officialDocumentationUrl' => 'https://www.kodak.fr/portra400',
                    'isTranslated' => true,
                ],
                [
                    'name' => 'Ektar 100',
                    'description' => 'Un film négatif couleur aux couleurs vives.',
                    'officialDocumentationUrl' => 'https://www.kodak.fr/ektar100',
                    'isTranslated' => true,
                ],
            ],
        ]);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Security\Mock\KeycloakBearerUserMock;
use Gedmo\Translatable\Document\Translation;
use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;

abstract class AbstractFilmTestCase extends ApiTestCase
{
    protected static null|bool $alwaysBootKernel = false;

    protected DocumentManager $documentManager;

    protected function setUp(): void
    {
        $this->documentManager = static::getContainer()->get(DocumentManager::class);
        $this->clearDatabase();
    }

    protected function clearDatabase(): void
    {
        $this->documentManager->getDocumentCollection(Film::class)->drop();
        $this->documentManager->getDocumentCollection(Manufacturer::class)->drop();
        $this->documentManager->getDocumentCollection(Translation::class)->drop();
    }

    protected function createManufacturer(string $name = 'Kodak'): Manufacturer
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setName($name);
        $this->documentManager->persist($manufacturer);
        $this->documentManager->flush();

        return $manufacturer;
    }

    protected function createFilm(array $overrides = []): Film
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

    public static function loggedClientWithUserAndRoles(
        array $roles = [],
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'jc.bonnisseurdlb',
        array $attributes = [],
    ): Client {
        $client = static::createClient();
        $client->loginUser(
            new KeycloakBearerUserMock(
                $roles,
                $sub,
                $name,
                $email,
                $given_name,
                $family_name,
                $preferred_username,
                $attributes,
            ),
            'api',
        );
        return $client;
    }

    public static function loggedClientAdmin(
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'test_user_admin',
        array $attributes = [],
    ): Client {
        return self::loggedClientWithUserAndRoles(
            KeycloakRoles::ALL_ROLES,
            $sub,
            $name,
            $email,
            $given_name,
            $family_name,
            $preferred_username,
            $attributes,
        );
    }

    public static function loggedClientDataWriter(
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'test_user_data_writer',
        array $attributes = [],
    ): Client {
        return self::loggedClientWithUserAndRoles(
            [KeycloakRoles::DATA_WRITER, KeycloakRoles::DATA_READER, KeycloakRoles::USER],
            $sub,
            $name,
            $email,
            $given_name,
            $family_name,
            $preferred_username,
            $attributes,
        );
    }

    public static function loggedClientDataReader(
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'test_user_data_reader',
        array $attributes = [],
    ): Client {
        return self::loggedClientWithUserAndRoles(
            [KeycloakRoles::DATA_READER],
            $sub,
            $name,
            $email,
            $given_name,
            $family_name,
            $preferred_username,
            $attributes,
        );
    }

    public static function loggedClientUser(
        $sub = '',
        $name = 'Jean-Claude Bonnisseur de la Bath',
        $email = 'jc.bonnisseurdlb@example.test',
        $given_name = 'Jean-Claude',
        $family_name = 'Bonnisseur de la Bath',
        $preferred_username = 'test_user_user',
        array $attributes = [],
    ): Client {
        return self::loggedClientWithUserAndRoles(
            [KeycloakRoles::USER, KeycloakRoles::DATA_READER],
            $sub,
            $name,
            $email,
            $given_name,
            $family_name,
            $preferred_username,
            $attributes,
        );
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use FilmAnalogger\FilmAnaloggerApi\Document\ApproximateDate;
use FilmAnalogger\FilmAnaloggerApi\Document\Camera;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use FilmAnalogger\FilmAnaloggerApi\Document\Tag;
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
        $this->documentManager->getDocumentCollection(Chemistry::class)->drop();
        $this->documentManager->getDocumentCollection(ChemistryType::class)->drop();
        $this->documentManager->getDocumentCollection(Camera::class)->drop();
        $this->documentManager->getDocumentCollection(Tag::class)->drop();
        $this->documentManager->getDocumentCollection(DevelopmentLog::class)->drop();
        $this->documentManager->getDocumentCollection(Translation::class)->drop();
    }

    protected function createChemistryType(
        string $process = 'B&W',
        string $typeCode = 'BW_FILM_DEVELOPER',
        string $typeLabel = 'Film Developer',
    ): ChemistryType {
        $chemistryType = new ChemistryType();
        $chemistryType->process = $process;
        $chemistryType->setTypeCode($typeCode);
        $chemistryType->setTypeLabel($typeLabel);
        $this->documentManager->persist($chemistryType);
        $this->documentManager->flush();

        return $chemistryType;
    }

    protected function createChemistry(array $overrides = []): Chemistry
    {
        $manufacturer = $overrides['manufacturer'] ?? $this->createManufacturer();
        $chemistryType = $overrides['chemistryType'] ?? $this->createChemistryType();

        $chemistry = new Chemistry();
        $chemistry->setName($overrides['name'] ?? 'D-76');
        $chemistry->process = $overrides['process'] ?? 'B&W';
        $chemistry->setChemistryType($chemistryType);
        $chemistry->setManufacturer($manufacturer);

        if (isset($overrides['description'])) {
            $chemistry->setDescription($overrides['description']);
        }
        if (isset($overrides['officialDocumentationUrl'])) {
            $chemistry->setOfficialDocumentationUrl($overrides['officialDocumentationUrl']);
        }

        $this->documentManager->persist($chemistry);
        $this->documentManager->flush();

        return $chemistry;
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

    protected function createCamera(array $overrides = []): Camera
    {
        $manufacturer = $overrides['manufacturer'] ?? $this->createManufacturer();

        $camera = new Camera();
        $camera->setName($overrides['name'] ?? 'F100');
        $camera->setManufacturer($manufacturer);
        $camera->setFilmFormat($overrides['filmFormat'] ?? '135');

        if (isset($overrides['description'])) {
            $camera->setDescription($overrides['description']);
        }

        $this->documentManager->persist($camera);
        $this->documentManager->flush();

        return $camera;
    }

    protected function createTag(array $overrides = []): Tag
    {
        $tag = new Tag();
        $tag->setName($overrides['name'] ?? 'Fogged');

        if (isset($overrides['description'])) {
            $tag->setDescription($overrides['description']);
        }
        if (isset($overrides['primaryColor'])) {
            $tag->primaryColor = $overrides['primaryColor'];
        }

        $this->documentManager->persist($tag);
        $this->documentManager->flush();

        return $tag;
    }

    protected function createDevelopmentLog(array $overrides = []): DevelopmentLog
    {
        $film = $overrides['film'] ?? $this->createFilm();

        $shotAt = new ApproximateDate();
        $shotAt->setYear($overrides['shotAtYear'] ?? 2024);
        $shotAt->setMonth($overrides['shotAtMonth'] ?? 6);

        $developmentLog = new DevelopmentLog();
        $developmentLog
            ->setFilm($film)
            ->setCamera($overrides['camera'] ?? null)
            ->setShotAt($shotAt)
            ->setIsoShotAt($overrides['isoShotAt'] ?? $film->getSensibility())
            ->setProcess($overrides['process'] ?? $film->getProcess())
            ->setDevelopedAt($overrides['developedAt'] ?? new \DateTimeImmutable('2024-06-15'));

        if (isset($overrides['shootingNotes'])) {
            $developmentLog->setShootingNotes($overrides['shootingNotes']);
        }
        if (isset($overrides['developmentNotes'])) {
            $developmentLog->setDevelopmentNotes($overrides['developmentNotes']);
        }
        if (isset($overrides['rating'])) {
            $developmentLog->setRating($overrides['rating']);
        }
        if (isset($overrides['createdBy'])) {
            $developmentLog->setCreatedBy($overrides['createdBy']);
        }

        $this->documentManager->persist($developmentLog);
        $this->documentManager->flush();

        return $developmentLog;
    }

    // protected function assertArraysHaveIdenticalValues(array $actual, array $expected): void
    // {
    //     $this->assertCount(count($expected), $actual);
    //     foreach ($expected as $expectedItem) {
    //         $this->assertContains($expectedItem, $actual);
    //     }
    // }

    protected function assertUnauthorizedMissingToken(
        Client $client,
        string $method,
        string $uri,
        array $options = [],
    ): void {
        $client->request($method, $uri, $options);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);
    }

    protected function assertSuccessfulStatus(
        Client $client,
        string $method,
        string $uri,
        int $expectedStatus,
        array $options = [],
    ): void {
        $client->request($method, $uri, $options);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame($expectedStatus);
    }

    protected function assertForbiddenAccessDenied(
        Client $client,
        string $method,
        string $uri,
        array $options = [],
    ): void {
        $client->request($method, $uri, $options);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);
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

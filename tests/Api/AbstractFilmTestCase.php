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
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\ExposureKind;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBase;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBrand;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperGrade;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperSurface;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemicalBath;
use FilmAnalogger\FilmAnaloggerApi\Document\Dilution;
use FilmAnalogger\FilmAnaloggerApi\Document\Exposure;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintSession;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintWork;
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
        $this->documentManager->getDocumentCollection(PrintSession::class)->drop();
        $this->documentManager->getDocumentCollection(PrintWork::class)->drop();
        $this->documentManager->getDocumentCollection(Exposure::class)->drop();
    }

    protected function createChemistryType(
        string $process = 'B&W',
        string $typeCode = 'BW_FILM_DEVELOPER',
        string $typeLabel = 'Film Developer',
        CatalogStatus $status = CatalogStatus::OFFICIAL,
        ?string $createdBy = null,
    ): ChemistryType {
        $chemistryType = new ChemistryType();
        $chemistryType->process = $process;
        $chemistryType->setTypeCode($typeCode);
        $chemistryType->setTypeLabel($typeLabel);
        $chemistryType->setStatus($status);
        if ($createdBy !== null) {
            $chemistryType->setCreatedBy($createdBy);
        }
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
        $chemistry->setStatus($overrides['status'] ?? CatalogStatus::OFFICIAL);

        if (isset($overrides['description'])) {
            $chemistry->setDescription($overrides['description']);
        }
        if (isset($overrides['officialDocumentationUrl'])) {
            $chemistry->setOfficialDocumentationUrl($overrides['officialDocumentationUrl']);
        }
        if (isset($overrides['createdBy'])) {
            $chemistry->setCreatedBy($overrides['createdBy']);
        }

        $this->documentManager->persist($chemistry);
        $this->documentManager->flush();

        return $chemistry;
    }

    protected function createManufacturer(
        string $name = 'Kodak',
        CatalogStatus $status = CatalogStatus::OFFICIAL,
        ?string $createdBy = null,
    ): Manufacturer {
        $manufacturer = new Manufacturer();
        $manufacturer->setName($name);
        $manufacturer->setStatus($status);
        if ($createdBy !== null) {
            $manufacturer->setCreatedBy($createdBy);
        }
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
        $film->setStatus($overrides['status'] ?? CatalogStatus::OFFICIAL);

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
        if (isset($overrides['createdBy'])) {
            $film->setCreatedBy($overrides['createdBy']);
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
        $camera->setStatus($overrides['status'] ?? CatalogStatus::OFFICIAL);

        if (isset($overrides['description'])) {
            $camera->setDescription($overrides['description']);
        }
        if (isset($overrides['createdBy'])) {
            $camera->setCreatedBy($overrides['createdBy']);
        }

        $this->documentManager->persist($camera);
        $this->documentManager->flush();

        return $camera;
    }

    protected function createTag(array $overrides = []): Tag
    {
        $tag = new Tag();
        $tag->setName($overrides['name'] ?? 'Fogged');
        $tag->setStatus($overrides['status'] ?? CatalogStatus::OFFICIAL);

        if (isset($overrides['description'])) {
            $tag->setDescription($overrides['description']);
        }
        if (isset($overrides['primaryColor'])) {
            $tag->primaryColor = $overrides['primaryColor'];
        }
        if (isset($overrides['createdBy'])) {
            $tag->setCreatedBy($overrides['createdBy']);
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

    protected function createPaperChemistry(string $typeCode, array $overrides = []): Chemistry
    {
        $chemistryType = $this->createChemistryType(
            ProcessConstants::CHEMISTRY_BW_PRINT,
            $typeCode,
            $overrides['typeLabel'] ?? $typeCode,
        );

        $chemistry = new Chemistry();
        $chemistry->setName($overrides['name'] ?? $typeCode);
        $chemistry->process = ProcessConstants::CHEMISTRY_BW_PRINT;
        $chemistry->setChemistryType($chemistryType);
        $chemistry->setManufacturer($overrides['manufacturer'] ?? $this->createManufacturer());

        $dilution = new Dilution();
        $dilution
            ->setChemistryParts(1)
            ->setWaterParts($overrides['waterParts'] ?? 9)
            ->setOfficial(true);
        $chemistry->addDilution($dilution);

        $this->documentManager->persist($chemistry);
        $this->documentManager->flush();

        return $chemistry;
    }

    protected function createChemicalBath(array $overrides = []): ChemicalBath
    {
        $bath = new ChemicalBath();
        $bath
            ->setChemistry($overrides['chemistry'] ?? $this->createPaperChemistry('BW_PAPER_DEVELOPER'))
            ->setDurationSeconds($overrides['durationSeconds'] ?? 60);

        if (isset($overrides['dilutionOverride'])) {
            $bath->setDilutionOverride($overrides['dilutionOverride']);
        }

        return $bath;
    }

    protected function createPrintSession(array $overrides = []): PrintSession
    {
        $session = new PrintSession();
        $session
            ->setDate($overrides['date'] ?? new \DateTimeImmutable('2026-01-15'))
            ->setLab($overrides['lab'] ?? 'Garage')
            ->setNumber($overrides['number'] ?? 1)
            ->setEnlarger($overrides['enlarger'] ?? 'Durst M805')
            ->setTemperatureCelsius($overrides['temperatureCelsius'] ?? 20.0);

        $chemicalBaths =
            $overrides['chemicalBaths'] ??
            [
                $this->createChemicalBath(['chemistry' => $this->createPaperChemistry('BW_PAPER_DEVELOPER')]),
                $this->createChemicalBath(['chemistry' => $this->createPaperChemistry('STOP')]),
                $this->createChemicalBath(['chemistry' => $this->createPaperChemistry('FIXER')]),
            ];
        foreach ($chemicalBaths as $bath) {
            $session->addChemicalBath($bath);
        }

        if (isset($overrides['wash'])) {
            $session->setWash($overrides['wash']);
        }
        if (isset($overrides['notes'])) {
            $session->setNotes($overrides['notes']);
        }

        $this->documentManager->persist($session);
        $this->documentManager->flush();

        return $session;
    }

    protected function createExposure(array $overrides = []): Exposure
    {
        $exposure = new Exposure();
        $exposure
            ->setOrder($overrides['order'] ?? 1)
            ->setKind($overrides['kind'] ?? ExposureKind::BASE)
            ->setBaseSeconds($overrides['baseSeconds'] ?? 32.0)
            ->setStopOffsetNumerator($overrides['stopOffsetNumerator'] ?? 0)
            ->setStopOffsetDenominator($overrides['stopOffsetDenominator'] ?? 1)
            ->setGrade($overrides['grade'] ?? PaperGrade::G2);

        if (isset($overrides['aperture'])) {
            $exposure->setAperture($overrides['aperture']);
        }
        if (isset($overrides['observation'])) {
            $exposure->setObservation($overrides['observation']);
        }

        return $exposure;
    }

    protected function createPrintWork(array $overrides = []): PrintWork
    {
        $print = new PrintWork();
        $print
            ->setSession($overrides['session'] ?? $this->createPrintSession())
            ->setNumber($overrides['number'] ?? 1)
            ->setPaperBrand($overrides['paperBrand'] ?? PaperBrand::ILFORD)
            ->setPaperBase($overrides['paperBase'] ?? PaperBase::RC)
            ->setPaperSurface($overrides['paperSurface'] ?? PaperSurface::GLOSSY)
            ->setPaperWidthCm($overrides['paperWidthCm'] ?? 18.0)
            ->setPaperHeightCm($overrides['paperHeightCm'] ?? 24.0);

        foreach ($overrides['exposures'] ?? [$this->createExposure()] as $exposure) {
            $print->addExposure($exposure);
        }

        $this->documentManager->persist($print);
        $this->documentManager->flush();

        return $print;
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

<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\Condenser;
use FilmAnalogger\FilmAnaloggerApi\Constant\ExposureKind;
use FilmAnalogger\FilmAnaloggerApi\Constant\FocalLength;
use FilmAnalogger\FilmAnaloggerApi\Constant\NegativeFormat;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBase;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBrand;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperGrade;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperSurface;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemicalBath;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\Exposure;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintSession;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintWork;

// Two real darkroom sessions (2026-07-28/29, "Garage", Durst M805), taken
// from an actual paper-form journal. Deliberately keeps the tricky cases the
// journal itself contains: a `1/2` grade (the 0.5-vs-1.5 misreading trap), a
// `+1/3` stop offset on the base exposure, a two-exposure print (base +
// burn), and a session-2 temperature drift (27°C then 26°C — outside
// Ilford's recommended 18-24°C window, and the model doesn't clamp it).
//
// All sessions are owned by one of the 4 AppUserFixtures test users
// (createdBy is only stamped automatically by the Blameable listener during
// an authenticated HTTP request, never during a fixtures load — set
// explicitly here instead) so that OwnedByCurrentUserExtension's per-user
// collection scoping has real data to exercise. The journal sessions belong
// to test_writer (the role that can actually POST sessions through the real
// API); the other 3 users each get 2 more sessions of their own, with
// slightly different lab/enlarger/chemistry/timings.
class PrintSessionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $developer = $this->getReference(ChemistryFixtures::ILFORD_BROMOPHEN, Chemistry::class);
        $stopBath = $this->getReference(ChemistryFixtures::HOMEMADE_ACETIC_ACID_STOP, Chemistry::class);
        $fixer = $this->getReference(ChemistryFixtures::ILFORD_RAPID_FIXER, Chemistry::class);

        $sessionOne = new PrintSession();
        $sessionOne
            ->setDate(new \DateTimeImmutable('2026-07-28'))
            ->setLab('Garage')
            ->setNumber(1)
            ->setEnlarger('Durst M805')
            ->setTemperatureCelsius(27.0)
            ->setWash("Bain d'eau")
            ->setCreatedBy(AppUserFixtures::TEST_WRITER_USERNAME)
            ->addChemicalBath(new ChemicalBath()->setChemistry($developer)->setDurationSeconds(90))
            ->addChemicalBath(new ChemicalBath()->setChemistry($stopBath)->setDurationSeconds(15))
            ->addChemicalBath(new ChemicalBath()->setChemistry($fixer)->setDurationSeconds(60));

        $manager->persist($sessionOne);
        $manager->flush();

        $this->createPrint($manager, $sessionOne, [
            'number' => 1,
            'contactSheetRef' => '77',
            'negativeNumber' => '19',
            'columnHeightCm' => 45.6,
            'paperWidthCm' => 30.0,
            'paperHeightCm' => 24.0,
            'exposures' => [
                ['kind' => ExposureKind::BASE, 'baseSeconds' => 16.0, 'num' => 1, 'denom' => 3, 'grade' => PaperGrade::G2],
            ],
        ]);

        $this->createPrint($manager, $sessionOne, [
            'number' => 2,
            'contactSheetRef' => '77',
            'negativeNumber' => '20',
            'columnHeightCm' => 45.6,
            'paperWidthCm' => 30.0,
            'paperHeightCm' => 24.0,
            'exposures' => [
                ['kind' => ExposureKind::BASE, 'baseSeconds' => 16.0, 'grade' => PaperGrade::G2],
            ],
        ]);

        $this->createPrint($manager, $sessionOne, [
            'number' => 3,
            'contactSheetRef' => '77',
            'negativeNumber' => '22',
            'columnHeightCm' => 45.6,
            'paperWidthCm' => 30.0,
            'paperHeightCm' => 24.0,
            'exposures' => [
                // Written "1/2" on the paper form: grade 0.5, not grade 1.5 —
                // exactly the misreading trap PaperGrade::G0_5 exists for.
                ['kind' => ExposureKind::BASE, 'baseSeconds' => 32.0, 'num' => 1, 'denom' => 3, 'grade' => PaperGrade::G0_5],
            ],
        ]);

        $sessionTwo = new PrintSession();
        $sessionTwo
            ->setDate(new \DateTimeImmutable('2026-07-29'))
            ->setLab('Garage')
            ->setNumber(2)
            ->setEnlarger('Durst M805')
            ->setTemperatureCelsius(27.0)
            ->setWash('2 bains 5 min')
            ->setNotes('Température a dérivé de 27°C à 26°C en fin de séance (hors plage Ilford 18-24°C).')
            ->setCreatedBy(AppUserFixtures::TEST_WRITER_USERNAME)
            ->addChemicalBath(new ChemicalBath()->setChemistry($developer)->setDurationSeconds(90))
            ->addChemicalBath(new ChemicalBath()->setChemistry($stopBath)->setDurationSeconds(15))
            ->addChemicalBath(new ChemicalBath()->setChemistry($fixer)->setDurationSeconds(60));

        $manager->persist($sessionTwo);
        $manager->flush();

        $this->createPrint($manager, $sessionTwo, [
            'number' => 1,
            'contactSheetRef' => '77',
            'negativeNumber' => '29',
            'columnHeightCm' => 53.2,
            'paperWidthCm' => 24.0,
            'paperHeightCm' => 30.0,
            'exposures' => [
                ['kind' => ExposureKind::BASE, 'baseSeconds' => 32.0, 'num' => 1, 'denom' => 3, 'grade' => PaperGrade::G1],
            ],
        ]);

        $this->createPrint($manager, $sessionTwo, [
            'number' => 2,
            'contactSheetRef' => '79',
            'negativeNumber' => '28',
            'columnHeightCm' => 46.0,
            'paperWidthCm' => 24.0,
            'paperHeightCm' => 30.0,
            'maskingNotes' => 'Rectangle + 2 personnages',
            'notes' => 'Pull (retenir) 16 s sur le public — voir croquis sur la fiche papier.',
            'exposures' => [
                ['kind' => ExposureKind::BASE, 'baseSeconds' => 32.0, 'num' => 1, 'denom' => 3, 'grade' => PaperGrade::G1],
                [
                    'kind' => ExposureKind::BURN,
                    'baseSeconds' => 16.0,
                    'grade' => PaperGrade::G1,
                    'observation' => 'Pull (retenir) sur le public',
                ],
            ],
        ]);

        $manager->flush();

        $multigradeDeveloper = $this->getReference(
            ChemistryFixtures::ILFORD_MULTIGRADE_DEVELOPER,
            Chemistry::class,
        );
        $ilfostop = $this->getReference(ChemistryFixtures::ILFORD_ILFOSTOP, Chemistry::class);

        // test_reader: 2 sessions, own lab/enlarger, Multigrade developer + Ilfostop.
        $this->createSession(
            $manager,
            [
                'date' => '2026-07-25',
                'lab' => 'Salle de bain',
                'number' => 1,
                'enlarger' => 'Meopta Anaret',
                'temperatureCelsius' => 20.0,
                'wash' => "Bain d'eau",
                'createdBy' => AppUserFixtures::TEST_READER_USERNAME,
                'chemicalBaths' => [[$multigradeDeveloper, 60], [$ilfostop, 10], [$fixer, 120]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '12',
                    'negativeNumber' => '5',
                    'columnHeightCm' => 40.0,
                    'paperWidthCm' => 24.0,
                    'paperHeightCm' => 30.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 12.0, 'grade' => PaperGrade::G3],
                    ],
                ],
            ],
        );

        $this->createSession(
            $manager,
            [
                'date' => '2026-07-27',
                'lab' => 'Salle de bain',
                'number' => 2,
                'enlarger' => 'Meopta Anaret',
                'temperatureCelsius' => 20.5,
                'wash' => "Bain d'eau",
                'createdBy' => AppUserFixtures::TEST_READER_USERNAME,
                'chemicalBaths' => [[$multigradeDeveloper, 65], [$ilfostop, 10], [$fixer, 130]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '13',
                    'negativeNumber' => '9',
                    'columnHeightCm' => 42.0,
                    'paperWidthCm' => 24.0,
                    'paperHeightCm' => 30.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 14.0, 'grade' => PaperGrade::G2_5],
                    ],
                ],
            ],
        );

        // test_user: 2 sessions, same lab/enlarger/chemistry as the journal
        // sessions but different negatives/timings.
        $this->createSession(
            $manager,
            [
                'date' => '2026-07-26',
                'lab' => 'Garage',
                'number' => 1,
                'enlarger' => 'Durst M805',
                'temperatureCelsius' => 21.5,
                'wash' => "Bain d'eau",
                'createdBy' => AppUserFixtures::TEST_USER_USERNAME,
                'chemicalBaths' => [[$developer, 90], [$stopBath, 15], [$fixer, 60]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '81',
                    'negativeNumber' => '3',
                    'columnHeightCm' => 45.6,
                    'paperWidthCm' => 30.0,
                    'paperHeightCm' => 24.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 18.0, 'grade' => PaperGrade::G2],
                    ],
                ],
            ],
        );

        $this->createSession(
            $manager,
            [
                'date' => '2026-07-27',
                'lab' => 'Garage',
                'number' => 2,
                'enlarger' => 'Durst M805',
                'temperatureCelsius' => 22.0,
                'wash' => "Bain d'eau",
                'createdBy' => AppUserFixtures::TEST_USER_USERNAME,
                'chemicalBaths' => [[$developer, 85], [$stopBath, 15], [$fixer, 65]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '82',
                    'negativeNumber' => '7',
                    'columnHeightCm' => 44.0,
                    'paperWidthCm' => 30.0,
                    'paperHeightCm' => 24.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 20.0, 'grade' => PaperGrade::G2],
                    ],
                ],
            ],
        );

        // test_admin: 2 sessions, own lab/enlarger, Multigrade developer.
        $this->createSession(
            $manager,
            [
                'date' => '2026-07-30',
                'lab' => 'Labo École',
                'number' => 1,
                'enlarger' => 'Durst M605',
                'temperatureCelsius' => 19.0,
                'wash' => '2 bains 5 min',
                'createdBy' => AppUserFixtures::TEST_ADMIN_USERNAME,
                'chemicalBaths' => [[$multigradeDeveloper, 75], [$stopBath, 15], [$fixer, 90]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '3',
                    'negativeNumber' => '11',
                    'columnHeightCm' => 50.0,
                    'paperWidthCm' => 30.0,
                    'paperHeightCm' => 40.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 22.0, 'grade' => PaperGrade::G1],
                    ],
                ],
            ],
        );

        $this->createSession(
            $manager,
            [
                'date' => '2026-08-01',
                'lab' => 'Labo École',
                'number' => 2,
                'enlarger' => 'Durst M605',
                'temperatureCelsius' => 18.5,
                'wash' => '2 bains 5 min',
                'createdBy' => AppUserFixtures::TEST_ADMIN_USERNAME,
                'chemicalBaths' => [[$multigradeDeveloper, 70], [$stopBath, 15], [$fixer, 85]],
            ],
            [
                [
                    'number' => 1,
                    'contactSheetRef' => '4',
                    'negativeNumber' => '14',
                    'columnHeightCm' => 48.0,
                    'paperWidthCm' => 30.0,
                    'paperHeightCm' => 40.0,
                    'exposures' => [
                        ['kind' => ExposureKind::BASE, 'baseSeconds' => 24.0, 'grade' => PaperGrade::G1_5],
                    ],
                ],
            ],
        );
    }

    private function createSession(ObjectManager $manager, array $sessionData, array $prints): PrintSession
    {
        $session = new PrintSession();
        $session
            ->setDate(new \DateTimeImmutable($sessionData['date']))
            ->setLab($sessionData['lab'])
            ->setNumber($sessionData['number'])
            ->setEnlarger($sessionData['enlarger'])
            ->setTemperatureCelsius($sessionData['temperatureCelsius'])
            ->setWash($sessionData['wash'] ?? null)
            ->setNotes($sessionData['notes'] ?? null)
            ->setCreatedBy($sessionData['createdBy']);

        foreach ($sessionData['chemicalBaths'] as [$chemistry, $durationSeconds]) {
            $session->addChemicalBath(
                new ChemicalBath()->setChemistry($chemistry)->setDurationSeconds($durationSeconds),
            );
        }

        $manager->persist($session);
        $manager->flush();

        foreach ($prints as $printData) {
            $this->createPrint($manager, $session, $printData);
        }

        return $session;
    }

    private function createPrint(ObjectManager $manager, PrintSession $session, array $data): void
    {
        $print = new PrintWork();
        $print
            ->setSession($session)
            ->setNumber($data['number'])
            ->setFilmFormat('135')
            ->setContactSheetRef($data['contactSheetRef'])
            ->setNegativeNumber($data['negativeNumber'])
            ->setNegativeFormat(NegativeFormat::F_24X36)
            ->setFocalLength(FocalLength::F50)
            ->setCondensers([Condenser::BIMACON_75->value, Condenser::FEMOCON_50->value])
            ->setColumnHeightCm($data['columnHeightCm'])
            ->setPaperWidthCm($data['paperWidthCm'])
            ->setPaperHeightCm($data['paperHeightCm'])
            ->setBorderCm(1.4)
            ->setCopies(1)
            ->setPaperBrand(PaperBrand::ILFORD)
            ->setPaperModel('Multigrade')
            ->setPaperBase(PaperBase::RC)
            ->setPaperSurface(PaperSurface::PEARL)
            ->setMaskingNotes($data['maskingNotes'] ?? null)
            ->setNotes($data['notes'] ?? null);

        foreach ($data['exposures'] as $order => $exposureData) {
            $print->addExposure(
                new Exposure()
                    ->setOrder($order + 1)
                    ->setKind($exposureData['kind'])
                    ->setBaseSeconds($exposureData['baseSeconds'])
                    ->setStopOffsetNumerator($exposureData['num'] ?? 0)
                    ->setStopOffsetDenominator($exposureData['denom'] ?? 1)
                    ->setGrade($exposureData['grade'])
                    ->setAperture('f/8')
                    ->setObservation($exposureData['observation'] ?? null),
            );
        }

        $manager->persist($print);
    }

    public function getDependencies(): array
    {
        return [ChemistryFixtures::class, AppUserFixtures::class];
    }
}

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
// These sessions have no owner (createdBy is only stamped by the Blameable
// listener during an authenticated HTTP request, never during a fixtures
// load) — only ROLE_admin will see them through the API, since
// PrintSession/Print collections are scoped to the current user otherwise.
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
        return [ChemistryFixtures::class];
    }
}

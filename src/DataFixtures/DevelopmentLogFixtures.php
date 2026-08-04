<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Document\ApproximateDate;
use FilmAnalogger\FilmAnaloggerApi\Document\Camera;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentStep;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Tag;

// Development logs for the rolls behind the PrintSessionFixtures journal: the
// contactSheetNumber values below (77, 79, 81, 82, 12, 13, 3, 4) deliberately
// match PrintWork::$contactSheetRef in PrintSessionFixtures, so a print and
// the negative it was made from resolve to the same physical contact sheet.
// Film/Camera/Chemistry aren't referenced by fixture reference (unlike
// Manufacturer/Tag/Enlarger) since none of FilmFixtures/CameraFixtures/
// ChemistryFixtures register one, so they're looked up by name instead.
class DevelopmentLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $hp5 = $this->film($manager, 'HP5 Plus');
        $portra400 = $this->film($manager, 'Portra 400');
        $triX = $this->film($manager, 'Tri-X 400');
        $delta3200 = $this->film($manager, 'Delta 3200');

        $f100 = $this->camera($manager, 'F100');
        $ae1 = $this->camera($manager, 'AE-1');
        $mx = $this->camera($manager, 'MX');
        $m6 = $this->camera($manager, 'M6');

        // 'Ilfostop' and 'Rapid Fixer' each exist twice in ChemistryFixtures
        // (once for film under B&W, once for paper under B&W Print), so the
        // process disambiguates which one is fetched.
        $d76 = $this->chemistry($manager, 'D-76', 'B&W');
        $ilfostop = $this->chemistry($manager, 'Ilfostop', 'B&W');
        $rapidFixer = $this->chemistry($manager, 'Rapid Fixer', 'B&W');
        $c41Developer = $this->chemistry($manager, 'Colortec C-41 Developer', 'C-41');
        $c41BleachFix = $this->chemistry($manager, 'Colortec C-41 Bleach Fix', 'C-41');
        $c41Stabilizer = $this->chemistry($manager, 'Colortec C-41 Stabilizer', 'C-41');

        $this->createLog($manager, [
            'film' => $hp5,
            'camera' => $f100,
            'shotAt' => ['year' => 2026, 'month' => 7],
            'isoShotAt' => 400,
            'process' => 'B&W',
            'developedAt' => '2026-07-28',
            'steps' => [
                [$d76, 1, 1, 20.0, 720, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, 'Continuous agitation'],
            ],
            'binderId' => '00',
            'contactSheetNumber' => 77,
            'createdBy' => AppUserFixtures::TEST_WRITER_USERNAME,
        ]);

        $this->createLog($manager, [
            'film' => $hp5,
            'camera' => $f100,
            'shotAt' => ['year' => 2026, 'month' => 7],
            'isoShotAt' => 400,
            'process' => 'B&W',
            'developedAt' => '2026-07-29',
            'steps' => [
                [$d76, 1, 1, 20.0, 720, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, 'Continuous agitation'],
            ],
            'binderId' => '00',
            'contactSheetNumber' => 79,
            'createdBy' => AppUserFixtures::TEST_WRITER_USERNAME,
            'tags' => [TagFixtures::UNEVEN_DEVELOPMENT],
        ]);

        $this->createLog($manager, [
            'film' => $portra400,
            'camera' => $mx,
            'shotAt' => ['year' => 2026, 'month' => 7, 'day' => 20],
            'isoShotAt' => 400,
            'process' => 'C-41',
            'developedAt' => '2026-07-26',
            'steps' => [
                [$c41Developer, 1, 0, 38.0, 195, 'Rotary, continuous'],
                [$c41BleachFix, 1, 0, 38.0, 480, null],
                [$c41Stabilizer, 1, 0, 25.0, 60, null],
            ],
            'binderId' => '01',
            'contactSheetNumber' => 81,
            'createdBy' => AppUserFixtures::TEST_USER_USERNAME,
        ]);

        $this->createLog($manager, [
            'film' => $portra400,
            'camera' => $mx,
            'shotAt' => ['year' => 2026, 'month' => 7, 'day' => 25],
            'isoShotAt' => 400,
            'process' => 'C-41',
            'developedAt' => '2026-07-27',
            'steps' => [
                [$c41Developer, 1, 0, 38.0, 195, 'Rotary, continuous'],
                [$c41BleachFix, 1, 0, 38.0, 480, null],
                [$c41Stabilizer, 1, 0, 25.0, 60, null],
            ],
            'binderId' => '01',
            'contactSheetNumber' => 82,
            'createdBy' => AppUserFixtures::TEST_USER_USERNAME,
        ]);

        $this->createLog($manager, [
            'film' => $triX,
            'camera' => $ae1,
            'shotAt' => ['year' => 2026, 'month' => 7, 'day' => 10],
            'isoShotAt' => 400,
            'process' => 'B&W',
            'developedAt' => '2026-07-25',
            'steps' => [
                [$d76, 1, 1, 20.0, 660, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, null],
            ],
            'binderId' => '02',
            'contactSheetNumber' => 12,
            'createdBy' => AppUserFixtures::TEST_READER_USERNAME,
        ]);

        $this->createLog($manager, [
            'film' => $triX,
            'camera' => $ae1,
            'shotAt' => ['year' => 2026, 'month' => 7, 'day' => 15],
            'isoShotAt' => 400,
            'process' => 'B&W',
            'developedAt' => '2026-07-27',
            'steps' => [
                [$d76, 1, 1, 20.0, 660, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, null],
            ],
            'binderId' => '02',
            'contactSheetNumber' => 13,
            'createdBy' => AppUserFixtures::TEST_READER_USERNAME,
        ]);

        $this->createLog($manager, [
            'film' => $delta3200,
            'camera' => $m6,
            'shotAt' => ['year' => 2026, 'month' => 7, 'day' => 29],
            'isoShotAt' => 3200,
            'process' => 'B&W',
            'developedAt' => '2026-07-30',
            'shootingNotes' => 'Low-light concert, pushed one stop.',
            'steps' => [
                [$d76, 1, 1, 20.0, 900, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, null],
            ],
            'binderId' => '03',
            'contactSheetNumber' => 3,
            'createdBy' => AppUserFixtures::TEST_ADMIN_USERNAME,
            'rating' => 5,
            'tags' => [TagFixtures::DENSE_NEGATIVES],
        ]);

        // Not archived yet: no binder/contact sheet assigned, exercising the
        // fields' nullability.
        $this->createLog($manager, [
            'film' => $delta3200,
            'camera' => $m6,
            'shotAt' => ['year' => 2026, 'month' => 8, 'day' => 3],
            'isoShotAt' => 3200,
            'process' => 'B&W',
            'developedAt' => '2026-08-04',
            'steps' => [
                [$d76, 1, 1, 20.0, 900, '10s initial, 5s every minute'],
                [$ilfostop, 1, 19, 20.0, 30, null],
                [$rapidFixer, 1, 4, 20.0, 300, null],
            ],
            'createdBy' => AppUserFixtures::TEST_ADMIN_USERNAME,
        ]);

        $manager->flush();
    }

    private function createLog(ObjectManager $manager, array $data): DevelopmentLog
    {
        $shotAt = new ApproximateDate();
        $shotAt->setYear($data['shotAt']['year']);
        $shotAt->setMonth($data['shotAt']['month'] ?? null);
        $shotAt->setDay($data['shotAt']['day'] ?? null);

        $log = new DevelopmentLog();
        $log->setFilm($data['film'])
            ->setCamera($data['camera'])
            ->setShotAt($shotAt)
            ->setIsoShotAt($data['isoShotAt'])
            ->setShootingNotes($data['shootingNotes'] ?? null)
            ->setProcess($data['process'])
            ->setDevelopedAt(new \DateTimeImmutable($data['developedAt']))
            ->setDevelopmentNotes($data['developmentNotes'] ?? null)
            ->setRating($data['rating'] ?? null)
            ->setBinderId($data['binderId'] ?? null)
            ->setContactSheetNumber($data['contactSheetNumber'] ?? null)
            ->setCreatedBy($data['createdBy']);

        foreach (
            $data['steps']
            as [$chemistry, $chemParts, $waterParts, $temperature, $durationSeconds, $agitationNote]
        ) {
            $log->addStep(
                new DevelopmentStep()
                    ->setChemistry($chemistry)
                    ->setChemistryParts($chemParts)
                    ->setWaterParts($waterParts)
                    ->setTemperature($temperature)
                    ->setDurationSeconds($durationSeconds)
                    ->setAgitationNote($agitationNote),
            );
        }

        foreach ($data['tags'] ?? [] as $tagReference) {
            $log->addTag($this->getReference($tagReference, Tag::class));
        }

        $manager->persist($log);

        return $log;
    }

    private function film(ObjectManager $manager, string $name): Film
    {
        return $manager->getRepository(Film::class)->findOneBy(['name' => $name]);
    }

    private function camera(ObjectManager $manager, string $name): Camera
    {
        return $manager->getRepository(Camera::class)->findOneBy(['name' => $name]);
    }

    private function chemistry(ObjectManager $manager, string $name, string $process): Chemistry
    {
        return $manager
            ->getRepository(Chemistry::class)
            ->findOneBy(['name' => $name, 'process' => $process]);
    }

    public function getDependencies(): array
    {
        return [
            FilmFixtures::class,
            CameraFixtures::class,
            ChemistryFixtures::class,
            TagFixtures::class,
            AppUserFixtures::class,
        ];
    }
}

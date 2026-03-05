<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;

class ChemistryTypeFixtures extends Fixture
{
    public const BW_FILM_DEVELOPER = 'chemistry-type-bw-film-developer';
    public const BW_FIXER = 'chemistry-type-bw-fixer';
    public const BW_STOP = 'chemistry-type-bw-stop';
    public const BW_WETTING_AGENT = 'chemistry-type-bw-wetting-agent';
    public const BW_FILM_CLEANER = 'chemistry-type-bw-film-cleaner';
    public const C41_COLOR_DEVELOPER = 'chemistry-type-c41-color-developer';
    public const C41_BLEACH = 'chemistry-type-c41-bleach';
    public const C41_STABILIZER = 'chemistry-type-c41-stabilizer';
    public const C41_WETTING_AGENT = 'chemistry-type-c41-wetting-agent';
    public const E6_FILM_DEVELOPER = 'chemistry-type-e6-film-developer';
    public const E6_COLOR_DEVELOPER = 'chemistry-type-e6-color-developer';
    public const E6_BLEACH = 'chemistry-type-e6-bleach';
    public const E6_STABILIZER = 'chemistry-type-e6-stabilizer';
    public const RA4_COLOR_DEVELOPER = 'chemistry-type-ra4-color-developer';
    public const RA4_BLEACH = 'chemistry-type-ra4-bleach';
    public const RA4_FIXER = 'chemistry-type-ra4-fixer';
    public const RA4_STABILIZER = 'chemistry-type-ra4-stabilizer';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $data) {
            $type = new ChemistryType();
            $type->process = $data['process'];
            $type
                ->setName($data['name'])
                ->setTypeCode($data['typeCode'])
                ->setTypeLabel($data['typeLabel']);

            $manager->persist($type);
            $this->addReference($data['reference'], $type);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            // ── B&W ──────────────────────────────────────────────────────────
            [
                'reference' => self::BW_FILM_DEVELOPER,
                'name' => 'B&W Film Developer',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_BW_FILM_DEVELOPER,
                'typeLabel' => 'Film Developer',
            ],
            [
                'reference' => self::BW_FIXER,
                'name' => 'B&W Fixer',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_FIXER,
                'typeLabel' => 'Fixer',
            ],
            [
                'reference' => self::BW_STOP,
                'name' => 'B&W Stop Bath',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_STOP,
                'typeLabel' => 'Stop Bath',
            ],
            [
                'reference' => self::BW_WETTING_AGENT,
                'name' => 'B&W Wetting Agent',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_WETTING_AGENT,
                'typeLabel' => 'Wetting Agent',
            ],
            [
                'reference' => self::BW_FILM_CLEANER,
                'name' => 'B&W Film Cleaner',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_FILM_CLEANER,
                'typeLabel' => 'Film Cleaner',
            ],

            // ── C-41 ─────────────────────────────────────────────────────────
            [
                'reference' => self::C41_COLOR_DEVELOPER,
                'name' => 'C-41 Color Developer',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_C41_COLOR_DEVELOPER,
                'typeLabel' => 'Color Developer',
            ],
            [
                'reference' => self::C41_BLEACH,
                'name' => 'C-41 Bleach',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
            ],
            [
                'reference' => self::C41_STABILIZER,
                'name' => 'C-41 Stabilizer',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
            ],
            [
                'reference' => self::C41_WETTING_AGENT,
                'name' => 'C-41 Wetting Agent',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_WETTING_AGENT,
                'typeLabel' => 'Wetting Agent',
            ],

            // ── E-6 ──────────────────────────────────────────────────────────
            [
                'reference' => self::E6_FILM_DEVELOPER,
                'name' => 'E-6 First Developer',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_E6_FILM_DEVELOPER,
                'typeLabel' => 'First Developer',
            ],
            [
                'reference' => self::E6_COLOR_DEVELOPER,
                'name' => 'E-6 Color Developer',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_E6_COLOR_DEVELOPER,
                'typeLabel' => 'Color Developer',
            ],
            [
                'reference' => self::E6_BLEACH,
                'name' => 'E-6 Bleach',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
            ],
            [
                'reference' => self::E6_STABILIZER,
                'name' => 'E-6 Stabilizer',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
            ],

            // ── RA-4 ─────────────────────────────────────────────────────────
            [
                'reference' => self::RA4_COLOR_DEVELOPER,
                'name' => 'RA-4 Color Developer',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_RA4_COLOR_DEVELOPER,
                'typeLabel' => 'Color Developer',
            ],
            [
                'reference' => self::RA4_BLEACH,
                'name' => 'RA-4 Bleach',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
            ],
            [
                'reference' => self::RA4_FIXER,
                'name' => 'RA-4 Fixer',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_FIXER,
                'typeLabel' => 'Fixer',
            ],
            [
                'reference' => self::RA4_STABILIZER,
                'name' => 'RA-4 Stabilizer',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
            ],
        ];
    }
}

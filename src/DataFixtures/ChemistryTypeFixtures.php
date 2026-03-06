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
        $entities = [];

        foreach ($this->getData() as $data) {
            $type = new ChemistryType();
            $type->process = $data['process'];
            $type->setTypeCode($data['typeCode'])->setTypeLabel($data['typeLabel']);

            $manager->persist($type);
            $this->addReference($data['reference'], $type);
            $entities[] = [$type, $data];
        }

        $manager->flush();

        foreach ($entities as [$type, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $type->setTranslatableLocale($locale);
                if (isset($translations['typeLabel'])) {
                    $type->setTypeLabel($translations['typeLabel']);
                }
                $manager->persist($type);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            // ── B&W ──────────────────────────────────────────────────────────
            [
                'reference' => self::BW_FILM_DEVELOPER,
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_BW_FILM_DEVELOPER,
                'typeLabel' => 'B&W Film Developer',
                'translations' => ['fr' => ['typeLabel' => 'Révélateur noir et blanc']],
            ],
            [
                'reference' => self::BW_FIXER,
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_FIXER,
                'typeLabel' => 'Fixer',
                'translations' => ['fr' => ['typeLabel' => 'Fixateur']],
            ],
            [
                'reference' => self::BW_STOP,
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_STOP,
                'typeLabel' => 'Stop Bath',
                'translations' => ['fr' => ['typeLabel' => 'Bain d\'arrêt']],
            ],
            [
                'reference' => self::BW_WETTING_AGENT,
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_WETTING_AGENT,
                'typeLabel' => 'Wetting Agent',
                'translations' => ['fr' => ['typeLabel' => 'Agent mouillant']],
            ],
            [
                'reference' => self::BW_FILM_CLEANER,
                'process' => ProcessConstants::CHEMISTRY_BW,
                'typeCode' => ProcessConstants::CHEMISTRY_FILM_CLEANER,
                'typeLabel' => 'Film Cleaner',
                'translations' => ['fr' => ['typeLabel' => 'Nettoyant pour film']],
            ],

            // ── C-41 ─────────────────────────────────────────────────────────
            [
                'reference' => self::C41_COLOR_DEVELOPER,
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_C41_COLOR_DEVELOPER,
                'typeLabel' => 'Color Developer',
                'translations' => ['fr' => ['typeLabel' => 'Révélateur couleur']],
            ],
            [
                'reference' => self::C41_BLEACH,
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
                'translations' => ['fr' => ['typeLabel' => 'Blanchiment']],
            ],
            [
                'reference' => self::C41_STABILIZER,
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
                'translations' => ['fr' => ['typeLabel' => 'Stabilisateur']],
            ],
            [
                'reference' => self::C41_WETTING_AGENT,
                'process' => ProcessConstants::CHEMISTRY_C41,
                'typeCode' => ProcessConstants::CHEMISTRY_WETTING_AGENT,
                'typeLabel' => 'Wetting Agent',
                'translations' => ['fr' => ['typeLabel' => 'Agent mouillant']],
            ],

            // ── E-6 ──────────────────────────────────────────────────────────
            [
                'reference' => self::E6_FILM_DEVELOPER,
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_E6_FILM_DEVELOPER,
                'typeLabel' => 'E-6 Film Developer',
                'translations' => ['fr' => ['typeLabel' => 'Révélateur Film E-6']],
            ],
            [
                'reference' => self::E6_COLOR_DEVELOPER,
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_E6_COLOR_DEVELOPER,
                'typeLabel' => 'E-6 Color Developer',
                'translations' => ['fr' => ['typeLabel' => 'Révélateur couleur E-6']],
            ],
            [
                'reference' => self::E6_BLEACH,
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
                'translations' => ['fr' => ['typeLabel' => 'Blanchiment']],
            ],
            [
                'reference' => self::E6_STABILIZER,
                'process' => ProcessConstants::CHEMISTRY_E6,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
                'translations' => ['fr' => ['typeLabel' => 'Stabilisateur']],
            ],

            // ── RA-4 ─────────────────────────────────────────────────────────
            [
                'reference' => self::RA4_COLOR_DEVELOPER,
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_RA4_COLOR_DEVELOPER,
                'typeLabel' => 'RA-4 Color Developer',
                'translations' => ['fr' => ['typeLabel' => 'Révélateur couleur RA-4']],
            ],
            [
                'reference' => self::RA4_BLEACH,
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_BLEACH,
                'typeLabel' => 'Bleach',
                'translations' => ['fr' => ['typeLabel' => 'Blanchisseur']],
            ],
            [
                'reference' => self::RA4_FIXER,
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_FIXER,
                'typeLabel' => 'Fixer',
                'translations' => ['fr' => ['typeLabel' => 'Fixateur']],
            ],
            [
                'reference' => self::RA4_STABILIZER,
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'typeCode' => ProcessConstants::CHEMISTRY_STABILIZER,
                'typeLabel' => 'Stabilizer',
                'translations' => ['fr' => ['typeLabel' => 'Stabilisateur']],
            ],
        ];
    }
}

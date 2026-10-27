<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\FocalLength;
use FilmAnalogger\FilmAnaloggerApi\Document\EnlargerLens;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class EnlargerLensFixtures extends Fixture implements DependentFixtureInterface
{
    public const EL_NIKKOR_50 = 'enlarger-lens-nikon-el-nikkor-50';
    public const RODAGON_50 = 'enlarger-lens-rodenstock-rodagon-50';
    public const RODAGON_80 = 'enlarger-lens-rodenstock-rodagon-80';
    public const COMPONON_S_50 = 'enlarger-lens-schneider-componon-s-50';
    public const COMPONON_S_80 = 'enlarger-lens-schneider-componon-s-80';
    public const MEOGON_50 = 'enlarger-lens-meopta-meogon-50';

    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $enlargerLens = new EnlargerLens();
            $enlargerLens
                ->setName($data['name'])
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setFocalLength($data['focalLength'])
                ->setMaxAperture($data['maxAperture'])
                ->setDescription($data['description'] ?? null)
                ->setStatus(CatalogStatus::OFFICIAL);

            $manager->persist($enlargerLens);
            $this->addReference($data['reference'], $enlargerLens);
            $entities[] = [$enlargerLens, $data];
        }

        $manager->flush();

        foreach ($entities as [$enlargerLens, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $enlargerLens->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $enlargerLens->setDescription($translations['description']);
                }
                $manager->persist($enlargerLens);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::EL_NIKKOR_50,
                'name' => 'EL-Nikkor 50mm',
                'description' => 'Standard enlarging lens for 35mm negatives.',
                'manufacturer' => ManufacturerFixtures::NIKON,
                'focalLength' => FocalLength::F50,
                'maxAperture' => 2.8,
                'translations' => [
                    'fr' => [
                        'description' => 'Objectif d\'agrandisseur standard pour négatifs 24x36.',
                    ],
                ],
            ],
            [
                'reference' => self::RODAGON_50,
                'name' => 'Rodagon 50mm',
                'description' => 'High-resolution enlarging lens for 35mm negatives.',
                'manufacturer' => ManufacturerFixtures::RODENSTOCK,
                'focalLength' => FocalLength::F50,
                'maxAperture' => 2.8,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Objectif d\'agrandisseur haute résolution pour négatifs 24x36.',
                    ],
                ],
            ],
            [
                'reference' => self::RODAGON_80,
                'name' => 'Rodagon 80mm',
                'description' => 'High-resolution enlarging lens for medium-format negatives.',
                'manufacturer' => ManufacturerFixtures::RODENSTOCK,
                'focalLength' => FocalLength::F80,
                'maxAperture' => 4.0,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Objectif d\'agrandisseur haute résolution pour négatifs moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::COMPONON_S_50,
                'name' => 'Componon-S 50mm',
                'description' => 'Enlarging lens for 35mm negatives.',
                'manufacturer' => ManufacturerFixtures::SCHNEIDER_KREUZNACH,
                'focalLength' => FocalLength::F50,
                'maxAperture' => 2.8,
                'translations' => [
                    'fr' => [
                        'description' => 'Objectif d\'agrandisseur pour négatifs 24x36.',
                    ],
                ],
            ],
            [
                'reference' => self::COMPONON_S_80,
                'name' => 'Componon-S 80mm',
                'description' => 'Enlarging lens for medium-format negatives.',
                'manufacturer' => ManufacturerFixtures::SCHNEIDER_KREUZNACH,
                'focalLength' => FocalLength::F80,
                'maxAperture' => 4.0,
                'translations' => [
                    'fr' => [
                        'description' => 'Objectif d\'agrandisseur pour négatifs moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::MEOGON_50,
                'name' => 'Meogon 50mm',
                'description' => 'Entry-level enlarging lens for 35mm negatives.',
                'manufacturer' => ManufacturerFixtures::MEOPTA,
                'focalLength' => FocalLength::F50,
                'maxAperture' => 2.8,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Objectif d\'agrandisseur d\'entrée de gamme pour négatifs 24x36.',
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [ManufacturerFixtures::class];
    }
}

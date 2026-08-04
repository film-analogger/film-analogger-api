<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\EnlargerLightSource;
use FilmAnalogger\FilmAnaloggerApi\Document\Enlarger;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class EnlargerFixtures extends Fixture implements DependentFixtureInterface
{
    public const M805 = 'enlarger-durst-m805';
    public const M605 = 'enlarger-durst-m605';
    public const ANARET = 'enlarger-meopta-anaret';

    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $enlarger = new Enlarger();
            $enlarger
                ->setName($data['name'])
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setLightSource($data['lightSource'])
                ->setDescription($data['description'] ?? null)
                ->setStatus(CatalogStatus::OFFICIAL);

            $manager->persist($enlarger);
            $this->addReference($data['reference'], $enlarger);
            $entities[] = [$enlarger, $data];
        }

        $manager->flush();

        foreach ($entities as [$enlarger, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $enlarger->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $enlarger->setDescription($translations['description']);
                }
                $manager->persist($enlarger);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::M805,
                'name' => 'M805',
                'description' => 'Modular condenser enlarger for medium-format darkroom printing.',
                'manufacturer' => ManufacturerFixtures::DURST,
                'lightSource' => EnlargerLightSource::CONDENSER,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Agrandisseur à condenseur modulaire pour le tirage moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::M605,
                'name' => 'M605',
                'description' => 'Compact condenser enlarger for 35mm and medium-format printing.',
                'manufacturer' => ManufacturerFixtures::DURST,
                'lightSource' => EnlargerLightSource::CONDENSER,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Agrandisseur à condenseur compact pour le tirage 24x36 et moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::ANARET,
                'name' => 'Anaret',
                'description' => 'Entry-level condenser enlarger for 35mm printing.',
                'manufacturer' => ManufacturerFixtures::MEOPTA,
                'lightSource' => EnlargerLightSource::CONDENSER,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Agrandisseur à condenseur d\'entrée de gamme pour le 24x36.',
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

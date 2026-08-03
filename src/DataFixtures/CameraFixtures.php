<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Camera;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class CameraFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $camera = new Camera();
            $camera
                ->setName($data['name'])
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setFilmFormat($data['filmFormat'])
                ->setDescription($data['description'] ?? null)
                ->setStatus(CatalogStatus::OFFICIAL);

            $manager->persist($camera);
            $entities[] = [$camera, $data];
        }

        $manager->flush();

        foreach ($entities as [$camera, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $camera->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $camera->setDescription($translations['description']);
                }
                $manager->persist($camera);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'name' => 'F100',
                'description' =>
                    'Professional 35mm SLR with fast, accurate autofocus and rugged build.',
                'manufacturer' => ManufacturerFixtures::NIKON,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_135,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex 35mm professionnel avec un autofocus rapide et précis et une construction robuste.',
                    ],
                ],
            ],
            [
                'name' => 'AE-1',
                'description' =>
                    'Iconic 35mm SLR that popularised electronically-controlled cameras for enthusiasts.',
                'manufacturer' => ManufacturerFixtures::CANON,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_135,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex 35mm emblématique qui a popularisé les appareils à commande électronique pour les passionnés.',
                    ],
                ],
            ],
            [
                'name' => 'MX',
                'description' =>
                    'Compact and lightweight mechanical 35mm SLR favoured for its reliability.',
                'manufacturer' => ManufacturerFixtures::PENTAX,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_135,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex 35mm mécanique compact et léger, apprécié pour sa fiabilité.',
                    ],
                ],
            ],
            [
                'name' => 'M6',
                'description' =>
                    'Manual rangefinder with a built-in light meter, a benchmark for street photography.',
                'manufacturer' => ManufacturerFixtures::LEICA,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_135,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Télémètre manuel avec posemètre intégré, une référence pour la photographie de rue.',
                    ],
                ],
            ],
            [
                'name' => '500 C/M',
                'description' =>
                    'Modular medium-format SLR with interchangeable backs, lenses and viewfinders.',
                'manufacturer' => ManufacturerFixtures::HASSELBLAD,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_120,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex moyen format modulaire à dos, objectifs et viseurs interchangeables.',
                    ],
                ],
            ],
            [
                'name' => 'RB67',
                'description' =>
                    'Studio medium-format SLR with a revolving back for both portrait and landscape framing.',
                'manufacturer' => ManufacturerFixtures::MAMIYA,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_120,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex moyen format de studio avec dos rotatif pour les cadrages portrait et paysage.',
                    ],
                ],
            ],
            [
                'name' => 'Rolleiflex 2.8F',
                'description' =>
                    'Classic twin-lens reflex producing square medium-format negatives.',
                'manufacturer' => ManufacturerFixtures::ROLLEI,
                'filmFormat' => ProcessConstants::CAMERA_FILM_FORMAT_120,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Reflex bi-objectif classique produisant des négatifs carrés en moyen format.',
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

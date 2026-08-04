<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBase;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperSurface;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use FilmAnalogger\FilmAnaloggerApi\Document\PhotoPaper;

class PhotoPaperFixtures extends Fixture implements DependentFixtureInterface
{
    public const ILFORD_MULTIGRADE_RC_PEARL = 'photo-paper-ilford-multigrade-rc-pearl';
    public const ILFORD_MULTIGRADE_FB_GLOSSY = 'photo-paper-ilford-multigrade-fb-glossy';
    public const FOMA_FOMABROM_RC_MATT = 'photo-paper-foma-fomabrom-rc-matt';

    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $photoPaper = new PhotoPaper();
            $photoPaper
                ->setName($data['name'])
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setPaperBase($data['paperBase'])
                ->setPaperSurface($data['paperSurface'])
                ->setVariableContrast($data['variableContrast'] ?? null)
                ->setDescription($data['description'] ?? null)
                ->setStatus(CatalogStatus::OFFICIAL);

            $manager->persist($photoPaper);
            $this->addReference($data['reference'], $photoPaper);
            $entities[] = [$photoPaper, $data];
        }

        $manager->flush();

        foreach ($entities as [$photoPaper, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $photoPaper->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $photoPaper->setDescription($translations['description']);
                }
                $manager->persist($photoPaper);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::ILFORD_MULTIGRADE_RC_PEARL,
                'name' => 'Multigrade RC Pearl',
                'description' => 'Variable-contrast resin-coated paper with a pearl surface.',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'paperBase' => PaperBase::RC,
                'paperSurface' => PaperSurface::PEARL,
                'variableContrast' => true,
                'translations' => [
                    'fr' => [
                        'description' => 'Papier RC à contraste variable avec une surface perlée.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD_MULTIGRADE_FB_GLOSSY,
                'name' => 'Multigrade FB Glossy',
                'description' => 'Variable-contrast fibre-based paper with a glossy surface.',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'paperBase' => PaperBase::FB,
                'paperSurface' => PaperSurface::GLOSSY,
                'variableContrast' => true,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Papier baryté à contraste variable avec une surface brillante.',
                    ],
                ],
            ],
            [
                'reference' => self::FOMA_FOMABROM_RC_MATT,
                'name' => 'Fomabrom RC Matt',
                'description' => 'Fixed-grade resin-coated paper with a matt surface.',
                'manufacturer' => ManufacturerFixtures::FOMA,
                'paperBase' => PaperBase::RC,
                'paperSurface' => PaperSurface::MATT,
                'variableContrast' => false,
                'translations' => [
                    'fr' => [
                        'description' => 'Papier RC à grade fixe avec une surface mate.',
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

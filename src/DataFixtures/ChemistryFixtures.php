<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;
use FilmAnalogger\FilmAnaloggerApi\Document\Dilution;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class ChemistryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $chemistry = new Chemistry();
            $chemistry->process = $data['process'];
            $chemistry
                ->setName($data['name'])
                ->setDescription($data['description'] ?? null)
                ->setChemistryType(
                    $this->getReference($data['chemistryType'], ChemistryType::class),
                )
                ->setOfficialDocumentationUrl($data['officialDocumentationUrl'] ?? null)
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class));

            foreach ($data['dilutions'] ?? [] as [$chemParts, $waterParts, $official]) {
                $chemistry->addDilution(
                    new Dilution()
                        ->setChemistryParts($chemParts)
                        ->setWaterParts($waterParts)
                        ->setOfficial($official),
                );
            }

            $manager->persist($chemistry);
            $entities[] = [$chemistry, $data];
        }

        $manager->flush();

        foreach ($entities as [$chemistry, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $chemistry->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $chemistry->setDescription($translations['description']);
                }
                $manager->persist($chemistry);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            // ── Kodak B&W Film Developers ─────────────────────────────────────
            [
                'name' => 'D-76',
                'description' =>
                    'Classic fine-grain B&W film developer delivering balanced sharpness and grain for most panchromatic films.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'dilutions' => [[1, 0, true], [1, 1, true]],
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/j78.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur N&B classique à grain fin offrant un équilibre entre netteté et grain pour la plupart des films panchromatiques.',
                    ],
                ],
            ],
            [
                'name' => 'HC-110',
                'description' =>
                    'Highly concentrated liquid B&W developer with a wide range of dilutions for push and normal processing.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'dilutions' => [[1, 15, true], [1, 31, true]],
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/wysiwyg/pro/chemistry/j24.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur liquide N&B hautement concentré avec une large gamme de dilutions pour le traitement normal et poussé.',
                    ],
                ],
            ],
            [
                'name' => 'XTOL',
                'description' =>
                    'Solvent developer with fine grain, excellent shadow detail, and environmentally friendly formula.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'dilutions' => [[1, 0, true], [1, 1, true], [1, 2, false], [1, 3, false]],
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/wysiwyg/pro/chemistry/J-109_Feb_2018.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur solvant à grain fin, excellent rendu des ombres et formule respectueuse de l\'environnement.',
                    ],
                ],
            ],

            // ── Kodak B&W Fixers ──────────────────────────────────────────────
            [
                'name' => 'T-MAX Fixer',
                'description' =>
                    'T-MAX fixer is a hardening all-purpose fixer bath for black and white film and paper.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FIXER,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'dilutions' => [[1, 5, true]],
                'officialDocumentationUrl' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Le fixateur T-MAX est un bain fixateur durcissant tout usage pour les films et papiers noir et blanc.',
                    ],
                ],
            ],

            // ── Kodak Wetting Agents ───────────────────────────────────────────
            [
                'name' => 'Photo-Flo 200',
                'description' =>
                    'Wetting agent that prevents water spots and drying marks on film after washing.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_WETTING_AGENT,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'dilutions' => [[1, 200, true]],
                'officialDocumentationUrl' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Agent mouillant qui prévient les traces d\'eau et les marques de séchage sur le film après lavage.',
                    ],
                ],
            ],

            // ── Ilford B&W Film Developers ────────────────────────────────────
            [
                'name' => 'ID-11',
                'description' =>
                    'Classic fine-grain powder developer equivalent to Kodak D-76, compatible with all Ilford films.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 0, true], [1, 1, true], [1, 3, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/wp/wp-content/uploads/2024/09/ILFORD-POWDER-CHEM-190824.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur en poudre classique à grain fin, équivalent au Kodak D-76, compatible avec tous les films Ilford.',
                    ],
                ],
            ],
            [
                'name' => 'Ilfosol 3',
                'description' =>
                    'Liquid film developer offering fine grain with good sharpness, ideal for occasional use.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 9, true], [1, 14, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/wp/wp-content/uploads/2024/09/Ilfosol3-Sept2024.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur liquide offrant un grain fin avec une bonne netteté, idéal pour une utilisation occasionnelle.',
                    ],
                ],
            ],
            [
                'name' => 'Microphen',
                'description' =>
                    'Speed-enhancing powder developer that extends film speed by up to one stop with minimal grain increase.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 0, true], [1, 1, true], [1, 3, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/wp/wp-content/uploads/2024/09/ILFORD-POWDER-CHEM-190824.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur en poudre amplificateur de vitesse qui augmente la sensibilité d\'un diaphragme avec une augmentation minimale du grain.',
                    ],
                ],
            ],

            // ── Ilford B&W Fixers ─────────────────────────────────────────────
            [
                'name' => 'Rapid Fixer',
                'description' =>
                    'Fast-acting liquid fixer for both film and paper, compatible with all B&W materials.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FIXER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 4, true], [1, 9, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1833/product/711/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fixateur liquide à action rapide pour films et papiers, compatible avec tous les matériaux N&B.',
                    ],
                ],
            ],

            // ── Ilford Stop Baths ─────────────────────────────────────────────
            [
                'name' => 'Ilfostop',
                'description' =>
                    'Liquid indicator stop bath that changes colour when exhausted, for film and paper.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_STOP,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 19, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1865/product/669/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain d\'arrêt liquide indicateur qui change de couleur lorsqu\'il est épuisé, pour films et papiers.',
                    ],
                ],
            ],

            // ── Ilford Wetting Agents ─────────────────────────────────────────
            [
                'name' => 'Ilfotol',
                'description' =>
                    'Liquid wetting agent preventing drying marks and water spots on processed film.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_WETTING_AGENT,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'dilutions' => [[1, 200, true]],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1865/product/673/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Agent mouillant liquide prévenant les traces de séchage et les traces d\'eau sur les films traités.',
                    ],
                ],
            ],

            // ── Adox B&W Film Developers ──────────────────────────────────────
            [
                'name' => 'Rodinal',
                'description' =>
                    'One-shot liquid developer known for its acutance and long shelf life, producing distinctive grain.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'chemistryType' => ChemistryTypeFixtures::BW_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ADOX,
                'dilutions' => [[1, 25, true], [1, 50, true], [1, 100, false]],
                'officialDocumentationUrl' =>
                    'https://www.fotoimpex.com/shop/images/products/media/56415_4_PDF-Datenblatt.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur monobain connu pour son acutance et sa longue durée de conservation, produisant un grain caractéristique.',
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [ChemistryTypeFixtures::class, ManufacturerFixtures::class];
    }
}

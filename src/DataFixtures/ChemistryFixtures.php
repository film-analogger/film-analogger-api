<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;
use FilmAnalogger\FilmAnaloggerApi\Document\Dilution;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class ChemistryFixtures extends Fixture implements DependentFixtureInterface
{
    public const ILFORD_MULTIGRADE_DEVELOPER = 'chemistry-ilford-multigrade-developer';
    public const ILFORD_ILFOSTOP = 'chemistry-ilford-ilfostop';
    public const ILFORD_RAPID_FIXER = 'chemistry-ilford-rapid-fixer';
    public const ILFORD_SELENIUM_TONER = 'chemistry-ilford-selenium-toner';
    public const ILFORD_BROMOPHEN = 'chemistry-ilford-bromophen';
    public const HOMEMADE_ACETIC_ACID_STOP = 'chemistry-homemade-acetic-acid-stop';

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
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setReferenceTemperatureMin($data['referenceTemperatureMin'] ?? null)
                ->setReferenceTemperatureMax($data['referenceTemperatureMax'] ?? null)
                ->setOpenShelfLifeDays($data['openShelfLifeDays'] ?? null)
                ->setStatus(CatalogStatus::OFFICIAL);

            foreach ($data['dilutions'] ?? [] as $dilutionData) {
                $chemistry->addDilution(
                    new Dilution()
                        ->setChemistryParts($dilutionData['chemistryParts'])
                        ->setWaterParts($dilutionData['waterParts'])
                        ->setOfficial($dilutionData['official'])
                        ->setDescription($dilutionData['description'] ?? null)
                        ->setReusable($dilutionData['reusable'] ?? false)
                        ->setTopUpRequired($dilutionData['topUpRequired'] ?? false)
                        ->setNotes($dilutionData['notes'] ?? null)
                        ->setCapacity($dilutionData['capacity'] ?? null)
                        ->setDilutedShelfLifeDays($dilutionData['dilutedShelfLifeDays'] ?? null),
                );
            }

            $manager->persist($chemistry);
            if (isset($data['reference'])) {
                $this->addReference($data['reference'], $chemistry);
            }
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock solution',
                        'reusable' => true,
                        'notes' =>
                            'Reusable for about 8-10 films per litre; add 10% to the development time for each additional film.',
                        'capacity' => '8-10 films/litre',
                        'dilutedShelfLifeDays' => 180,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'description' => '1+1, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'capacity' => '1 film per 300ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 15,
                        'official' => true,
                        'description' => 'Dilution B (normal)',
                        'notes' => 'Mixed fresh from the syrup concentrate for each session; one-shot.',
                        'capacity' => '1 film per 300ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 31,
                        'official' => true,
                        'description' => 'Dilution H (extended/economy)',
                        'notes' => 'Mixed fresh from the syrup concentrate for each session; one-shot.',
                        'capacity' => '1 film per 500ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock solution',
                        'reusable' => true,
                        'topUpRequired' => true,
                        'notes' => 'Can be replenished with XT-3 replenisher for higher-volume, ongoing use.',
                        'capacity' => '~8 films/litre one-shot, more with replenishment',
                        'dilutedShelfLifeDays' => 180,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'description' => '1+1, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'capacity' => '1 film per 500ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 2,
                        'official' => false,
                        'description' => '1+2, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 3,
                        'official' => false,
                        'description' => '1+3, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 5,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable until exhausted; check with a fixer-check solution.',
                        'capacity' => '~24 films/litre',
                        'dilutedShelfLifeDays' => 60,
                    ],
                ],
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
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 200,
                        'official' => true,
                        'notes' => 'Mix fresh for each session to avoid contamination and streaking.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock solution',
                        'reusable' => true,
                        'notes' =>
                            'Reusable for about 8-10 films per litre; add 10% to the development time for each additional film.',
                        'capacity' => '8-10 films/litre',
                        'dilutedShelfLifeDays' => 180,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'description' => '1+1, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'capacity' => '1 film per 500ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 3,
                        'official' => true,
                        'description' => '1+3, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'description' => '1+9, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'capacity' => '1 film per 300ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 14,
                        'official' => true,
                        'description' => '1+14, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock solution',
                        'reusable' => true,
                        'notes' =>
                            'Reusable for about 8-10 films per litre; add 10% to the development time for each additional film.',
                        'capacity' => '8-10 films/litre',
                        'dilutedShelfLifeDays' => 180,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'description' => '1+1, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 3,
                        'official' => true,
                        'description' => '1+3, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 4,
                        'official' => true,
                        'description' => '1+4 film',
                        'reusable' => true,
                        'notes' => 'Reusable until exhausted; check with a fixer-check solution.',
                        'capacity' => '~24 films/litre',
                        'dilutedShelfLifeDays' => 60,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'description' => '1+9 paper',
                        'reusable' => true,
                        'notes' => 'Reusable until exhausted; check with a fixer-check solution.',
                        'capacity' => '~1.5m² of paper per litre',
                        'dilutedShelfLifeDays' => 60,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 19,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable until the indicator changes colour.',
                        'capacity' => '~20 films per litre',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
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
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 200,
                        'official' => true,
                        'notes' => 'Mix fresh for each session to avoid contamination and streaking.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
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
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 1095,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 25,
                        'official' => true,
                        'description' => '1+25, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'capacity' => '1 film per 300ml',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 50,
                        'official' => true,
                        'description' => '1+50, one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 100,
                        'official' => false,
                        'description' => '1+100 (stand development), one-shot',
                        'notes' => 'One-shot dilution; discard after use.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://www.fotoimpex.com/shop/images/products/media/56415_4_PDF-Datenblatt.pdf',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur monobain connu pour son acutance et sa longue durée de conservation, produisant un grain caractéristique.',
                    ],
                ],
            ],

            // ── Tetenal C-41 (Colortec) ─────────────────────────────────────────
            [
                'name' => 'Colortec C-41 Developer',
                'description' =>
                    'Colour developer bath from the Colortec C-41 kit, for processing all C-41-compatible colour negative films.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'chemistryType' => ChemistryTypeFixtures::C41_COLOR_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 29.5,
                'referenceTemperatureMax' => 30.5,
                'openShelfLifeDays' => 42,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 4,
                        'official' => true,
                        'notes' => 'Mixed to working strength for the session; one-shot for consistent colour.',
                        'capacity' => '1 film per working-solution batch',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102221/colortec-c-41-kit-for-1l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain révélateur couleur du kit Colortec C-41, pour le traitement de tous les films négatifs couleur compatibles C-41.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec C-41 Bleach Fix',
                'description' =>
                    'Combined bleach-fix bath from the Colortec C-41 kit, clearing silver and fixing the image in a single step.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'chemistryType' => ChemistryTypeFixtures::C41_BLEACH,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 40.0,
                'openShelfLifeDays' => 90,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 4,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Less temperature-critical than the developer; can be reused across several sessions.',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102221/colortec-c-41-kit-for-1l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain blanchisseur-fixateur combiné du kit Colortec C-41, qui élimine l\'argent et fixe l\'image en une seule étape.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec C-41 Stabilizer',
                'description' =>
                    'Final stabilizer bath from the Colortec C-41 kit, protecting the processed film during drying.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'chemistryType' => ChemistryTypeFixtures::C41_STABILIZER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 30.0,
                'openShelfLifeDays' => 90,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 200,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Final rinse; can be reused across several sessions.',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102221/colortec-c-41-kit-for-1l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain stabilisateur final du kit Colortec C-41, qui protège le film traité pendant le séchage.',
                    ],
                ],
            ],

            // ── Tetenal E-6 (Colortec) ──────────────────────────────────────────
            [
                'name' => 'Colortec E-6 First Developer',
                'description' =>
                    'First developer bath from the Colortec E-6 3-bath kit, for the initial black-and-white development of slide film.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'chemistryType' => ChemistryTypeFixtures::E6_FILM_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 37.5,
                'referenceTemperatureMax' => 38.5,
                'openShelfLifeDays' => 42,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock, one-shot',
                        'notes' => 'Mixed to working strength for the session; one-shot for consistent colour.',
                        'capacity' => '1 film per working-solution batch',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102035/colortec-e-6-3-bath-kit-for-1-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain premier révélateur du kit Colortec E-6 3 bains, pour le développement noir et blanc initial des films diapositives.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec E-6 Color Developer',
                'description' =>
                    'Colour developer bath from the Colortec E-6 3-bath kit, forming the dye image after the first developer.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'chemistryType' => ChemistryTypeFixtures::E6_COLOR_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 37.5,
                'referenceTemperatureMax' => 38.5,
                'openShelfLifeDays' => 42,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock, one-shot',
                        'notes' => 'Mixed to working strength for the session; one-shot for consistent colour.',
                        'capacity' => '1 film per working-solution batch',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102035/colortec-e-6-3-bath-kit-for-1-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain révélateur couleur du kit Colortec E-6 3 bains, qui forme l\'image colorée après le premier révélateur.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec E-6 Bleach Fix',
                'description' =>
                    'Combined bleach-fix bath from the Colortec E-6 3-bath kit, clearing silver and fixing the reversed image.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'chemistryType' => ChemistryTypeFixtures::E6_BLEACH,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 40.0,
                'openShelfLifeDays' => 90,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 0,
                        'official' => true,
                        'description' => 'Stock',
                        'reusable' => true,
                        'notes' => 'Less temperature-critical than the developer; can be reused across several sessions.',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102035/colortec-e-6-3-bath-kit-for-1-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain blanchisseur-fixateur combiné du kit Colortec E-6 3 bains, qui élimine l\'argent et fixe l\'image inversée.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec E-6 Stabilizer',
                'description' =>
                    'Final stabilizer bath from the Colortec E-6 3-bath kit, protecting the processed slide film during drying.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'chemistryType' => ChemistryTypeFixtures::E6_STABILIZER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 30.0,
                'openShelfLifeDays' => 90,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 200,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Final rinse; can be reused across several sessions.',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102035/colortec-e-6-3-bath-kit-for-1-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain stabilisateur final du kit Colortec E-6 3 bains, qui protège le film diapositive traité pendant le séchage.',
                    ],
                ],
            ],

            // ── Tetenal RA-4 (Colortec) ─────────────────────────────────────────
            [
                'name' => 'Colortec RA-4 Developer',
                'description' =>
                    'Colour developer bath from the Colortec RA-4 kit, for processing RA-4 colour photographic paper.',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'chemistryType' => ChemistryTypeFixtures::RA4_COLOR_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 33.0,
                'referenceTemperatureMax' => 35.0,
                'openShelfLifeDays' => 42,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'notes' => 'Mixed to working strength for the session; one-shot for consistent colour.',
                        'capacity' => '~1.5m² of paper per working-solution batch',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102124/colortec-ra-4-kit-for-5-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain révélateur couleur du kit Colortec RA-4, pour le traitement du papier photographique couleur RA-4.',
                    ],
                ],
            ],
            [
                'name' => 'Colortec RA-4 Bleach Fix',
                'description' =>
                    'Combined bleach-fix bath from the Colortec RA-4 kit, clearing silver and fixing the print in a single step.',
                'process' => ProcessConstants::CHEMISTRY_RA4,
                'chemistryType' => ChemistryTypeFixtures::RA4_BLEACH,
                'manufacturer' => ManufacturerFixtures::TETENAL,
                'referenceTemperatureMin' => 20.0,
                'referenceTemperatureMax' => 35.0,
                'openShelfLifeDays' => 90,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Less temperature-critical than the developer; can be reused across several sessions.',
                        'capacity' => '~1.5m² of paper per litre',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://tetenal.com/en/homepage/consumer-shop/color-chemistry/colortec/102124/colortec-ra-4-kit-for-5-l',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain blanchisseur-fixateur combiné du kit Colortec RA-4, qui élimine l\'argent et fixe le tirage en une seule étape.',
                    ],
                ],
            ],

            // ── Ilford B&W Print (darkroom printing) ────────────────────────
            [
                'reference' => self::ILFORD_MULTIGRADE_DEVELOPER,
                'name' => 'Multigrade Developer',
                'description' =>
                    'General-purpose paper developer for Ilford Multigrade variable-contrast papers, warm-neutral black tones.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_PAPER_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable for the duration of a printing session; discard afterwards.',
                        'capacity' => '~1.5m² of paper per litre (~24 sheets of 18x24cm)',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 14,
                        'official' => false,
                        'reusable' => true,
                        'notes' => 'Economy dilution; reusable for the duration of a printing session.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1/product/670/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur papier polyvalent pour les papiers à contraste variable Ilford Multigrade, tons noirs chauds-neutres.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD_ILFOSTOP,
                'name' => 'Ilfostop',
                'description' =>
                    'Odourless indicator stop bath for film and paper, changes colour from yellow to purple when exhausted.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_STOP,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 19,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable until the indicator changes colour.',
                        'capacity' => '~1.5m² of paper per litre',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/3/product/671/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain d\'arrêt indicateur inodore pour film et papier, change de couleur du jaune au violet une fois épuisé.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD_RAPID_FIXER,
                'name' => 'Rapid Fixer',
                'description' =>
                    'Fast-acting film and paper fixer; more dilute for archival paper fixing than for film.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_FIXER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'description' => '1+9 paper',
                        'reusable' => true,
                        'notes' => 'Reusable until exhausted; check with a fixer-check solution.',
                        'capacity' => '~1.5m² of paper per litre',
                        'dilutedShelfLifeDays' => 60,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 4,
                        'official' => false,
                        'description' => '1+4 film',
                        'reusable' => true,
                        'notes' => 'Reusable until exhausted; check with a fixer-check solution.',
                        'capacity' => '~24 films/litre',
                        'dilutedShelfLifeDays' => 60,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/5/product/672/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fixateur rapide film et papier ; plus dilué pour un fixage archive du papier que pour le film.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD_SELENIUM_TONER,
                'name' => 'Selenium Toner',
                'description' =>
                    'Selenium-based toner for archival permanence and subtle tone shift, from mild split-tone to deep purple-brown.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_TONER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'description' => 'Mild split-tone',
                        'reusable' => true,
                        'notes' => 'Reusable across multiple toning sessions; store in a sealed bottle between uses.',
                        'capacity' => '~20-30 prints (18x24cm) per litre',
                        'dilutedShelfLifeDays' => 180,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 4,
                        'official' => false,
                        'description' => 'Deep purple-brown',
                        'reusable' => true,
                        'notes' => 'Reusable across multiple toning sessions; store in a sealed bottle between uses.',
                        'capacity' => '~20-30 prints (18x24cm) per litre',
                        'dilutedShelfLifeDays' => 180,
                    ],
                ],
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/7/product/673/',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Virage au sélénium pour la pérennité de conservation et une nuance de ton subtile, du léger virage partiel au brun-violet profond.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD_BROMOPHEN,
                'name' => 'Bromophen',
                'description' =>
                    'Warm-tone paper developer for bromide and chlorobromide papers, popular for its rich, neutral-to-warm blacks.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_PAPER_DEVELOPER,
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 180,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 3,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable for the duration of a printing session; discard afterwards.',
                        'capacity' => '~1.5m² of paper per litre (~24 sheets of 18x24cm)',
                        'dilutedShelfLifeDays' => 1,
                    ],
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => false,
                        'reusable' => true,
                        'notes' => 'Economy dilution; reusable for the duration of a printing session.',
                        'dilutedShelfLifeDays' => 1,
                    ],
                ],
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Révélateur papier à tons chauds pour papiers bromure et chlorobromure, apprécié pour ses noirs riches, neutres à chauds.',
                    ],
                ],
            ],
            [
                'reference' => self::HOMEMADE_ACETIC_ACID_STOP,
                'name' => 'Acide acétique 20%',
                'description' =>
                    'Homemade stop bath: household vinegar or diluted acetic acid, used interchangeably by name across sessions.',
                'process' => ProcessConstants::CHEMISTRY_BW_PRINT,
                'chemistryType' => ChemistryTypeFixtures::BW_PRINT_STOP,
                'manufacturer' => ManufacturerFixtures::GENERIC,
                'referenceTemperatureMin' => 18.0,
                'referenceTemperatureMax' => 24.0,
                'openShelfLifeDays' => 365,
                'dilutions' => [
                    [
                        'chemistryParts' => 1,
                        'waterParts' => 9,
                        'official' => true,
                        'reusable' => true,
                        'notes' => 'Reusable until it smells weak or a session\'s worth of prints has passed through it.',
                        'capacity' => '~1.5m² of paper per litre',
                        'dilutedShelfLifeDays' => 90,
                    ],
                ],
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Bain d\'arrêt maison : vinaigre ménager ou acide acétique dilué, noté indifféremment sous l\'un ou l\'autre nom selon les séances.',
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

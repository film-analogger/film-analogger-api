<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class ManufacturerFixtures extends Fixture
{
    public const KODAK = 'manufacturer-kodak';
    public const ILFORD = 'manufacturer-ilford';
    public const FUJIFILM = 'manufacturer-fujifilm';
    public const ADOX = 'manufacturer-adox';
    public const FOMA = 'manufacturer-foma';
    public const ROLLEI = 'manufacturer-rollei';
    public const NIKON = 'manufacturer-nikon';
    public const CANON = 'manufacturer-canon';
    public const PENTAX = 'manufacturer-pentax';
    public const LEICA = 'manufacturer-leica';
    public const HASSELBLAD = 'manufacturer-hasselblad';
    public const MAMIYA = 'manufacturer-mamiya';
    public const CINESTILL = 'manufacturer-cinestill';
    public const LOMOGRAPHY = 'manufacturer-lomography';
    public const BERGGER = 'manufacturer-bergger';
    public const TETENAL = 'manufacturer-tetenal';
    public const GENERIC = 'manufacturer-generic';
    public const DURST = 'manufacturer-durst';
    public const MEOPTA = 'manufacturer-meopta';
    public const RODENSTOCK = 'manufacturer-rodenstock';
    public const SCHNEIDER_KREUZNACH = 'manufacturer-schneider-kreuznach';

    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach ($this->getData() as $data) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($data['name']);
            $manufacturer->setDescription($data['description'] ?? null);
            $manufacturer->setWebsite($data['website']);
            $manufacturer->setPrimaryColor($data['primaryColor']);
            $manufacturer->setSecondaryColor($data['secondaryColor']);
            $manufacturer->setTertiaryColor($data['tertiaryColor']);
            $manufacturer->setStatus(CatalogStatus::OFFICIAL);

            $manager->persist($manufacturer);
            $this->addReference($data['reference'], $manufacturer);
            $entities[] = [$manufacturer, $data];
        }

        $manager->flush();

        foreach ($entities as [$manufacturer, $data]) {
            foreach ($data['translations'] ?? [] as $locale => $translations) {
                $manufacturer->setTranslatableLocale($locale);
                if (isset($translations['description'])) {
                    $manufacturer->setDescription($translations['description']);
                }
                $manager->persist($manufacturer);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::KODAK,
                'name' => 'Kodak',
                'description' =>
                    'American manufacturer with a long heritage in colour and black-and-white photographic film.',
                'website' => 'https://www.kodak.gtcie.com/en',
                'primaryColor' => '#FAB617',
                'secondaryColor' => '#ED0000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant américain à la longue tradition dans les films photographiques couleur et noir et blanc.',
                    ],
                ],
            ],
            [
                'reference' => self::ILFORD,
                'name' => 'Ilford',
                'description' =>
                    'British manufacturer specialising in black-and-white films, papers and chemistry.',
                'website' => 'https://www.ilfordphoto.com/',
                'primaryColor' => '#FFFFFF',
                'secondaryColor' => '#231F20',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant britannique spécialisé dans les films, papiers et produits chimiques noir et blanc.',
                    ],
                ],
            ],
            [
                'reference' => self::FUJIFILM,
                'name' => 'Fujifilm',
                'description' =>
                    'Japanese manufacturer of colour negative, instant and motion picture film.',
                'website' => 'https://www.fujifilm.com/fr/en',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => '#FB0020',
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant japonais de films couleur, instantanés et cinématographiques.',
                    ],
                ],
            ],
            [
                'reference' => self::ADOX,
                'name' => 'Adox',
                'description' =>
                    'German manufacturer of black-and-white films and darkroom chemistry.',
                'website' => 'https://www.adox.de/Photo/en/',
                'primaryColor' => '#FFFFFF',
                'secondaryColor' => '#E48157',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant allemand de films noir et blanc et de produits chimiques de laboratoire.',
                    ],
                ],
            ],
            [
                'reference' => self::FOMA,
                'name' => 'Foma',
                'description' =>
                    'Czech manufacturer of black-and-white films and photographic papers.',
                'website' => 'https://www.foma.cz/en',
                'primaryColor' => '#ffffff',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant tchèque de films et papiers photographiques noir et blanc.',
                    ],
                ],
            ],
            [
                'reference' => self::ROLLEI,
                'name' => 'Rollei',
                'description' =>
                    'German brand offering black-and-white and specialty analog films.',
                'website' => 'https://www.rollei.de/en',
                'primaryColor' => '#FEFEFE',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Marque allemande proposant des films argentiques noir et blanc et spéciaux.',
                    ],
                ],
            ],
            [
                'reference' => self::NIKON,
                'name' => 'Nikon',
                'description' => 'Japanese manufacturer of cameras and photographic lenses.',
                'website' => 'https://www.nikon.com/',
                'primaryColor' => '#FFE100',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' => 'Fabricant japonais d\'appareils photo et d\'objectifs.',
                    ],
                ],
            ],
            [
                'reference' => self::CANON,
                'name' => 'Canon',
                'description' => 'Japanese manufacturer of cameras and photographic lenses.',
                'website' => 'https://global.canon/',
                'primaryColor' => '#DB0011',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' => 'Fabricant japonais d\'appareils photo et d\'objectifs.',
                    ],
                ],
            ],
            [
                'reference' => self::PENTAX,
                'name' => 'Pentax',
                'description' => 'Japanese manufacturer known for its compact 35mm SLR cameras.',
                'website' => 'https://www.ricoh-imaging.com/',
                'primaryColor' => '#003DA5',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' => 'Fabricant japonais réputé pour ses reflex 35mm compacts.',
                    ],
                ],
            ],
            [
                'reference' => self::LEICA,
                'name' => 'Leica',
                'description' =>
                    'German manufacturer of premium cameras and lenses, pioneer of 35mm photography.',
                'website' => 'https://leica-camera.com/',
                'primaryColor' => '#E20613',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant allemand d\'appareils photo et d\'objectifs haut de gamme, pionnier du format 35mm.',
                    ],
                ],
            ],
            [
                'reference' => self::HASSELBLAD,
                'name' => 'Hasselblad',
                'description' => 'Swedish manufacturer of medium-format cameras.',
                'website' => 'https://www.hasselblad.com/',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' => 'Fabricant suédois d\'appareils photo moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::MAMIYA,
                'name' => 'Mamiya',
                'description' => 'Japanese manufacturer of medium-format cameras.',
                'website' => 'https://mamiya.com/',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' => 'Fabricant japonais d\'appareils photo moyen format.',
                    ],
                ],
            ],
            [
                'reference' => self::CINESTILL,
                'name' => 'CineStill',
                'description' =>
                    'American brand repackaging motion picture film stock for still photography.',
                'website' => 'https://cinestillfilm.com/',
                'primaryColor' => '#000000',
                'secondaryColor' => '#ED1C24',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Marque américaine reconditionnant des pellicules cinéma pour la photographie argentique.',
                    ],
                ],
            ],
            [
                'reference' => self::LOMOGRAPHY,
                'name' => 'Lomography',
                'description' =>
                    'Austrian brand promoting experimental analog photography, toy cameras and film.',
                'website' => 'https://www.lomography.com/',
                'primaryColor' => '#D71920',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Marque autrichienne promouvant la photographie argentique expérimentale, les toy cameras et les films.',
                    ],
                ],
            ],
            [
                'reference' => self::BERGGER,
                'name' => 'Bergger',
                'description' => 'French manufacturer of black-and-white films and papers.',
                'website' => 'https://bergger.com/en/',
                'primaryColor' => '#0B2E59',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant français de films et papiers photographiques noir et blanc.',
                    ],
                ],
            ],
            [
                'reference' => self::TETENAL,
                'name' => 'Tetenal',
                'description' => 'German manufacturer of photographic development chemistry.',
                'website' => 'https://tetenal.com/en/',
                'primaryColor' => '#E30613',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant allemand de produits chimiques de développement photographique.',
                    ],
                ],
            ],
            [
                'reference' => self::GENERIC,
                'name' => 'Générique / maison',
                // Catch-all for homemade/unbranded chemistry (e.g. a diluted
                // household vinegar stop bath) — not every bath used in a real
                // darkroom session comes from a commercial product line.
                'description' =>
                    'Catch-all entry for homemade or unbranded products with no specific manufacturer.',
                'website' => null,
                'primaryColor' => null,
                'secondaryColor' => null,
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Entrée générique pour les produits faits maison ou sans marque, sans fabricant particulier.',
                    ],
                ],
            ],
            [
                'reference' => self::DURST,
                'name' => 'Durst',
                'description' => 'Italian manufacturer of enlargers and darkroom equipment.',
                'website' => 'https://www.durst-group.com/',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant italien d\'agrandisseurs et de matériel de laboratoire.',
                    ],
                ],
            ],
            [
                'reference' => self::MEOPTA,
                'name' => 'Meopta',
                'description' => 'Czech manufacturer of enlargers and optical equipment.',
                'website' => 'https://www.meopta.com/',
                'primaryColor' => '#003057',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant tchèque d\'agrandisseurs et de matériel optique.',
                    ],
                ],
            ],
            [
                'reference' => self::RODENSTOCK,
                'name' => 'Rodenstock',
                'description' => 'German manufacturer of high-precision enlarging lenses.',
                'website' => 'https://www.rodenstock-photo.com/',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant allemand d\'objectifs d\'agrandisseur de haute précision.',
                    ],
                ],
            ],
            [
                'reference' => self::SCHNEIDER_KREUZNACH,
                'name' => 'Schneider Kreuznach',
                'description' => 'German manufacturer of optics, including enlarging lenses.',
                'website' => 'https://schneiderkreuznach.com/',
                'primaryColor' => '#003DA5',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
                'translations' => [
                    'fr' => [
                        'description' =>
                            'Fabricant allemand d\'optiques, dont des objectifs d\'agrandisseur.',
                    ],
                ],
            ],
        ];
    }
}

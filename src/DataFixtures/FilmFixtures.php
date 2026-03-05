<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class FilmFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $data) {
            $film = new Film();
            $film->process = $data['process'];
            $film->sensibility = $data['sensibility'];
            $film->emulsionType = $data['emulsionType'];
            $film->inversible = $data['inversible'] ?? null;
            $film
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class))
                ->setOfficialDocumentationUrl($data['officialDocumentationUrl'] ?? null)
                ->setPrimaryColor($data['primaryColor'] ?? null)
                ->setSecondaryColor($data['secondaryColor'] ?? null)
                ->setTertiaryColor($data['tertiaryColor'] ?? null);

            $manager->persist($film);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            // ── Kodak ────────────────────────────────────────────────────────
            [
                'name' => 'Tri-X 400',
                'description' =>
                    'Iconic high-speed panchromatic black-and-white film with characteristic grain and wide exposure latitude.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/f4017_TriX.pdf',
                'primaryColor' => '#0E7873',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'T-Max 100',
                'description' =>
                    'Fine-grain T-grain black-and-white film offering exceptional sharpness at low speeds.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 100,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/f4016_TMax_100.pdf',
                'primaryColor' => '#754583',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'T-Max 400',
                'description' =>
                    'Versatile T-grain film combining high speed with very fine grain for a B&W film.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/f4043_TMax_400.pdf',
                'primaryColor' => '#4CB599',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Gold 200',
                'description' =>
                    'For bright, colourful silver photos, Kodak Gold 135mm 36 exposure film delivers striking, vivid, natural colours. Its unique formulation captures light precisely, producing rich, vibrant tones that are perfect for capturing your memories. ( official description from https://www.kodak.gtcie.com/ )',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 200,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/E7022_Gold_200.pdf',
                'primaryColor' => '#A6198A',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'UltraMax 400',
                'description' =>
                    'Kodak UltraMax 400 ISO colour film is a must-have choice for photographers looking for a versatile, high-performance film. With its ISO sensitivity of 400, it offers great flexibility in a variety of light conditions, while maintaining exceptional image quality. Compatible with all 35mm film cameras, you\'ll be able to make 36 fine-grain exposures. ( official description from https://www.kodak.gtcie.com/ )',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://apps.kodakmoments.com/wp-content/uploads/2017/07/E7023_max_400.pdf',
                'primaryColor' => '#3F79AB',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Ektar 100',
                'description' =>
                    'World\'s finest grain colour negative film with vivid saturation, ideal for landscape photography.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 100,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/products/e4046_ektar_100.pdf',
                'primaryColor' => '#4B1311',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Portra 160',
                'description' =>
                    'Professional portrait film with natural skin tones, fine grain and wide exposure latitude.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 160,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/resources/e4051_Portra_160.pdf',
                'primaryColor' => '#715FD9',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Portra 400',
                'description' =>
                    'Professional high-speed portrait film with natural colours and excellent push processing capability.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://tables.pirate-photo.fr/documents/Kodak_doc_portra400.pdf',
                'primaryColor' => '#6245DF',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Ektachrome E100',
                'description' =>
                    'Professional E-6 slide film with fine grain, high sharpness and natural colour rendition.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'sensibility' => 100,
                'emulsionType' => 'chromogene',
                'inversible' => true,
                'manufacturer' => ManufacturerFixtures::KODAK,
                'officialDocumentationUrl' =>
                    'https://business.kodakmoments.com/sites/default/files/files/products/e4000_ektachrome_100.pdf',
                'primaryColor' => '#415FD0',
                'secondaryColor' => '#FAB617',
                'tertiaryColor' => null,
            ],

            // ── Ilford ───────────────────────────────────────────────────────
            [
                'name' => 'HP5 Plus',
                'description' =>
                    'Versatile high-speed black-and-white film with wide exposure latitude, excellent for push processing.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1903/product/695/',
                'primaryColor' => '#24AF2C',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'FP4 Plus',
                'description' =>
                    'Fine-grain medium-speed black-and-white film offering exceptional sharpness and tonal range.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 125,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1919/product/690/',
                'primaryColor' => '#0B3C9A',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Delta 100',
                'description' =>
                    'Core-shell technology black-and-white film delivering extremely fine grain at ISO 100.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 100,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/3/product/681/',
                'primaryColor' => '#0898D0',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Delta 400',
                'description' =>
                    'Core-shell technology high-speed black-and-white film with very fine grain for ISO 400.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1915/product/685/',
                'primaryColor' => '#30B256',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Delta 3200',
                'description' =>
                    'Core-shell technology ultra-high-speed black-and-white film with fine grain for ISO 3200, ideal for low-light and action photography.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 3200,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/wp/wp-content/uploads/2025/07/DP3200_F25.pdf',
                'primaryColor' => '#9B79D8',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Pan F Plus',
                'description' =>
                    'Ultra-fine grain slow-speed film for maximum detail in controlled lighting conditions.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 50,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1905/product/699/',
                'primaryColor' => '#E5AD2C',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'SFX 200',
                'description' =>
                    'Extended red sensitivity black-and-white film producing infrared-like effects.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 200,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.ilfordphoto.com/amfile/file/download/file/1907/product/702/',
                'primaryColor' => '#D720C8',
                'secondaryColor' => '#ffffff',
                'tertiaryColor' => null,
            ],

            // ── Fujifilm ─────────────────────────────────────────────────────
            [
                'name' => 'Fujicolor 200',
                'description' =>
                    'Consumer colour negative film with natural colour reproduction for everyday photography.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 200,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://asset.fujifilm.com/master/emea/files/2020-10/98c3d5087c253f51c132a5d46059f131/films_c200_datasheet_01.pdf',
                'primaryColor' => '#ffffff',
                'secondaryColor' => '#CA4979',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Fujicolor 400',
                'description' =>
                    'High-speed consumer colour negative film for indoor and low-light situations.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://asset.fujifilm.com/master/emea/files/2020-10/a6cb96275e4957ddc7b3ca932b7755e5/films_pro-400h_datasheet_01.pdf',
                'primaryColor' => '#ffffff',
                'secondaryColor' => '#0D76B9',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Velvia 50',
                'description' =>
                    'Professional slide film renowned for its vivid saturation and ultra-fine grain.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'sensibility' => 50,
                'emulsionType' => 'chromogene',
                'inversible' => true,
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
                'officialDocumentationUrl' =>
                    'https://www.ishootfujifilm.com/uploads/VELVIA%2050%20Data%20Guide.pdf',
                'primaryColor' => '#000000',
                'secondaryColor' => '#CFAD66',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Velvia 100',
                'description' =>
                    'Professional slide film with vibrant colours and fine grain for faster shooting.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'sensibility' => 100,
                'emulsionType' => 'chromogene',
                'inversible' => true,
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
                'officialDocumentationUrl' =>
                    'https://asset.fujifilm.com/master/emea/files/2020-10/2f3c7f90a0b0c6e605e84f98b7d489c2/films_velvia-100_datasheet_01.pdf',
                'primaryColor' => '#CFAD66',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Provia 100F',
                'description' =>
                    'Professional daylight slide film with natural colour balance and fine grain.',
                'process' => ProcessConstants::CHEMISTRY_E6,
                'sensibility' => 100,
                'emulsionType' => 'chromogene',
                'inversible' => true,
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
                'officialDocumentationUrl' =>
                    'https://asset.fujifilm.com/master/emea/files/2020-10/2c27854d5609945fbe7e48afc61f815d/films_provia-100f_datasheet_01.pdf',
                'primaryColor' => '#C2B775',
                'secondaryColor' => '#000000',
                'tertiaryColor' => null,
            ],

            // ── Adox ─────────────────────────────────────────────────────────
            [
                'name' => 'CMS 20 II',
                'description' =>
                    'Extremely high-resolution orthochromatic black-and-white film with ultra-fine grain.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 20,
                'emulsionType' => 'orthochromatic',
                'manufacturer' => ManufacturerFixtures::ADOX,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.adox.de/Technical_Informations/CMS20_ADOTECHII_instructions.pdf',
                'primaryColor' => '#FFFFFF',
                'secondaryColor' => '#E48157',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'HR-50',
                'description' =>
                    'High-resolution panchromatic black-and-white film combining fine grain with wide latitude.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 50,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ADOX,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.adox.de/Technical_Informations/TA_HR-50_EN.pdf',
                'primaryColor' => '#FFFFFF',
                'secondaryColor' => '#E48157',
                'tertiaryColor' => null,
            ],

            // ── Foma ─────────────────────────────────────────────────────────
            [
                'name' => 'Fomapan 100',
                'description' =>
                    'Classic panchromatic black-and-white film with fine grain and good tonal range.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 100,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::FOMA,
                'inversible' => false,
                'officialDocumentationUrl' => 'https://www.foma.cz/fr/fomapan-100',
                'primaryColor' => '#000000',
                'secondaryColor' => '#FDE403',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Fomapan 400',
                'description' =>
                    'High-speed panchromatic black-and-white film with wide exposure latitude.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::FOMA,
                'inversible' => false,
                'officialDocumentationUrl' => 'https://www.foma.cz/fr/fomapan-400',
                'primaryColor' => '#000000',
                'secondaryColor' => '#9CE53C',
                'tertiaryColor' => null,
            ],

            // ── Rollei ────────────────────────────────────────────────────────
            [
                'name' => 'Rollei RPX 400',
                'description' =>
                    'Versatile panchromatic black-and-white film with broad tonal gradation.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ROLLEI,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.rolleianalog.com/wp-content/uploads/2021/07/RPX400_Data-Sheet_EN_R210701.pdf',
                'primaryColor' => '#EE2E22',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
            ],
            [
                'name' => 'Rollei Infrared 400',
                'description' =>
                    'Panchromatic infrared-sensitive black-and-white film for dramatic sky rendition.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ROLLEI,
                'inversible' => false,
                'officialDocumentationUrl' =>
                    'https://www.rolleianalog.com/wp-content/uploads/2021/02/INFRARED_Datenblatt_EN_R012101.pdf',
                'primaryColor' => '##E60064',
                'secondaryColor' => '#FFFFFF',
                'tertiaryColor' => null,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [ManufacturerFixtures::class];
    }
}

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
                ->setManufacturer($this->getReference($data['manufacturer'], Manufacturer::class));

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
            ],
            [
                'name' => 'T-Max 100',
                'description' =>
                    'Fine-grain T-grain black-and-white film offering exceptional sharpness at low speeds.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 100,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'T-Max 400',
                'description' =>
                    'Versatile T-grain film combining high speed with very fine grain for a B&W film.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'Gold 200',
                'description' =>
                    'Everyday colour negative film with warm tones and moderate grain.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 200,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'UltraMax 400',
                'description' =>
                    'High-speed colour negative film designed for low-light and action photography.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'Ektar 100',
                'description' =>
                    'World\'s finest grain colour negative film with vivid saturation, ideal for landscape photography.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 100,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'Portra 160',
                'description' =>
                    'Professional portrait film with natural skin tones, fine grain and wide exposure latitude.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 160,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
            ],
            [
                'name' => 'Portra 400',
                'description' =>
                    'Professional high-speed portrait film with natural colours and excellent push processing capability.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::KODAK,
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
            ],
            [
                'name' => 'FP4 Plus',
                'description' =>
                    'Fine-grain medium-speed black-and-white film offering exceptional sharpness and tonal range.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 125,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
            ],
            [
                'name' => 'Delta 100',
                'description' =>
                    'Core-shell technology black-and-white film delivering extremely fine grain at ISO 100.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 100,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
            ],
            [
                'name' => 'Delta 400',
                'description' =>
                    'Core-shell technology high-speed black-and-white film with very fine grain for ISO 400.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
            ],
            [
                'name' => 'Pan F Plus',
                'description' =>
                    'Ultra-fine grain slow-speed film for maximum detail in controlled lighting conditions.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 50,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
            ],
            [
                'name' => 'SFX 200',
                'description' =>
                    'Extended red sensitivity black-and-white film producing infrared-like effects.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 200,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ILFORD,
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
            ],
            [
                'name' => 'Fujicolor 400',
                'description' =>
                    'High-speed consumer colour negative film for indoor and low-light situations.',
                'process' => ProcessConstants::CHEMISTRY_C41,
                'sensibility' => 400,
                'emulsionType' => 'chromogene',
                'manufacturer' => ManufacturerFixtures::FUJIFILM,
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
            ],
            [
                'name' => 'HR-50',
                'description' =>
                    'High-resolution panchromatic black-and-white film combining fine grain with wide latitude.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 50,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ADOX,
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
            ],
            [
                'name' => 'Fomapan 400',
                'description' =>
                    'High-speed panchromatic black-and-white film with wide exposure latitude.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::FOMA,
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
            ],
            [
                'name' => 'Rollei Infrared 400',
                'description' =>
                    'Panchromatic infrared-sensitive black-and-white film for dramatic sky rendition.',
                'process' => ProcessConstants::CHEMISTRY_BW,
                'sensibility' => 400,
                'emulsionType' => 'panchromatic',
                'manufacturer' => ManufacturerFixtures::ROLLEI,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [ManufacturerFixtures::class];
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;

class ManufacturerFixtures extends Fixture
{
    public const KODAK = 'manufacturer-kodak';
    public const ILFORD = 'manufacturer-ilford';
    public const FUJIFILM = 'manufacturer-fujifilm';
    public const ADOX = 'manufacturer-adox';
    public const FOMA = 'manufacturer-foma';
    public const ROLLEI = 'manufacturer-rollei';

    public function load(ObjectManager $manager): void
    {
        foreach (
            $this->getData()
            as [$reference, $name, $website, $primaryColor, $secondaryColor, $tertiaryColor]
        ) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($name);
            $manufacturer->setWebsite($website);
            $manufacturer->setPrimaryColor($primaryColor);
            $manufacturer->setSecondaryColor($secondaryColor);
            $manufacturer->setTertiaryColor($tertiaryColor);

            $manager->persist($manufacturer);
            $this->addReference($reference, $manufacturer);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [self::KODAK, 'Kodak', 'https://www.kodak.gtcie.com/en', '#FAB617', '#ED0000', null],
            [self::ILFORD, 'Ilford', 'https://www.ilfordphoto.com/', '#FFFFFF', '#231F20', null],
            [
                self::FUJIFILM,
                'Fujifilm',
                'https://www.fujifilm.com/fr/en',
                '#000000',
                '#FFFFFF',
                '#FB0020',
            ],
            [self::ADOX, 'Adox', 'https://www.adox.de/Photo/en/', '#FFFFFF', '#E48157', null],
            [self::FOMA, 'Foma', 'https://www.foma.cz/en', '#ffffff', '#000000', null],
            [self::ROLLEI, 'Rollei', 'https://www.rollei.de/en', '#FEFEFE', '#000000', null],
        ];
    }
}

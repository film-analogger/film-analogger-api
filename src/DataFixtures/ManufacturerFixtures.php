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
        foreach ($this->getData() as [$reference, $name]) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($name);

            $manager->persist($manufacturer);
            $this->addReference($reference, $manufacturer);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [self::KODAK, 'Kodak', null, null, null],
            [self::ILFORD, 'Ilford', null, null, null],
            [self::FUJIFILM, 'Fujifilm', null, null, null],
            [self::ADOX, 'Adox', null, null, null],
            [self::FOMA, 'Foma', null, null, null],
            [self::ROLLEI, 'Rollei', null, null, null],
        ];
    }
}

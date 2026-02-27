<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: ['groups' => [Film::SERIALIZATION_READ_GROUP]],
        denormalizationContext: ['groups' => [Film::SERIALIZATION_WRITE_GROUP]],
    ),
]
class Film
{
    const SERIALIZATION_READ_GROUP = 'read-film';
    const SERIALIZATION_WRITE_GROUP = 'write-film';

    #[ODM\Id]
    #[Groups([Film::SERIALIZATION_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Groups([Film::SERIALIZATION_READ_GROUP, Film::SERIALIZATION_WRITE_GROUP])]
    public string $name;

    #[ODM\ReferenceOne(targetDocument: Manufacturer::class)]
    #[Groups([Film::SERIALIZATION_READ_GROUP, Film::SERIALIZATION_WRITE_GROUP])]
    public Manufacturer $manufacturer;

    public function __construct() {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setManufacturer(Manufacturer $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getManufacturer(): Manufacturer
    {
        return $this->manufacturer;
    }
}

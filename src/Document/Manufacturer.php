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
        normalizationContext: ['groups' => [Manufacturer::SERIALIZATION_READ_GROUP]],
        denormalizationContext: ['groups' => [Manufacturer::SERIALIZATION_WRITE_GROUP]],
    ),
]
class Manufacturer
{
    const SERIALIZATION_READ_GROUP = 'read-manufacturer';
    const SERIALIZATION_WRITE_GROUP = 'write-manufacturer';

    #[ODM\Id]
    #[Groups([Manufacturer::SERIALIZATION_READ_GROUP, Film::SERIALIZATION_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            Manufacturer::SERIALIZATION_READ_GROUP,
            Manufacturer::SERIALIZATION_WRITE_GROUP,
            Film::SERIALIZATION_READ_GROUP,
        ]),
    ]
    public string $name;

    #[ODM\ReferenceMany(targetDocument: Film::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[Groups([Manufacturer::SERIALIZATION_READ_GROUP, Manufacturer::SERIALIZATION_WRITE_GROUP])]
    public Collection $films;

    public function __construct()
    {
        $this->films = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function addFilm(Film $film): void
    {
        $film->setManufacturer($this);
        $this->films->add($film);
    }

    public function getFilms(): Collection
    {
        return $this->films;
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'groups' => [
                SerializationGroups::MANUFACTURER_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::MANUFACTURER_WRITE_GROUP]],
        operations: [
            new Get(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Post(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Patch(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Delete(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
        ],
    ),
]
class Manufacturer
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::MANUFACTURER_READ_GROUP, SerializationGroups::FILM_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
            SerializationGroups::FILM_READ_GROUP,
        ]),
    ]
    public string $name;

    #[ODM\ReferenceMany(targetDocument: Film::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $films;

    #[ODM\ReferenceMany(targetDocument: Chemistry::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $chemistries;

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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

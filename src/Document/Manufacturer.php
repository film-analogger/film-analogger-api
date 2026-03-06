<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiProperty;
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
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::MANUFACTURER_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
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
class Manufacturer implements Translatable
{
    use TimestampableBlameableTrait;
    use TranslatableTrait;

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

    #[ODM\Field(nullable: true)]
    #[Assert\CssColor]
    #[ApiProperty(example: '#FF0000')]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $primaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#00FF00')]
    #[Assert\CssColor]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $secondaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#0000FF')]
    #[Assert\CssColor]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $tertiaryColor = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $website = null;

    public function __construct()
    {
        $this->films = new ArrayCollection();
        $this->chemistries = new ArrayCollection();
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

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setSecondaryColor(?string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;
        return $this;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    public function setTertiaryColor(?string $tertiaryColor): static
    {
        $this->tertiaryColor = $tertiaryColor;
        return $this;
    }

    public function getTertiaryColor(): ?string
    {
        return $this->tertiaryColor;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function addChemistry(Chemistry $chemistry): static
    {
        $chemistry->setManufacturer($this);
        $this->chemistries->add($chemistry);
        return $this;
    }

    public function getChemistries(): Collection
    {
        return $this->chemistries;
    }

    public function setChemistries(Collection $chemistries): static
    {
        foreach ($chemistries as $chemistry) {
            $chemistry->setManufacturer($this);
        }
        $this->chemistries = $chemistries;
        return $this;
    }
}

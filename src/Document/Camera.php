<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::CAMERA_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::CAMERA_WRITE_GROUP]],
        operations: [
            new Get(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Post(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Patch(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Delete(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
        ],
    ),
]
class Camera implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::CAMERA_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Groups([SerializationGroups::CAMERA_READ_GROUP, SerializationGroups::CAMERA_WRITE_GROUP])]
    public string $name;

    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'cameras')]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    #[Groups([SerializationGroups::CAMERA_READ_GROUP, SerializationGroups::CAMERA_WRITE_GROUP])]
    public Manufacturer $manufacturer;

    #[ODM\Field(nullable: true)]
    #[
        Assert\Choice(
            choices: ProcessConstants::CAMERA_FILM_FORMATS,
            message: 'Choose a valid film format.',
        ),
    ]
    #[Groups([SerializationGroups::CAMERA_READ_GROUP, SerializationGroups::CAMERA_WRITE_GROUP])]
    public ?string $filmFormat = null;

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[Groups([SerializationGroups::CAMERA_READ_GROUP, SerializationGroups::CAMERA_WRITE_GROUP])]
    public ?string $description = null;

    public function __construct() {}

    public function getId(): ?string
    {
        return $this->id;
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

    public function setManufacturer(Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    public function getManufacturer(): Manufacturer
    {
        return $this->manufacturer;
    }

    public function setFilmFormat(?string $filmFormat): static
    {
        $this->filmFormat = $filmFormat;
        return $this;
    }

    public function getFilmFormat(): ?string
    {
        return $this->filmFormat;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}

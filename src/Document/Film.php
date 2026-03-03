<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\Repository\FilmRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document(repositoryClass: FilmRepository::class)]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::FILM_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [SerializationGroups::FILM_WRITE_GROUP],
        ],

        operations: [
            new Get(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Post(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Patch(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Delete(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
        ],
    ),
]
class Film implements Translatable
{
    use TranslatableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::FILM_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public string $name;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Gedmo\Translatable]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public string $description;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['C-41', 'E-6', 'B&W', 'ECN-2'], message: 'Choose a valid process.')]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public string $process;

    #[ODM\Field(nullable: true)]
    #[
        Assert\Choice(
            choices: ['panchromatic', 'orthochromatic', 'chromogene'],
            message: 'Choose a valid emulsion type.',
        ),
    ]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?string $emulsionType = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?bool $inversible = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Gedmo\Translatable]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?string $officialDocumentationUrl = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public int $sensibility;

    #[ODM\Field(nullable: true)]
    #[Assert\CssColor]
    #[ApiProperty(example: '#FF0000')]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?string $primaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#00FF00')]
    #[Assert\CssColor]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?string $secondaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#0000FF')]
    #[Assert\CssColor]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    public ?string $tertiaryColor = null;

    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'films')]
    #[Groups([SerializationGroups::FILM_READ_GROUP, SerializationGroups::FILM_WRITE_GROUP])]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setProcess(string $process): void
    {
        $this->process = $process;
    }

    public function getProcess(): string
    {
        return $this->process;
    }

    public function setEmulsionType(?string $emulsionType): void
    {
        $this->emulsionType = $emulsionType;
    }

    public function getEmulsionType(): ?string
    {
        return $this->emulsionType;
    }

    public function setInversible(?bool $inversible): void
    {
        $this->inversible = $inversible;
    }

    public function getInversible(): ?bool
    {
        return $this->inversible;
    }

    public function setOfficialDocumentationUrl(?string $officialDocumentationUrl): void
    {
        $this->officialDocumentationUrl = $officialDocumentationUrl;
    }

    public function getOfficialDocumentationUrl(): ?string
    {
        return $this->officialDocumentationUrl;
    }

    public function setSensibility(int $sensibility): void
    {
        $this->sensibility = $sensibility;
    }

    public function getSensibility(): int
    {
        return $this->sensibility;
    }

    public function setPrimaryColor(?string $primaryColor): void
    {
        $this->primaryColor = $primaryColor;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setSecondaryColor(?string $secondaryColor): void
    {
        $this->secondaryColor = $secondaryColor;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    public function setTertiaryColor(?string $tertiaryColor): void
    {
        $this->tertiaryColor = $tertiaryColor;
    }

    public function getTertiaryColor(): ?string
    {
        return $this->tertiaryColor;
    }
}

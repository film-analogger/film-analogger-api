<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::CHEMISTRY_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [SerializationGroups::CHEMISTRY_WRITE_GROUP],
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
class Chemistry
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::CHEMISTRY_READ_GROUP])]
    private string $id;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    private string $name;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Assert\Choice(
            choices: ProcessConstants::CHEMISTRY_PROCESSES,
            message: 'Choose a valid process.',
        ),
    ]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public string $process;

    #[ODM\ReferenceOne(targetDocument: ChemistryType::class, inversedBy: 'chemistries')]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    #[Assert\NotNull(message: 'Chemistry type must be set.')]
    public ChemistryType $chemistryType;

    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'chemistries')]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    public Manufacturer $manufacturer;

    #[ODM\ReferenceMany(targetDocument: Chemistry::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $chemistries;

    #[ODM\Field]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    private ?string $description = null;

    #[ODM\EmbedMany(targetDocument: Dilution::class)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public Collection $dilutions;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?string $officialDocumentationUrl = null;

    public function __construct()
    {
        $this->dilutions = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getProcess(): string
    {
        return $this->process;
    }

    public function setProcess(string $process): static
    {
        $this->process = $process;
        return $this;
    }

    public function getChemistryType(): ChemistryType
    {
        return $this->chemistryType;
    }

    public function setChemistryType(ChemistryType $chemistryType): static
    {
        $this->chemistryType = $chemistryType;
        return $this;
    }

    public function getManufacturer(): Manufacturer
    {
        return $this->manufacturer;
    }

    public function setManufacturer(Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    public function getChemistries(): Collection
    {
        return $this->chemistries;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDilutions(): Collection
    {
        return $this->dilutions;
    }

    public function addDilution(Dilution $dilution): void
    {
        if (!$this->dilutions->contains($dilution)) {
            $this->dilutions->add($dilution);
        }
    }

    public function removeDilution(Dilution $dilution): void
    {
        $this->dilutions->removeElement($dilution);
    }

    public function setOfficialDocumentationUrl(?string $officialDocumentationUrl): static
    {
        $this->officialDocumentationUrl = $officialDocumentationUrl;
        return $this;
    }

    public function getOfficialDocumentationUrl(): ?string
    {
        return $this->officialDocumentationUrl;
    }
}

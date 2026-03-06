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
use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::CHEMISTRY_TYPE_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [SerializationGroups::CHEMISTRY_TYPE_WRITE_GROUP],
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
class ChemistryType implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_TYPE_READ_GROUP,
        ]),
    ]
    private string $id;

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
            SerializationGroups::CHEMISTRY_TYPE_READ_GROUP,
            SerializationGroups::CHEMISTRY_TYPE_WRITE_GROUP,
        ]),
    ]
    public string $process = '';

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Assert\Choice(
            callback: 'getValidChemistryTypesForProcess',
            message: 'Choose a valid chemistry type for the given process.',
        ),
    ]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_TYPE_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_TYPE_WRITE_GROUP,
        ]),
    ]
    private string $typeCode;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_TYPE_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_TYPE_WRITE_GROUP,
        ]),
    ]
    private string $typeLabel;

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
        $this->chemistries = new ArrayCollection();
    }

    public function getValidChemistryTypesForProcess(): array
    {
        return ProcessConstants::getValidChemistryTypesForProcess($this->process);
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getTypeCode(): string
    {
        return $this->typeCode;
    }

    public function setTypeCode(string $typeCode): static
    {
        $this->typeCode = $typeCode;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return $this->typeLabel;
    }

    public function setTypeLabel(string $typeLabel): static
    {
        $this->typeLabel = $typeLabel;
        return $this;
    }

    public function addChemistry(Chemistry $chemistry): static
    {
        $chemistry->setChemistryType($this);
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
            $chemistry->setChemistryType($this);
        }
        $this->chemistries = $chemistries;
        return $this;
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\CatalogStatusTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use ApiPlatform\OpenApi\Model as Model;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::CHEMISTRY_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::CHEMISTRY_WRITE_GROUP,
                SerializationGroups::CATALOG_STATUS_WRITE_GROUP,
            ],
        ],

        operations: [
            new Get(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_READER .
                    '") and (object.isOfficial() or is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new GetCollection(
                security: 'is_granted("' . KeycloakRoles::DATA_READER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Post(
                security: 'is_granted("' . KeycloakRoles::USER . '")',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Patch(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and object.isOwnerEditableStatus())',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Delete(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and not object.isOfficial())',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
        ],
    ),
]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class Chemistry implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;
    use CatalogStatusTrait;

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

    // writableLink: false forces the OpenAPI write schema back to a plain
    // IRI string. Without it, API Platform sees that ChemistryType::$status
    // shares CATALOG_STATUS_WRITE_GROUP with this resource's own
    // denormalizationContext and documents `chemistryType` as an
    // embeddable object requiring `status`, even though only an IRI is
    // ever needed.
    #[ODM\ReferenceOne(targetDocument: ChemistryType::class, inversedBy: 'chemistries')]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    #[Assert\NotNull(message: 'Chemistry type must be set.')]
    #[ApiProperty(writableLink: false)]
    public ChemistryType $chemistryType;

    // Same writableLink: false rationale as chemistryType above, this time
    // because Manufacturer::$status shares the group too.
    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'chemistries')]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    #[ApiProperty(writableLink: false)]
    public Manufacturer $manufacturer;

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    private ?string $description = null;

    // Reference temperature range this chemistry is designed to work at (°C), as given
    // by the manufacturer's data sheet.
    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?float $referenceTemperatureMin = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?float $referenceTemperatureMax = null;

    // Storage life once the bottle/packet has been opened, in days.
    #[ODM\Field(nullable: true)]
    #[Assert\PositiveOrZero]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?int $openShelfLifeDays = null;

    #[ODM\EmbedMany(targetDocument: Dilution::class)]
    #[Assert\Valid]
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getReferenceTemperatureMin(): ?float
    {
        return $this->referenceTemperatureMin;
    }

    public function setReferenceTemperatureMin(?float $referenceTemperatureMin): static
    {
        $this->referenceTemperatureMin = $referenceTemperatureMin;
        return $this;
    }

    public function getReferenceTemperatureMax(): ?float
    {
        return $this->referenceTemperatureMax;
    }

    public function setReferenceTemperatureMax(?float $referenceTemperatureMax): static
    {
        $this->referenceTemperatureMax = $referenceTemperatureMax;
        return $this;
    }

    public function getOpenShelfLifeDays(): ?int
    {
        return $this->openShelfLifeDays;
    }

    public function setOpenShelfLifeDays(?int $openShelfLifeDays): static
    {
        $this->openShelfLifeDays = $openShelfLifeDays;
        return $this;
    }

    #[Assert\Callback]
    public function validateReferenceTemperatureRange(ExecutionContextInterface $context): void
    {
        if (
            null !== $this->referenceTemperatureMin &&
            null !== $this->referenceTemperatureMax &&
            $this->referenceTemperatureMin > $this->referenceTemperatureMax
        ) {
            $context
                ->buildViolation('referenceTemperatureMin cannot be greater than referenceTemperatureMax.')
                ->atPath('referenceTemperatureMin')
                ->addViolation();
        }
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

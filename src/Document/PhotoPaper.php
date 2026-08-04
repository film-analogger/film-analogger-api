<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model as Model;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperBase;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperSurface;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\CatalogStatusTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::PHOTO_PAPER_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
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
class PhotoPaper implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;
    use CatalogStatusTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::PHOTO_PAPER_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public string $name;

    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'photoPapers')]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public Manufacturer $manufacturer;

    #[ODM\Field(enumType: PaperBase::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public PaperBase $paperBase;

    #[ODM\Field(enumType: PaperSurface::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public PaperSurface $paperSurface;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public ?string $paperSurfaceOther = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
    public ?bool $variableContrast = null;

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_WRITE_GROUP,
        ]),
    ]
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

    public function setPaperBase(PaperBase $paperBase): static
    {
        $this->paperBase = $paperBase;
        return $this;
    }

    public function getPaperBase(): PaperBase
    {
        return $this->paperBase;
    }

    public function setPaperSurface(PaperSurface $paperSurface): static
    {
        $this->paperSurface = $paperSurface;
        return $this;
    }

    public function getPaperSurface(): PaperSurface
    {
        return $this->paperSurface;
    }

    public function setPaperSurfaceOther(?string $paperSurfaceOther): static
    {
        $this->paperSurfaceOther = $paperSurfaceOther;
        return $this;
    }

    public function getPaperSurfaceOther(): ?string
    {
        return $this->paperSurfaceOther;
    }

    public function setVariableContrast(?bool $variableContrast): static
    {
        $this->variableContrast = $variableContrast;
        return $this;
    }

    public function getVariableContrast(): ?bool
    {
        return $this->variableContrast;
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

    #[Assert\Callback]
    public function validatePaperSurfaceOther(ExecutionContextInterface $context): void
    {
        if (
            $this->paperSurface === PaperSurface::OTHER &&
            ($this->paperSurfaceOther === null || trim($this->paperSurfaceOther) === '')
        ) {
            $context
                ->buildViolation('paperSurfaceOther is required when paperSurface is OTHER.')
                ->atPath('paperSurfaceOther')
                ->addViolation();
        }
    }
}

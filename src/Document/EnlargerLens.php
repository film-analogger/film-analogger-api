<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model as Model;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\FocalLength;
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

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::ENLARGER_LENS_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
                SerializationGroups::CATALOG_STATUS_WRITE_GROUP,
            ],
        ],
        operations: [
            new Get(
                uriTemplate: '/enlarger_lenses/{id}',
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
                uriTemplate: '/enlarger_lenses',
                security: 'is_granted("' . KeycloakRoles::DATA_READER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Post(
                uriTemplate: '/enlarger_lenses',
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
                uriTemplate: '/enlarger_lenses/{id}',
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
                uriTemplate: '/enlarger_lenses/{id}',
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
class EnlargerLens implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;
    use CatalogStatusTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::ENLARGER_LENS_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
        ]),
    ]
    public string $name;

    // writableLink: false forces the OpenAPI write schema back to a plain
    // IRI string — see the identical rationale on Enlarger::$manufacturer.
    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'enlargerLenses')]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
        ]),
    ]
    public Manufacturer $manufacturer;

    #[ODM\Field(type: 'int', enumType: FocalLength::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
        ]),
    ]
    public FocalLength $focalLength;

    #[ODM\Field(type: 'float')]
    #[Assert\Positive]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
        ]),
    ]
    public float $maxAperture;

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_WRITE_GROUP,
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

    public function setFocalLength(FocalLength $focalLength): static
    {
        $this->focalLength = $focalLength;
        return $this;
    }

    public function getFocalLength(): FocalLength
    {
        return $this->focalLength;
    }

    public function setMaxAperture(float $maxAperture): static
    {
        $this->maxAperture = $maxAperture;
        return $this;
    }

    public function getMaxAperture(): float
    {
        return $this->maxAperture;
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

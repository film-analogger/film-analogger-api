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
use FilmAnalogger\FilmAnaloggerApi\Constant\EnlargerLightSource;
use FilmAnalogger\FilmAnaloggerApi\Constant\NegativeFormat;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
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
                SerializationGroups::ENLARGER_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::ENLARGER_WRITE_GROUP,
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
class Enlarger implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;
    use CatalogStatusTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
    public string $name;

    // writableLink: false forces the OpenAPI write schema back to a plain
    // IRI string. Without it, API Platform sees that Manufacturer::$status
    // shares CATALOG_STATUS_WRITE_GROUP with this resource's own
    // denormalizationContext and documents `manufacturer` as an embeddable
    // object requiring `status`, even though only an IRI is ever needed.
    #[ODM\ReferenceOne(targetDocument: Manufacturer::class, inversedBy: 'enlargers')]
    #[Assert\NotNull(message: 'Manufacturer must be set.')]
    #[ApiProperty(writableLink: false)]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
    public Manufacturer $manufacturer;

    #[ODM\Field(nullable: true, enumType: EnlargerLightSource::class)]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
    public ?EnlargerLightSource $lightSource = null;

    // Film formats this enlarger can print from (via its interchangeable
    // negative carriers/condensers/lenses), e.g. a modular enlarger might
    // support both 135 and 120. Plural, unlike Camera::$filmFormat, since
    // one enlarger can handle several.
    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    #[Assert\All([new Assert\Choice(choices: ProcessConstants::CAMERA_FILM_FORMATS)])]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
    public array $filmFormats = [];

    // Negative formats this enlarger can print from, e.g. 120 roll film
    // itself can yield 6x6, 6x7 or 6x9 negatives depending on the camera
    // back — this is the frame size, distinct from filmFormats above.
    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    #[Assert\All([new Assert\Choice(callback: [NegativeFormat::class, 'values'])])]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
    public array $negativeFormats = [];

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[Groups([SerializationGroups::ENLARGER_READ_GROUP, SerializationGroups::ENLARGER_WRITE_GROUP])]
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

    public function setLightSource(?EnlargerLightSource $lightSource): static
    {
        $this->lightSource = $lightSource;
        return $this;
    }

    public function getLightSource(): ?EnlargerLightSource
    {
        return $this->lightSource;
    }

    public function setFilmFormats(array $filmFormats): static
    {
        $this->filmFormats = $filmFormats;
        return $this;
    }

    public function getFilmFormats(): array
    {
        return $this->filmFormats;
    }

    public function setNegativeFormats(array $negativeFormats): static
    {
        $this->negativeFormats = $negativeFormats;
        return $this;
    }

    public function getNegativeFormats(): array
    {
        return $this->negativeFormats;
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

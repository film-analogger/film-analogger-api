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
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperFormat;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

// A PhotoPaperStock entry is personal data: how many sheets of a given
// PhotoPaper/format a user has on hand. It belongs only to its creator (see
// OwnedByCurrentUserExtension for the collection-scoping half of this),
// except for admins who can see everything. Mirrors the DevelopmentLog/
// PrintSession convention: ROLE_data_writer gates writes, ownership is
// checked per-item via object.getCreatedBy().
#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP]],
        operations: [
            new Get(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                        SerializationGroups::PHOTO_PAPER_READ_GROUP,
                        SerializationGroups::TRANSLATABLE_READ_GROUP,
                        SerializationGroups::CATALOG_STATUS_READ_GROUP,
                    ],
                ],
                security: 'is_granted("' .
                    KeycloakRoles::DATA_READER .
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
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
                security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")',
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
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
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
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
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
#[ApiFilter(SearchFilter::class, properties: ['photoPaper' => 'exact'])]
class PhotoPaperStock
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: PhotoPaper::class, storeAs: 'id')]
    #[Assert\NotNull(message: 'Photo paper must be set.')]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public PhotoPaper $photoPaper;

    #[ODM\Field(enumType: PaperFormat::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public PaperFormat $format;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public int $quantity;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public ?\DateTimeImmutable $purchasedAt = null;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public ?\DateTimeImmutable $expiresAt = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PHOTO_PAPER_STOCK_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_STOCK_WRITE_GROUP,
        ]),
    ]
    public ?string $notes = null;

    public function __construct() {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setPhotoPaper(PhotoPaper $photoPaper): static
    {
        $this->photoPaper = $photoPaper;
        return $this;
    }

    public function getPhotoPaper(): PhotoPaper
    {
        return $this->photoPaper;
    }

    public function setFormat(PaperFormat $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function getFormat(): PaperFormat
    {
        return $this->format;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setPurchasedAt(?\DateTimeImmutable $purchasedAt): static
    {
        $this->purchasedAt = $purchasedAt;
        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}

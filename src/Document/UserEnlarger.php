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
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

// A UserEnlarger entry is personal data: which enlarger(s) a user owns. It
// belongs only to its creator (see OwnedByCurrentUserExtension for the
// collection-scoping half of this), except for admins who can see
// everything. Mirrors the DevelopmentLog/PrintSession convention:
// ROLE_data_writer gates writes, ownership is checked per-item via
// object.getCreatedBy().
#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::USER_ENLARGER_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::USER_ENLARGER_WRITE_GROUP]],
        operations: [
            new Get(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::USER_ENLARGER_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                        SerializationGroups::ENLARGER_READ_GROUP,
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
#[ApiFilter(SearchFilter::class, properties: ['enlarger' => 'exact'])]
class UserEnlarger
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::USER_ENLARGER_READ_GROUP])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Enlarger::class, storeAs: 'id')]
    #[Assert\NotNull(message: 'Enlarger must be set.')]
    #[
        Groups([
            SerializationGroups::USER_ENLARGER_READ_GROUP,
            SerializationGroups::USER_ENLARGER_WRITE_GROUP,
        ]),
    ]
    public Enlarger $enlarger;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::USER_ENLARGER_READ_GROUP,
            SerializationGroups::USER_ENLARGER_WRITE_GROUP,
        ]),
    ]
    public int $quantity = 1;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    #[
        Groups([
            SerializationGroups::USER_ENLARGER_READ_GROUP,
            SerializationGroups::USER_ENLARGER_WRITE_GROUP,
        ]),
    ]
    public ?\DateTimeImmutable $acquiredAt = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::USER_ENLARGER_READ_GROUP,
            SerializationGroups::USER_ENLARGER_WRITE_GROUP,
        ]),
    ]
    public ?string $notes = null;

    public function __construct() {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setEnlarger(Enlarger $enlarger): static
    {
        $this->enlarger = $enlarger;
        return $this;
    }

    public function getEnlarger(): Enlarger
    {
        return $this->enlarger;
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

    public function setAcquiredAt(?\DateTimeImmutable $acquiredAt): static
    {
        $this->acquiredAt = $acquiredAt;
        return $this;
    }

    public function getAcquiredAt(): ?\DateTimeImmutable
    {
        return $this->acquiredAt;
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

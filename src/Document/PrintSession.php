<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model as Model;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;
use FilmAnalogger\FilmAnaloggerApi\Repository\PrintSessionRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiFilter(DateFilter::class, properties: ['date'])]
#[ODM\Document(repositoryClass: PrintSessionRepository::class)]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::PRINT_SESSION_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [SerializationGroups::PRINT_SESSION_WRITE_GROUP],
        ],
        operations: [
            new Get(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::PRINT_SESSION_READ_GROUP,
                        SerializationGroups::PRINT_SESSION_ITEM_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
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
                // No PRINT_SESSION_ITEM_READ_GROUP here: embedding `prints` in
                // every row of a collection listing would trigger one extra
                // PrintWork query per session (N+1). Fetch a session's prints
                // via GET /print_sessions/{id} or /print_sessions/{id}/prints.
                security: 'is_granted("' . KeycloakRoles::DATA_READER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Post(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::PRINT_SESSION_READ_GROUP,
                        SerializationGroups::PRINT_SESSION_ITEM_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                    ],
                ],
                security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Patch(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::PRINT_SESSION_READ_GROUP,
                        SerializationGroups::PRINT_SESSION_ITEM_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                    ],
                ],
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
class PrintSession
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::PRINT_SESSION_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field(type: 'date_immutable')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public \DateTimeImmutable $date;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public string $lab;

    #[ODM\Field(type: 'int')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public int $number;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public string $enlarger;

    #[ODM\Field(type: 'float')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public float $temperatureCelsius;

    // Ordered chain of baths (developer, stop, fixer, toner, extra clearing
    // baths...). A list rather than fixed developer/stopBath/fixer/toner
    // fields so the process can vary from session to session — a lab might
    // skip the stop bath, run a two-bath fixer, or add a hardener step.
    // Order in the array is the process order; role/nature of each bath is
    // whatever the referenced Chemistry's own chemistryType says it is.
    #[ODM\EmbedMany(targetDocument: ChemicalBath::class)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'A print session must have at least one chemical bath.')]
    #[
        ApiProperty(
            example: [
                ['chemistry' => '/chemistries/revelo-id', 'durationSeconds' => 60],
                ['chemistry' => '/chemistries/stop-id', 'durationSeconds' => 30],
                ['chemistry' => '/chemistries/fix-id', 'durationSeconds' => 300],
            ],
        ),
    ]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public Collection $chemicalBaths;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public ?string $wash = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public ?string $notes = null;

    #[ODM\ReferenceMany(targetDocument: PrintWork::class, mappedBy: 'session', sort: ['number' => 1])]
    #[Groups([SerializationGroups::PRINT_SESSION_ITEM_READ_GROUP])]
    public Collection $prints;

    public function __construct()
    {
        $this->prints = new ArrayCollection();
        $this->chemicalBaths = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getLab(): string
    {
        return $this->lab;
    }

    public function setLab(string $lab): static
    {
        $this->lab = $lab;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function getEnlarger(): string
    {
        return $this->enlarger;
    }

    public function setEnlarger(string $enlarger): static
    {
        $this->enlarger = $enlarger;
        return $this;
    }

    public function getTemperatureCelsius(): float
    {
        return $this->temperatureCelsius;
    }

    public function setTemperatureCelsius(float $temperatureCelsius): static
    {
        $this->temperatureCelsius = $temperatureCelsius;
        return $this;
    }

    public function getChemicalBaths(): Collection
    {
        return $this->chemicalBaths;
    }

    public function setChemicalBaths(Collection $chemicalBaths): static
    {
        $this->chemicalBaths = $chemicalBaths;
        return $this;
    }

    public function addChemicalBath(ChemicalBath $bath): static
    {
        $this->chemicalBaths->add($bath);
        return $this;
    }

    public function removeChemicalBath(ChemicalBath $bath): void
    {
        $this->chemicalBaths->removeElement($bath);
    }

    public function getWash(): ?string
    {
        return $this->wash;
    }

    public function setWash(?string $wash): static
    {
        $this->wash = $wash;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPrints(): Collection
    {
        return $this->prints;
    }
}

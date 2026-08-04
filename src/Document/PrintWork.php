<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model as Model;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\Condenser;
use FilmAnalogger\FilmAnaloggerApi\Constant\ExposureKind;
use FilmAnalogger\FilmAnaloggerApi\Constant\FocalLength;
use FilmAnalogger\FilmAnaloggerApi\Constant\NegativeFormat;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;
use FilmAnalogger\FilmAnaloggerApi\Repository\PrintWorkRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

// The PHP class is named PrintWork because `print` is a reserved language
// construct and cannot be used as a class name. The API-facing resource
// keeps shortName "Print" so routes stay /prints, /print_sessions/{id}/prints.
// fromProperty (rather than toProperty) so this resolves via
// a `_id IN (...)` match instead of a raw `$lookup` path
// comparison, which doesn't type-cast string IDs to ObjectId.
// This route's own security only checks the role: per-user scoping
// (non-admins see only their own prints) is enforced entirely by
// OwnedByCurrentUserExtension, which applies to any GetCollection on
// PrintWork/PrintSession regardless of URI. See that class' docblock.
// `grade` filtering (per the print docket spec) lives on the nested
// Exposure documents, not on PrintWork itself; filtering a ReferenceMany
// by a related document's field needs a custom $lookup-based filter and is
// deferred — see plan notes. session/photoPaper/contactSheetRef/
// negativeNumber are plain PrintWork properties and filter directly.
#[ODM\Document(repositoryClass: PrintWorkRepository::class)]
#[
    ApiResource(
        shortName: 'Print',
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::PRINT_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [SerializationGroups::PRINT_WRITE_GROUP],
        ],
        operations: [
            new Get(
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
            new GetCollection(
                uriTemplate: '/print_sessions/{id}/prints',
                uriVariables: [
                    'id' => new Link(fromClass: PrintSession::class, fromProperty: 'prints'),
                ],
                security: 'is_granted("' . KeycloakRoles::DATA_READER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Post(
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.session.getCreatedBy() === user.getUserIdentifier())',
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
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.session.getCreatedBy() === user.getUserIdentifier()',
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
#[
    ApiFilter(
        SearchFilter::class,
        properties: [
            'session' => 'exact',
            'photoPaper' => 'exact',
            'contactSheetRef' => 'partial',
            'negativeNumber' => 'partial',
        ],
    ),
]
class PrintWork
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::PRINT_READ_GROUP])]
    private ?string $id = null;

    // storeAs: 'id' (rather than the default dbRef) is required for the
    // /print_sessions/{id}/prints sub-resource, which resolves via a Mongo
    // $lookup aggregation stage — $lookup doesn't support dbRef references.
    #[ODM\ReferenceOne(targetDocument: PrintSession::class, inversedBy: 'prints', storeAs: 'id')]
    #[Assert\NotNull(message: 'Session must be set.')]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public PrintSession $session;

    #[ODM\Field(type: 'int')]
    #[Assert\NotNull]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public int $number;

    // --- Négatif ---

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?string $filmFormat = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?string $contactSheetRef = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?string $negativeNumber = null;

    // --- Poste ---

    #[ODM\Field(nullable: true, enumType: NegativeFormat::class)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?NegativeFormat $negativeFormat = null;

    #[ODM\Field(nullable: true, enumType: FocalLength::class)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?FocalLength $focalLength = null;

    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    #[Assert\All([new Assert\Choice(callback: [Condenser::class, 'values'])])]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public array $condensers = [];

    // --- Agrandissement ---

    #[ODM\Field(type: 'float', nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?float $columnHeightCm = null;

    #[ODM\Field(type: 'float', nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?float $paperWidthCm = null;

    #[ODM\Field(type: 'float', nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?float $paperHeightCm = null;

    #[ODM\Field(type: 'float', nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?float $borderCm = null;

    #[ODM\Field(type: 'int', nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?int $copies = null;

    // --- Papier ---

    #[ODM\ReferenceOne(targetDocument: PhotoPaper::class, storeAs: 'id')]
    #[Assert\NotNull(message: 'Photo paper must be set.')]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public PhotoPaper $photoPaper;

    // --- Exposition ---

    #[ODM\Field(type: 'float', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?float $preFlashSeconds = null;

    #[
        ODM\ReferenceMany(
            targetDocument: Exposure::class,
            mappedBy: 'print',
            sort: ['order' => 1],
            cascade: ['persist'],
            orphanRemoval: true,
        ),
    ]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'A print must have at least one exposure.')]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public Collection $exposures;

    // --- Divers ---

    // Free-text description of any dodge/burn masks used (e.g. "rectangle +
    // 2 personnages"). Distinct from the paper-form's raw notes field. The
    // mask shape itself isn't modelled — the sheet's own sketch/photo is the
    // record (see PrintAttachment, out of scope for this iteration).
    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?string $maskingNotes = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::PRINT_READ_GROUP, SerializationGroups::PRINT_WRITE_GROUP])]
    public ?string $notes = null;

    public function __construct()
    {
        $this->exposures = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSession(): PrintSession
    {
        return $this->session;
    }

    public function setSession(PrintSession $session): static
    {
        $this->session = $session;
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

    public function getFilmFormat(): ?string
    {
        return $this->filmFormat;
    }

    public function setFilmFormat(?string $filmFormat): static
    {
        $this->filmFormat = $filmFormat;
        return $this;
    }

    public function getContactSheetRef(): ?string
    {
        return $this->contactSheetRef;
    }

    public function setContactSheetRef(?string $contactSheetRef): static
    {
        $this->contactSheetRef = $contactSheetRef;
        return $this;
    }

    public function getNegativeNumber(): ?string
    {
        return $this->negativeNumber;
    }

    public function setNegativeNumber(?string $negativeNumber): static
    {
        $this->negativeNumber = $negativeNumber;
        return $this;
    }

    public function getNegativeFormat(): ?NegativeFormat
    {
        return $this->negativeFormat;
    }

    public function setNegativeFormat(?NegativeFormat $negativeFormat): static
    {
        $this->negativeFormat = $negativeFormat;
        return $this;
    }

    public function getFocalLength(): ?FocalLength
    {
        return $this->focalLength;
    }

    public function setFocalLength(?FocalLength $focalLength): static
    {
        $this->focalLength = $focalLength;
        return $this;
    }

    public function getCondensers(): array
    {
        return $this->condensers;
    }

    public function setCondensers(array $condensers): static
    {
        $this->condensers = $condensers;
        return $this;
    }

    public function getColumnHeightCm(): ?float
    {
        return $this->columnHeightCm;
    }

    public function setColumnHeightCm(?float $columnHeightCm): static
    {
        $this->columnHeightCm = $columnHeightCm;
        return $this;
    }

    public function getPaperWidthCm(): ?float
    {
        return $this->paperWidthCm;
    }

    public function setPaperWidthCm(?float $paperWidthCm): static
    {
        $this->paperWidthCm = $paperWidthCm;
        return $this;
    }

    public function getPaperHeightCm(): ?float
    {
        return $this->paperHeightCm;
    }

    public function setPaperHeightCm(?float $paperHeightCm): static
    {
        $this->paperHeightCm = $paperHeightCm;
        return $this;
    }

    public function getBorderCm(): ?float
    {
        return $this->borderCm;
    }

    public function setBorderCm(?float $borderCm): static
    {
        $this->borderCm = $borderCm;
        return $this;
    }

    public function getCopies(): ?int
    {
        return $this->copies;
    }

    public function setCopies(?int $copies): static
    {
        $this->copies = $copies;
        return $this;
    }

    public function getPhotoPaper(): PhotoPaper
    {
        return $this->photoPaper;
    }

    public function setPhotoPaper(PhotoPaper $photoPaper): static
    {
        $this->photoPaper = $photoPaper;
        return $this;
    }

    public function getPreFlashSeconds(): ?float
    {
        return $this->preFlashSeconds;
    }

    public function setPreFlashSeconds(?float $preFlashSeconds): static
    {
        $this->preFlashSeconds = $preFlashSeconds;
        return $this;
    }

    public function getMaskingNotes(): ?string
    {
        return $this->maskingNotes;
    }

    public function setMaskingNotes(?string $maskingNotes): static
    {
        $this->maskingNotes = $maskingNotes;
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

    public function getExposures(): Collection
    {
        return $this->exposures;
    }

    public function addExposure(Exposure $exposure): static
    {
        $exposure->setPrint($this);
        if (!$this->exposures->contains($exposure)) {
            $this->exposures->add($exposure);
        }
        return $this;
    }

    public function removeExposure(Exposure $exposure): void
    {
        $this->exposures->removeElement($exposure);
    }

    #[Assert\Callback]
    public function validateExposures(ExecutionContextInterface $context): void
    {
        $exposures = $this->exposures;
        $baseCount = 0;
        $seenOrders = [];

        foreach ($exposures as $exposure) {
            if ($exposure->getKind() === ExposureKind::BASE) {
                $baseCount++;
            }
            if (isset($seenOrders[$exposure->getOrder()])) {
                $context
                    ->buildViolation('Exposure order must be unique within a print.')
                    ->atPath('exposures')
                    ->addViolation();
            }
            $seenOrders[$exposure->getOrder()] = true;
        }

        if ($baseCount !== 1) {
            $context
                ->buildViolation('A print must have exactly one BASE exposure.')
                ->atPath('exposures')
                ->addViolation();
        }
    }
}

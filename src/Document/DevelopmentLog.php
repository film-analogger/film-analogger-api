<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\ProcessConstants;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

// A DevelopmentLog is personal data: it belongs only to its creator (see
// FilmAnalogger\FilmAnaloggerApi\Doctrine\OwnedByCurrentUserExtension for the
// collection-scoping half of this), except for admins who can see everything.
// This mirrors the PrintSession/PrintWork convention: DATA_READER/DATA_WRITER
// gate the role, ownership is checked per-item via object.getCreatedBy().

// Deep-embeds film/camera (and their own manufacturer) and
// tags instead of leaving them as bare IRIs, so a log entry
// renders from a single request. Scoped to this item Get
// only: GetCollection keeps the base (shallow) context below
// so listing many logs doesn't trigger a Film/Camera lookup
// per row.
#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP]],
        operations: [
            new Get(
                normalizationContext: [
                    'skip_null_values' => false,
                    'groups' => [
                        SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
                        SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                        SerializationGroups::FILM_READ_GROUP,
                        SerializationGroups::CAMERA_READ_GROUP,
                        SerializationGroups::TAG_READ_GROUP,
                        SerializationGroups::TRANSLATABLE_READ_GROUP,
                        SerializationGroups::CATALOG_STATUS_READ_GROUP,
                    ],
                ],
                security: 'is_granted("' .
                    KeycloakRoles::DATA_READER .
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
            ),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Post(security: 'is_granted("' . KeycloakRoles::DATA_WRITER . '")'),
            new Patch(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
            ),
            new Delete(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") and (is_granted("' .
                    KeycloakRoles::ADMIN .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
            ),
        ],
    ),
]
class DevelopmentLog
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::DEVELOPMENT_LOG_READ_GROUP])]
    private ?string $id = null;

    // ── Shooting ─────────────────────────────────────────────────────────

    #[ODM\ReferenceOne(targetDocument: Film::class)]
    #[Assert\NotNull(message: 'Film must be set.')]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public Film $film;

    #[ODM\ReferenceOne(targetDocument: Camera::class)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?Camera $camera = null;

    #[ODM\EmbedOne(targetDocument: ApproximateDate::class)]
    #[Assert\Valid]
    #[Assert\NotNull(message: 'Shot date must be set.')]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ApproximateDate $shotAt;

    #[ODM\Field]
    #[Assert\Positive]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public int $isoShotAt;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?string $shootingNotes = null;

    // ── Development ──────────────────────────────────────────────────────

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
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public string $process;

    #[ODM\Field(type: 'date_immutable')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public \DateTimeImmutable $developedAt;

    #[ODM\EmbedMany(targetDocument: DevelopmentStep::class)]
    #[Assert\Valid]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public Collection $steps;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?string $developmentNotes = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?int $rating = null;

    #[ODM\ReferenceMany(targetDocument: Tag::class, storeAs: 'id')]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public Collection $tags;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setFilm(Film $film): static
    {
        $this->film = $film;
        return $this;
    }

    public function getFilm(): Film
    {
        return $this->film;
    }

    public function setCamera(?Camera $camera): static
    {
        $this->camera = $camera;
        return $this;
    }

    public function getCamera(): ?Camera
    {
        return $this->camera;
    }

    public function setShotAt(ApproximateDate $shotAt): static
    {
        $this->shotAt = $shotAt;
        return $this;
    }

    public function getShotAt(): ApproximateDate
    {
        return $this->shotAt;
    }

    public function setIsoShotAt(int $isoShotAt): static
    {
        $this->isoShotAt = $isoShotAt;
        return $this;
    }

    public function getIsoShotAt(): int
    {
        return $this->isoShotAt;
    }

    public function setShootingNotes(?string $shootingNotes): static
    {
        $this->shootingNotes = $shootingNotes;
        return $this;
    }

    public function getShootingNotes(): ?string
    {
        return $this->shootingNotes;
    }

    public function setProcess(string $process): static
    {
        $this->process = $process;
        return $this;
    }

    public function getProcess(): string
    {
        return $this->process;
    }

    public function setDevelopedAt(\DateTimeImmutable $developedAt): static
    {
        $this->developedAt = $developedAt;
        return $this;
    }

    public function getDevelopedAt(): \DateTimeImmutable
    {
        return $this->developedAt;
    }

    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(DevelopmentStep $step): void
    {
        $this->steps->add($step);
    }

    public function removeStep(DevelopmentStep $step): void
    {
        $this->steps->removeElement($step);
    }

    public function setDevelopmentNotes(?string $developmentNotes): static
    {
        $this->developmentNotes = $developmentNotes;
        return $this;
    }

    public function getDevelopmentNotes(): ?string
    {
        return $this->developmentNotes;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Exposure index relative to the film's nominal sensibility, in stops (e.g. +1.0 = pushed one stop).
     */
    #[Groups([SerializationGroups::DEVELOPMENT_LOG_READ_GROUP])]
    public function getPushPullStops(): float
    {
        return round(log($this->isoShotAt / $this->film->getSensibility(), 2), 2);
    }
}

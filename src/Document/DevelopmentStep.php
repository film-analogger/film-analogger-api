<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A single bath of a development session (developer, stop, fixer, wetting agent...), with the
 * dilution actually used, which may differ from the dilutions catalogued on the referenced Chemistry.
 */
#[ODM\EmbeddedDocument]
class DevelopmentStep
{
    #[ODM\ReferenceOne(targetDocument: Chemistry::class)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?Chemistry $chemistry = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Positive]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?int $chemistryParts = null;

    #[ODM\Field(nullable: true)]
    #[Assert\PositiveOrZero]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?int $waterParts = null;

    #[ODM\Field]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public float $temperature;

    #[ODM\Field]
    #[Assert\Positive]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public int $durationSeconds;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?string $agitationNote = null;

    public function getChemistry(): ?Chemistry
    {
        return $this->chemistry;
    }

    public function setChemistry(?Chemistry $chemistry): static
    {
        $this->chemistry = $chemistry;
        return $this;
    }

    public function getChemistryParts(): ?int
    {
        return $this->chemistryParts;
    }

    public function setChemistryParts(?int $chemistryParts): static
    {
        $this->chemistryParts = $chemistryParts;
        return $this;
    }

    public function getWaterParts(): ?int
    {
        return $this->waterParts;
    }

    public function setWaterParts(?int $waterParts): static
    {
        $this->waterParts = $waterParts;
        return $this;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): static
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;
        return $this;
    }

    public function getAgitationNote(): ?string
    {
        return $this->agitationNote;
    }

    public function setAgitationNote(?string $agitationNote): static
    {
        $this->agitationNote = $agitationNote;
        return $this;
    }
}

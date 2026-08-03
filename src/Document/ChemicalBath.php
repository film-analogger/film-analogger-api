<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\EmbeddedDocument]
class ChemicalBath
{
    #[ODM\ReferenceOne(targetDocument: Chemistry::class)]
    #[Assert\NotNull(message: 'Chemistry must be set.')]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public Chemistry $chemistry;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public ?string $dilutionOverride = null;

    // Nullable: some baths (toner in particular) are noted on the paper
    // form with just a product name and no timed duration.
    #[ODM\Field(type: 'int', nullable: true)]
    #[Assert\Positive]
    #[
        Groups([
            SerializationGroups::PRINT_SESSION_READ_GROUP,
            SerializationGroups::PRINT_SESSION_WRITE_GROUP,
        ]),
    ]
    public ?int $durationSeconds = null;

    public function getChemistry(): Chemistry
    {
        return $this->chemistry;
    }

    public function setChemistry(Chemistry $chemistry): static
    {
        $this->chemistry = $chemistry;
        return $this;
    }

    public function getDilutionOverride(): ?string
    {
        return $this->dilutionOverride;
    }

    public function setDilutionOverride(?string $dilutionOverride): static
    {
        $this->dilutionOverride = $dilutionOverride;
        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(?int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;
        return $this;
    }

    /**
     * The dilution actually used for this bath: the explicit override if
     * set, otherwise the catalogued Chemistry's official dilution (falling
     * back to its first dilution if none is flagged official).
     */
    #[Groups([SerializationGroups::PRINT_SESSION_READ_GROUP])]
    public function getEffectiveDilution(): ?string
    {
        if ($this->dilutionOverride !== null) {
            return $this->dilutionOverride;
        }

        $dilutions = $this->chemistry->getDilutions();
        $default = $dilutions->filter(fn(Dilution $dilution) => $dilution->isOfficial())->first();

        if ($default === false) {
            $default = $dilutions->first();
        }

        return $default !== false ? $default->getLabel() : null;
    }
}

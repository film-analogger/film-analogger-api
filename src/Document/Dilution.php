<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ODM\EmbeddedDocument]
class Dilution
{
    #[ODM\Field]
    #[Assert\Positive]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public int $chemistryParts = 1;

    #[ODM\Field]
    #[Assert\PositiveOrZero]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public int $waterParts = 0;

    #[ODM\Field]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public bool $official = false;

    // Free-text description of when to use this dilution, e.g. "1+4 film, 1+9 paper".
    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?string $description = null;

    #[ODM\Field]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public bool $reusable = false;

    // Whether this reusable dilution needs to be topped up with fresh chemistry between
    // uses rather than used as-is until exhausted. Only meaningful when reusable is true.
    #[ODM\Field]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public bool $topUpRequired = false;

    // Free-text usage notes, e.g. "reusable for 8-10 films per litre, add 10% to the time for each film".
    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?string $notes = null;

    // Free-text capacity, e.g. "10 films/litre" or "1.5m2 of paper, i.e. x sheets of 18x24".
    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?string $capacity = null;

    // Storage life once diluted, in days.
    #[ODM\Field(nullable: true)]
    #[Assert\PositiveOrZero]
    #[
        Groups([
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::CHEMISTRY_WRITE_GROUP,
        ]),
    ]
    public ?int $dilutedShelfLifeDays = null;

    public function getChemistryParts(): int
    {
        return $this->chemistryParts;
    }

    public function setChemistryParts(int $chemistryParts): static
    {
        $this->chemistryParts = $chemistryParts;
        return $this;
    }

    public function getWaterParts(): int
    {
        return $this->waterParts;
    }

    public function setWaterParts(int $waterParts): static
    {
        $this->waterParts = $waterParts;
        return $this;
    }

    public function isOfficial(): bool
    {
        return $this->official;
    }

    public function setOfficial(bool $official): static
    {
        $this->official = $official;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isReusable(): bool
    {
        return $this->reusable;
    }

    public function setReusable(bool $reusable): static
    {
        $this->reusable = $reusable;
        return $this;
    }

    public function isTopUpRequired(): bool
    {
        return $this->topUpRequired;
    }

    public function setTopUpRequired(bool $topUpRequired): static
    {
        $this->topUpRequired = $topUpRequired;
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

    public function getCapacity(): ?string
    {
        return $this->capacity;
    }

    public function setCapacity(?string $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getDilutedShelfLifeDays(): ?int
    {
        return $this->dilutedShelfLifeDays;
    }

    public function setDilutedShelfLifeDays(?int $dilutedShelfLifeDays): static
    {
        $this->dilutedShelfLifeDays = $dilutedShelfLifeDays;
        return $this;
    }

    #[Assert\Callback]
    public function validateTopUpRequiresReusable(ExecutionContextInterface $context): void
    {
        if ($this->topUpRequired && !$this->reusable) {
            $context
                ->buildViolation('topUpRequired can only be set when reusable is true.')
                ->atPath('topUpRequired')
                ->addViolation();
        }
    }

    /**
     * Returns the human-readable label: "stock" when undiluted, or e.g. "1+1", "1+2".
     */
    #[Groups([SerializationGroups::CHEMISTRY_READ_GROUP])]
    public function getLabel(): string
    {
        if ($this->waterParts === 0) {
            return 'stock';
        }

        return sprintf('%d+%d', $this->chemistryParts, $this->waterParts);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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

    public function getChemistryParts(): int
    {
        return $this->chemistryParts;
    }

    public function setChemistryParts(int $chemistryParts): void
    {
        $this->chemistryParts = $chemistryParts;
    }

    public function getWaterParts(): int
    {
        return $this->waterParts;
    }

    public function setWaterParts(int $waterParts): void
    {
        $this->waterParts = $waterParts;
    }

    public function isOfficial(): bool
    {
        return $this->official;
    }

    public function setOfficial(bool $official): void
    {
        $this->official = $official;
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

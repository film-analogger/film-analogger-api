<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document\Trait;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

trait CatalogStatusTrait
{
    #[ODM\Field(enumType: CatalogStatus::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::CATALOG_STATUS_READ_GROUP,
            SerializationGroups::CATALOG_STATUS_WRITE_GROUP,
        ]),
    ]
    public CatalogStatus $status = CatalogStatus::PERSONAL;

    public function getStatus(): CatalogStatus
    {
        return $this->status;
    }

    public function setStatus(CatalogStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isOfficial(): bool
    {
        return $this->status === CatalogStatus::OFFICIAL;
    }

    public function isPersonalOrPending(): bool
    {
        return in_array($this->status, [CatalogStatus::PERSONAL, CatalogStatus::PENDING], true);
    }

    public function isOwnerEditableStatus(): bool
    {
        return in_array($this->status, [CatalogStatus::PERSONAL, CatalogStatus::REJECTED], true);
    }
}

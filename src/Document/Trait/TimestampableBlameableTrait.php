<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document\Trait;

use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;

use Gedmo\Blameable\Traits\BlameableDocument;
use Gedmo\Timestampable\Traits\TimestampableDocument;

trait TimestampableBlameableTrait
{
    use BlameableDocument {
        getCreatedBy as protected parentGetCreatedBy;
        getUpdatedBy as protected parentGetUpdatedBy;
    }

    use TimestampableDocument {
        getCreatedAt as protected parentGetCreatedAt;
        getUpdatedAt as protected parentGetUpdatedAt;
    }

    #[Groups([SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP])]
    public function getCreatedBy(): ?string
    {
        return $this->parentGetCreatedBy();
    }

    #[Groups([SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP])]
    public function getUpdatedBy(): ?string
    {
        return $this->parentGetUpdatedBy();
    }

    #[Groups([SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP])]
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->parentGetCreatedAt();
    }

    #[Groups([SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP])]
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->parentGetUpdatedAt();
    }
}

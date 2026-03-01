<?php

namespace FilmAnalogger\FilmAnaloggerApi\POPO;

use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;

class TranslatedField
{
    #[Groups([SerializationGroups::TRANSLATABLE_READ_GROUP])]
    public string $field;
    #[Groups([SerializationGroups::TRANSLATABLE_READ_GROUP])]
    public string $locale;

    public function __construct(string $field, string $locale)
    {
        $this->field = $field;
        $this->locale = $locale;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}

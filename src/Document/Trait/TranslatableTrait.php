<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document\Trait;

use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

trait TranslatableTrait
{
    #[Gedmo\Locale]
    private $locale;

    /**
     * array of TranslatedField objects, each containing the name of the translated field and the locale it was translated into
     */
    #[Groups([SerializationGroups::TRANSLATABLE_READ_GROUP])]
    private array $translations = [];

    #[Groups([SerializationGroups::TRANSLATABLE_READ_GROUP])]
    private bool $isTranslated = false;

    public function getLocale()
    {
        return $this->locale;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function setTranslations(array $translations): self
    {
        $this->translations = $translations;
        return $this;
    }

    public function getIsTranslated(): bool
    {
        return $this->isTranslated;
    }

    public function setIsTranslated(bool $isTranslated): self
    {
        $this->isTranslated = $isTranslated;
        return $this;
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document\Trait;

use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

trait TranslatableTrait
{
    const TRANSLATABLE_SERIALIZATION_READ_GROUP = 'translatable-read';

    #[Gedmo\Locale]
    private $locale;

    /**
     * array of TranslatedField objects, each containing the name of the translated field and the locale it was translated into
     */
    #[Groups([self::TRANSLATABLE_SERIALIZATION_READ_GROUP])]
    private array $translations = [];

    #[Groups([self::TRANSLATABLE_SERIALIZATION_READ_GROUP])]
    private bool $isTranslated = false;

    public function getLocale()
    {
        return $this->locale;
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

<?php

namespace FilmAnalogger\FilmAnaloggerApi\EventListener;

use Doctrine\Common\EventArgs;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\POPO\TranslatedField;
use Gedmo\Translatable\TranslatableListener as BaseTranslatableListener;

class TranslatableListener extends BaseTranslatableListener
{
    public function postLoad(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);

        /** @var \Gedmo\Translatable\Mapping\Event\TranslatableAdapter $ea */

        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());

        // Call parent to perform the actual translation
        parent::postLoad($args);

        // Check if the object was translated into a non-default locale
        if (
            isset($config['fields']) &&
            in_array(
                TranslatableTrait::class,
                array_keys(new \ReflectionClass(get_class($object))->getTraits()),
            )
        ) {
            $locale = $this->getTranslatableLocale($object, $meta, $om);
            $isTranslated = false;
            $translations = [];

            if ($locale !== $this->getDefaultLocale()) {
                // Load translations to check if any exist
                $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
                $result = $ea->loadTranslations(
                    $object,
                    $translationClass,
                    $locale,
                    $config['useObjectClass'],
                );

                foreach ($result as $translation) {
                    if (isset($translation['field'])) {
                        $translations[] = new TranslatedField(
                            field: $translation['field'],
                            locale: $locale,
                        );
                    }
                }

                $isTranslated = !empty($result);
            }

            // Set the isTranslated and translations properties if the methods exist
            if (method_exists($object, 'setIsTranslated')) {
                $object->setIsTranslated($isTranslated);
            }
            if (method_exists($object, 'setTranslations')) {
                $object->setTranslations($translations);
            }
        }
    }
}

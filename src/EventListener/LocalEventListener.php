<?php

namespace FilmAnalogger\FilmAnaloggerApi\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class LocalEventListener implements EventSubscriberInterface
{
    const LOCAL_HEADER = 'X-LOCALE';
    const ACCEPT_LANGUAGE_HEADER = 'Accept-Language';

    private $translatableListener;

    public function __construct(
        TranslatableListener $translatableListener,
        private string $defaultLocale,
        private array $availableLocales,
    ) {
        $this->translatableListener = $translatableListener;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 200]],
            KernelEvents::RESPONSE => ['setContentLanguage'],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // Persist DefaultLocale in translation table
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $localeInitialised = false;
        $request = $event->getRequest();
        if ($request->headers->has(self::LOCAL_HEADER)) {
            $locale = $request->headers->get(self::LOCAL_HEADER);
            if (in_array($locale, $this->availableLocales)) {
                $request->setLocale($locale);
            } else {
                $request->setLocale($this->defaultLocale);
            }
        } elseif ($request->headers->has(self::ACCEPT_LANGUAGE_HEADER)) {
            $acceptLanguage = $request->headers->get(self::ACCEPT_LANGUAGE_HEADER);
            // example: en-US,en;q=0.9,zh-CN;q=0.8,zh;q=0.7
            $locales = explode(',', $acceptLanguage);
            usort($locales, function ($a, $b) {
                $qualityA = 1.0;
                $qualityB = 1.0;

                if (strpos($a, ';q=') !== false) {
                    $qualityA = (float) substr($a, strpos($a, ';q=') + 3);
                    $a = trim(substr($a, 0, strpos($a, ';q=')));
                }

                if (strpos($b, ';q=') !== false) {
                    $qualityB = (float) substr($b, strpos($b, ';q=') + 3);
                    $b = trim(substr($b, 0, strpos($b, ';q=')));
                }

                return $qualityB <=> $qualityA;
            });
            $locales = array_map(fn($l) => trim(explode(';', $l)[0]), $locales);
            foreach ($locales as $locale) {
                if (in_array($locale, $this->availableLocales)) {
                    $request->setLocale($locale);
                    break;
                }
            }
        } else {
            $request->setLocale($this->defaultLocale);
        }

        // Set currentLocale
        $this->translatableListener->setTranslatableLocale($request->getLocale());
    }

    /**
     * @param ResponseEvent $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setContentLanguage(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $response->headers->add(['Content-Language' => $request->getLocale()]);
        $response->headers->add(['Accept-Language' => implode(', ', $this->availableLocales)]);
    }
}

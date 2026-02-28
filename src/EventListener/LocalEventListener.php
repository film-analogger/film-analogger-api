<?php

namespace FilmAnalogger\FilmAnaloggerApi\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class LocalEventListener implements EventSubscriberInterface
{
    const LOCAL_HEADER = 'X-LOCALE';

    private $translatableListener;
    protected $currentLocale;

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

    public function onKernelRequest(RequestEvent $event)
    {
        // Persist DefaultLocale in translation table
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);

        $request = $event->getRequest();
        if ($request->headers->has(self::LOCAL_HEADER)) {
            $locale = $request->headers->get(self::LOCAL_HEADER);
            if (in_array($locale, $this->availableLocales)) {
                $request->setLocale($locale);
            } else {
                $request->setLocale($this->defaultLocale);
            }
        } else {
            $request->setLocale($this->defaultLocale);
        }

        // Set currentLocale
        $this->translatableListener->setTranslatableLocale($request->getLocale());
        $this->currentLocale = $request->getLocale();
    }

    /**
     * @param FilterResponseEvent $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setContentLanguage(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->add(['Content-Language' => $this->currentLocale]);

        return $response;
    }
}

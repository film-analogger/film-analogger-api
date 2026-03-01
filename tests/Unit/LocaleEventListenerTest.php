<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Unit;

use FilmAnalogger\FilmAnaloggerApi\EventListener\LocaleEventListener;
use FilmAnalogger\FilmAnaloggerApi\EventListener\TranslatableListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LocaleEventListenerTest extends TestCase
{
    private MockObject|TranslatableListener $translatableListener;
    private LocaleEventListener $listener;
    private string $defaultLocale = 'en';
    private array $availableLocales = ['en', 'fr', 'de'];

    protected function setUp(): void
    {
        $this->translatableListener = $this->createMock(TranslatableListener::class);
        $this->listener = new LocaleEventListener(
            $this->translatableListener,
            $this->defaultLocale,
            $this->availableLocales,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = LocaleEventListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertEquals([['onKernelRequest', 200]], $events[KernelEvents::REQUEST]);
        $this->assertEquals(['setContentLanguage'], $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelRequestWithValidXLocaleHeader(): void
    {
        $request = new Request();
        $request->headers->set(LocaleEventListener::LOCAL_HEADER, 'fr');

        $this->translatableListener
            ->expects($this->once())
            ->method('setPersistDefaultLocaleTranslation')
            ->with(true);

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with('fr');

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithInvalidXLocaleHeaderFallsBackToDefault(): void
    {
        $request = new Request();
        $request->headers->set(LocaleEventListener::LOCAL_HEADER, 'es');

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with($this->defaultLocale);

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals($this->defaultLocale, $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageHeader(): void
    {
        $request = new Request();
        $request->headers->set(
            LocaleEventListener::ACCEPT_LANGUAGE_HEADER,
            'fr-FR,fr;q=0.9,en;q=0.8',
        );

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with('fr');

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageHeaderPicksHighestQuality(): void
    {
        $request = new Request();
        $request->headers->set(
            LocaleEventListener::ACCEPT_LANGUAGE_HEADER,
            'de;q=0.7,fr;q=0.9,en;q=0.8',
        );

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with('fr');

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageHeaderNoMatchFallsBackToDefault(): void
    {
        $request = new Request();
        $request->headers->set(LocaleEventListener::ACCEPT_LANGUAGE_HEADER, 'es;q=0.9,it;q=0.8');

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with($this->defaultLocale);

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals($this->defaultLocale, $request->getLocale());
    }

    public function testOnKernelRequestWithNoHeadersFallsBackToDefault(): void
    {
        $request = new Request();

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with($this->defaultLocale);

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals($this->defaultLocale, $request->getLocale());
    }

    public function testOnKernelRequestSetsPersistDefaultLocaleTranslation(): void
    {
        $request = new Request();

        $this->translatableListener
            ->expects($this->once())
            ->method('setPersistDefaultLocaleTranslation')
            ->with(true);

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);
    }

    public function testSetContentLanguageSetsResponseHeaders(): void
    {
        $request = new Request();
        $request->setLocale('fr');
        $response = new Response();

        $event = $this->createResponseEvent($request, $response);
        $this->listener->setContentLanguage($event);

        $this->assertEquals('fr', $response->headers->get('Content-Language'));
        $this->assertEquals(
            implode(', ', $this->availableLocales),
            $response->headers->get('Accept-Language'),
        );
    }

    public function testSetContentLanguageWithDefaultLocale(): void
    {
        $request = new Request();
        $response = new Response();

        $event = $this->createResponseEvent($request, $response);
        $this->listener->setContentLanguage($event);

        $this->assertEquals($this->defaultLocale, $response->headers->get('Content-Language'));
        $this->assertEquals('en, fr, de', $response->headers->get('Accept-Language'));
    }

    public function testOnKernelRequestWithAcceptLanguageHeaderWithSpaces(): void
    {
        $request = new Request();
        $request->headers->set(
            LocaleEventListener::ACCEPT_LANGUAGE_HEADER,
            'en-US, fr ; q=0.9 , de ; q=0.7',
        );

        $this->translatableListener->expects($this->once())->method('setTranslatableLocale');

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestXLocaleHeaderTakesPrecedenceOverAcceptLanguage(): void
    {
        $request = new Request();
        $request->headers->set(LocaleEventListener::LOCAL_HEADER, 'de');
        $request->headers->set(LocaleEventListener::ACCEPT_LANGUAGE_HEADER, 'fr;q=0.9,en;q=0.8');

        $this->translatableListener
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with('de');

        $event = $this->createRequestEvent($request);
        $this->listener->onKernelRequest($event);

        $this->assertEquals('de', $request->getLocale());
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}

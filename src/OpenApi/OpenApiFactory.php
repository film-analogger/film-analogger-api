<?php

namespace FilmAnalogger\FilmAnaloggerApi\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use FilmAnalogger\FilmAnaloggerApi\EventListener\LocaleEventListener;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $inner) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->inner->__invoke($context);

        $this->addLocaleHeader($openApi);

        return $openApi;
    }

    /**
     * Add X-LOCALE header parameter to every path in the OpenAPI specification.
     */
    private function addLocaleHeader(OpenApi $openApi): void
    {
        $xLocaleHeader = new Parameter(
            name: LocaleEventListener::LOCAL_HEADER,
            in: 'header',
            description: 'Locale (e.g. "en", "fr")',
            schema: ['type' => 'string'],
        );

        $acceptLanguageHeader = new Parameter(
            name: LocaleEventListener::ACCEPT_LANGUAGE_HEADER,
            in: 'header',
            description: 'Accept-Language (e.g. "en", "fr", "en-US,en;q=0.9,fr;q=0.8") - used as a fallback if X-LOCALE is not set, the first language in the list that matches an available locale will be used',
            schema: ['type' => 'string'],
        );

        foreach ($openApi->getPaths()->getPaths() as $pathId => $pathItem) {
            /** @var PathItem $pathItem */
            $params = $pathItem->getParameters();
            $params = array_filter(
                $params ?? [],
                fn(Parameter $p) => !in_array($p->getName(), [
                    LocaleEventListener::LOCAL_HEADER,
                    LocaleEventListener::ACCEPT_LANGUAGE_HEADER,
                ]),
            );
            $params[] = $xLocaleHeader;
            $params[] = $acceptLanguageHeader;
            $openApi->getPaths()->addPath($pathId, $pathItem->withParameters($params));
        }
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use FilmAnalogger\FilmAnaloggerApi\EventListener\LocalEventListener;
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
        $localeHeader = new Parameter(
            name: LocalEventListener::LOCAL_HEADER,
            in: 'header',
            description: 'Locale (e.g. "en", "fr")',
            schema: ['type' => 'string'],
        );

        foreach ($openApi->getPaths()->getPaths() as $pathId => $pathItem) {
            /** @var PathItem $pathItem */
            $params = $pathItem->getParameters();
            $params[] = $localeHeader;

            $openApi->getPaths()->addPath($pathId, $pathItem->withParameters($params));
        }
    }
}

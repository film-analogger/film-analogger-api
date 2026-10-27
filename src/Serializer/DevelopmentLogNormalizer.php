<?php

namespace FilmAnalogger\FilmAnaloggerApi\Serializer;

use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use Vich\UploaderBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DevelopmentLogNormalizer implements NormalizerInterface
{
    private const ALREADY_CALLED = 'DEVELOPMENT_LOG_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        #[
            Autowire(service: 'api_platform.jsonld.normalizer.item'),
        ]
        private readonly NormalizerInterface $normalizer,
        private readonly StorageInterface $storage,
    ) {}

    public function normalize(
        $object,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {
        $context[self::ALREADY_CALLED] = true;

        $object->contactSheetPhotoUrl = $this->storage->resolveUri($object, 'contactSheetPhotoFile');

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof DevelopmentLog;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DevelopmentLog::class => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Handler\UploadHandler;

final readonly class MaskingSchemaPhotoUploadProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadHandler $uploadHandler,
        private DocumentManager $documentManager,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): mixed {
        if (!$data instanceof PrintWork) {
            $this->logger->error('MaskingSchemaPhotoUploadProcessor received unsupported data type', [
                'data_type' => get_debug_type($data),
            ]);
            return $data;
        }

        $request = $this->requestStack->getCurrentRequest();
        $maskingSchemaPhotoFile = $request?->files->get('maskingSchemaPhotoFile');

        if (!$maskingSchemaPhotoFile instanceof UploadedFile) {
            throw new InvalidArgumentException('No valid masking schema photo file uploaded.');
        }

        $data->setMaskingSchemaPhotoFile($maskingSchemaPhotoFile);
        $this->uploadHandler->upload($data, 'maskingSchemaPhotoFile');
        $this->documentManager->flush();

        return $data;
    }
}

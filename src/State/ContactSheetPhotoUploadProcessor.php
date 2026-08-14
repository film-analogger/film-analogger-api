<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Handler\UploadHandler;

final readonly class ContactSheetPhotoUploadProcessor implements ProcessorInterface
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
        if (!$data instanceof DevelopmentLog) {
            $this->logger->error('ContactSheetPhotoUploadProcessor received unsupported data type', [
                'data_type' => get_debug_type($data),
            ]);
            return $data;
        }

        $request = $this->requestStack->getCurrentRequest();
        $contactSheetPhotoFile = $request?->files->get('contactSheetPhotoFile');

        if (!$contactSheetPhotoFile instanceof UploadedFile) {
            throw new InvalidArgumentException('No valid contact sheet photo file uploaded.');
        }

        $data->setContactSheetPhotoFile($contactSheetPhotoFile);
        $this->uploadHandler->upload($data, 'contactSheetPhotoFile');
        $this->documentManager->flush();

        return $data;
    }
}

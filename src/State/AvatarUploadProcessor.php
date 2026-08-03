<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Handler\UploadHandler;

final readonly class AvatarUploadProcessor implements ProcessorInterface
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
        if (!$data instanceof AppUser) {
            $this->logger->error('AvatarUploadProcessor received unsupported data type', [
                'data_type' => get_debug_type($data),
            ]);
            return $data;
        }

        $request = $this->requestStack->getCurrentRequest();
        $avatarFile = $request?->files->get('avatarFile');

        if (!$avatarFile instanceof UploadedFile) {
            throw new InvalidArgumentException('No valid avatar file uploaded.');
        }

        $data->setAvatarFile($avatarFile);
        $this->uploadHandler->upload($data, 'avatarFile');
        $this->documentManager->flush();

        return $data;
    }
}

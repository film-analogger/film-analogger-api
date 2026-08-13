<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;
use FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private DocumentManager $documentManager,
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): AppUser {
        $user = $this->security->getUser();

        /** @var AppUserRepository $repository */
        $repository = $this->documentManager->getRepository(AppUser::class);
        $appUser = $user ? $repository->findOneByUsername($user->getUserIdentifier()) : null;

        if (!$appUser instanceof AppUser) {
            throw new NotFoundHttpException('Current user could not be found.');
        }

        return $appUser;
    }
}

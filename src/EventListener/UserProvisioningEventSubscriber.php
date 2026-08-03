<?php

namespace FilmAnalogger\FilmAnaloggerApi\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;
use FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\User\KeycloakBearerUser;
use MongoDB\Driver\Exception\BulkWriteException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class UserProvisioningEventSubscriber implements EventSubscriberInterface
{
    private const MONGODB_DUPLICATE_KEY_ERROR_CODE = 11000;

    public function __construct(private readonly DocumentManager $documentManager) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => [['provisionUser', 10]],
        ];
    }

    public function provisionUser(LoginSuccessEvent $event): void
    {
        $keycloakUser = $event->getUser();
        if (!$keycloakUser instanceof KeycloakBearerUser) {
            return;
        }

        /** @var AppUserRepository $repository */
        $repository = $this->documentManager->getRepository(AppUser::class);

        if ($repository->findOneBySub($keycloakUser->getSub()) !== null) {
            return;
        }

        $appUser = new AppUser();
        $appUser->keycloakSub = $keycloakUser->getSub();
        $appUser->username = $keycloakUser->getPreferredUsername();
        $appUser->email = $keycloakUser->getEmail();
        $appUser->name = $keycloakUser->getName();
        $appUser->givenName = $keycloakUser->getGivenName();
        $appUser->familyName = $keycloakUser->getFamilyName();

        $this->documentManager->persist($appUser);

        try {
            $this->documentManager->flush();
        } catch (BulkWriteException $e) {
            if ($e->getCode() !== self::MONGODB_DUPLICATE_KEY_ERROR_CODE) {
                throw $e;
            }

            // A concurrent request already provisioned this user (race on the
            // unique keycloakSub/username/email indexes) — nothing left to do.
            $this->documentManager->clear(AppUser::class);
        }
    }
}

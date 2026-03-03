<?php
namespace FilmAnalogger\FilmAnaloggerApi\EventListener;

use Gedmo\Blameable\BlameableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class GedmoExtensionsEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BlameableListener $blameableListener,
        private ?AuthorizationCheckerInterface $authorizationChecker = null,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['configureBlameableListener']],
        ];
    }

    /**
     * Configures the blameable listener using the currently authenticated user
     */
    public function configureBlameableListener(RequestEvent $event): void
    {
        // Only applies to the main request
        if (!$event->isMainRequest()) {
            return;
        }

        // If the required security component services weren't provided, there's nothing we can do
        if (null === $this->authorizationChecker || null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        // Only set the user information if there is a token in storage and it represents an authenticated user
        if (null !== $token && $this->authorizationChecker->isGranted('IS_AUTHENTICATED')) {
            $this->blameableListener->setUserValue($token->getUser());
        }
    }
}

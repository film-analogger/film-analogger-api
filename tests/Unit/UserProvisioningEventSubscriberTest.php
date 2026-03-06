<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Unit;

use Doctrine\ODM\MongoDB\DocumentManager;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;
use FilmAnalogger\FilmAnaloggerApi\EventListener\UserProvisioningEventSubscriber;
use FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\User\KeycloakBearerUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class UserProvisioningEventSubscriberTest extends TestCase
{
    private MockObject|DocumentManager $documentManager;
    private MockObject|AppUserRepository $repository;
    private UserProvisioningEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->repository = $this->createMock(AppUserRepository::class);

        $this->subscriber = new UserProvisioningEventSubscriber($this->documentManager);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [LoginSuccessEvent::class => [['provisionUser', 10]]],
            UserProvisioningEventSubscriber::getSubscribedEvents(),
        );
    }

    public function testProvisionUserReturnsEarlyWhenUserIsNotKeycloakBearerUser(): void
    {
        $event = $this->createMock(LoginSuccessEvent::class);
        $event
            ->method('getUser')
            ->willReturn(
                new \Symfony\Component\Security\Core\User\InMemoryUser('test-user', 'password'),
            );

        $this->documentManager->expects($this->never())->method('getRepository');

        $this->documentManager->expects($this->never())->method('persist');

        $this->documentManager->expects($this->never())->method('flush');

        $this->subscriber->provisionUser($event);
    }

    public function testProvisionUserDoesNothingWhenUserAlreadyExists(): void
    {
        $keycloakUser = $this->createMock(KeycloakBearerUser::class);
        $keycloakUser->method('getSub')->willReturn('sub-123');

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($keycloakUser);

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(AppUser::class)
            ->willReturn($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('findOneBySub')
            ->with('sub-123')
            ->willReturn(new AppUser());

        $this->documentManager->expects($this->never())->method('persist');

        $this->documentManager->expects($this->never())->method('flush');

        $this->subscriber->provisionUser($event);
    }

    public function testProvisionUserPersistsMappedAppUserWhenNotExisting(): void
    {
        $keycloakUser = $this->createMock(KeycloakBearerUser::class);
        $keycloakUser->method('getSub')->willReturn('sub-456');
        $keycloakUser->method('getPreferredUsername')->willReturn('tguerin');
        $keycloakUser->method('getEmail')->willReturn('tguerin@example.com');
        $keycloakUser->method('getName')->willReturn('Théo Guerin');
        $keycloakUser->method('getGivenName')->willReturn('Théo');
        $keycloakUser->method('getFamilyName')->willReturn('Guerin');

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($keycloakUser);

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(AppUser::class)
            ->willReturn($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('findOneBySub')
            ->with('sub-456')
            ->willReturn(null);

        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(function (AppUser $appUser): bool {
                    self::assertSame('sub-456', $appUser->keycloakSub);
                    self::assertSame('tguerin', $appUser->username);
                    self::assertSame('tguerin@example.com', $appUser->email);
                    self::assertSame('Théo Guerin', $appUser->name);
                    self::assertSame('Théo', $appUser->givenName);
                    self::assertSame('Guerin', $appUser->familyName);

                    return true;
                }),
            );

        $this->documentManager->expects($this->once())->method('flush');

        $this->subscriber->provisionUser($event);
    }
}

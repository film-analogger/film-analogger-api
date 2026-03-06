<?php

declare(strict_types=1);

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\UserApi;

use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;
use FilmAnalogger\FilmAnaloggerApi\EventListener\UserProvisioningEventSubscriber;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Security\Mock\KeycloakBearerUserMock;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class UserApiTest extends AbstractFilmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->documentManager->getDocumentCollection(AppUser::class)->drop();
    }

    private function createAppUser(
        string $sub = 'sub-123',
        string $username = 'testuser',
        string $email = 'testuser@example.test',
        string $name = 'Test User',
        string $givenName = 'Test',
        string $familyName = 'User',
    ): AppUser {
        $user = new AppUser();
        $user->keycloakSub = $sub;
        $user->username = $username;
        $user->email = $email;
        $user->name = $name;
        $user->givenName = $givenName;
        $user->familyName = $familyName;
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        return $user;
    }

    // -------------------------------------------------------------------------
    // UserProvisioningEventSubscriber integration tests
    // -------------------------------------------------------------------------

    public function testSubscriberProvisionesNewUserOnLoginEvent(): void
    {
        $subscriber = static::getContainer()->get(UserProvisioningEventSubscriber::class);

        $keycloakUser = new KeycloakBearerUserMock(
            KeycloakRoles::ALL_ROLES,
            'unique-sub-abc',
            'Théo Guerin',
            'theo@example.test',
            'Théo',
            'Guerin',
            'theo.guerin',
        );

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($keycloakUser);

        $subscriber->provisionUser($event);

        $this->documentManager->clear();

        /** @var \FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository $repository */
        $repository = $this->documentManager->getRepository(AppUser::class);
        $appUser = $repository->findOneBySub('unique-sub-abc');

        $this->assertNotNull($appUser);
        $this->assertSame('unique-sub-abc', $appUser->keycloakSub);
        $this->assertSame('theo.guerin', $appUser->username);
        $this->assertSame('theo@example.test', $appUser->email);
        $this->assertSame('Théo Guerin', $appUser->name);
        $this->assertSame('Théo', $appUser->givenName);
        $this->assertSame('Guerin', $appUser->familyName);
    }

    public function testSubscriberDoesNotDuplicateExistingUser(): void
    {
        $this->createAppUser(sub: 'sub-already-exists', username: 'existing');

        $subscriber = static::getContainer()->get(UserProvisioningEventSubscriber::class);

        $keycloakUser = new KeycloakBearerUserMock(
            KeycloakRoles::ALL_ROLES,
            'sub-already-exists',
            'Existing User',
            'existing@example.test',
            'Existing',
            'User',
            'existing',
        );

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($keycloakUser);

        $subscriber->provisionUser($event);

        $this->documentManager->clear();

        $collection = $this->documentManager->getDocumentCollection(AppUser::class);
        $this->assertSame(1, $collection->countDocuments());
    }

    public function testSubscriberIgnoresNonKeycloakUser(): void
    {
        $subscriber = static::getContainer()->get(UserProvisioningEventSubscriber::class);

        $plainUser = new \Symfony\Component\Security\Core\User\InMemoryUser(
            'plainuser',
            'password',
        );

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($plainUser);

        $subscriber->provisionUser($event);

        $collection = $this->documentManager->getDocumentCollection(AppUser::class);
        $this->assertSame(0, $collection->countDocuments());
    }

    // -------------------------------------------------------------------------
    // Security tests
    // -------------------------------------------------------------------------

    public function testUnauthenticatedRequestsReturn401(): void
    {
        $user = $this->createAppUser();
        $client = static::createClient();

        foreach (
            [
                ['GET', '/app_users'],
                ['GET', '/app_users/' . $user->getId()],
                ['PATCH', '/app_users/' . $user->getId()],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testUserWithoutDataReaderRoleGetsForbidden(): void
    {
        $user = $this->createAppUser();
        $client = self::loggedClientWithUserAndRoles([]);

        $this->assertForbiddenAccessDenied($client, 'GET', '/app_users');
        $this->assertForbiddenAccessDenied($client, 'GET', '/app_users/' . $user->getId());
    }

    // -------------------------------------------------------------------------
    // GET collection
    // -------------------------------------------------------------------------

    public function testGetCollectionReturnsAllUsers(): void
    {
        $this->createAppUser(sub: 'sub-1', username: 'user1', email: 'user1@example.test');
        $this->createAppUser(sub: 'sub-2', username: 'user2', email: 'user2@example.test');

        $client = self::loggedClientAdmin();
        $client->request('GET', '/app_users');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // 3 users in total: the 2 created above + the one created by the UserProvisioningEventSubscriber

        $this->assertJsonContains([
            '@context' => '/contexts/AppUser',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
        ]);
    }

    public function testGetCollectionReturnsEmptyWhenNoUsers(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/app_users');

        $this->assertResponseIsSuccessful();

        // 1 user beacause of the UserProvisioningEventSubscriber that creates a user for the current test client

        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);
    }

    public function testDataReaderCanGetCollection(): void
    {
        $this->createAppUser();

        $client = self::loggedClientDataReader();
        $client->request('GET', '/app_users');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    // -------------------------------------------------------------------------
    // GET single
    // -------------------------------------------------------------------------

    public function testGetSingleUser(): void
    {
        $user = $this->createAppUser(username: 'johndoe', email: 'john@example.test');

        $client = self::loggedClientAdmin();
        $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'username' => 'johndoe',
        ]);
    }

    public function testGetNonExistentUserReturns404(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/app_users/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // PATCH
    // -------------------------------------------------------------------------

    public function testOwnerCanPatchOwnProfile(): void
    {
        $user = $this->createAppUser(username: 'test_user_data_reader');

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $client->request('PATCH', '/app_users/' . $user->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'website' => 'https://example.com',
                'description' => 'My bio',
                'avatarUrl' => 'https://example.com/avatar.png',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'website' => 'https://example.com',
            'description' => 'My bio',
            'avatarUrl' => 'https://example.com/avatar.png',
        ]);
    }

    public function testOwnerCannotPatchAnotherUsersProfile(): void
    {
        $otherUser = $this->createAppUser(
            sub: 'sub-other',
            username: 'other_user',
            email: 'other@example.test',
        );

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $this->assertForbiddenAccessDenied($client, 'PATCH', '/app_users/' . $otherUser->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['description' => 'Hijacked bio'],
        ]);
    }

    public function testPatchWithInvalidWebsiteUrlFails(): void
    {
        $user = $this->createAppUser(username: 'test_user_data_reader');

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $client->request('PATCH', '/app_users/' . $user->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['website' => 'not-a-valid-url'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPatchWithInvalidAvatarUrlFails(): void
    {
        $user = $this->createAppUser(username: 'test_user_data_reader');

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $client->request('PATCH', '/app_users/' . $user->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['avatarUrl' => 'not-a-valid-url'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    // -------------------------------------------------------------------------
    // Field-level security
    // -------------------------------------------------------------------------

    public function testKeycloakSubVisibleToAdmin(): void
    {
        $user = $this->createAppUser(sub: 'the-sub-value');

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('keycloakSub', $response->toArray());
        $this->assertSame('the-sub-value', $response->toArray()['keycloakSub']);
    }

    public function testKeycloakSubHiddenFromNonAdmin(): void
    {
        $user = $this->createAppUser(sub: 'the-sub-value');

        $client = self::loggedClientDataReader();
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('keycloakSub', $response->toArray());
    }

    public function testSensitiveFieldsVisibleToAdmin(): void
    {
        $user = $this->createAppUser(
            username: 'privateuser',
            email: 'private@example.test',
            name: 'Private User',
            givenName: 'Private',
            familyName: 'User',
        );

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('givenName', $data);
        $this->assertArrayHasKey('familyName', $data);
    }

    public function testSensitiveFieldsVisibleToOwner(): void
    {
        $user = $this->createAppUser(
            username: 'test_user_data_reader',
            email: 'owner@example.test',
            name: 'Owner User',
            givenName: 'Owner',
            familyName: 'User',
        );

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('givenName', $data);
        $this->assertArrayHasKey('familyName', $data);
    }

    public function testSensitiveFieldsHiddenFromOtherUsers(): void
    {
        $user = $this->createAppUser(
            sub: 'sub-private',
            username: 'privateuser',
            email: 'private@example.test',
            name: 'Private User',
            givenName: 'Private',
            familyName: 'User',
        );

        $client = self::loggedClientDataReader(preferred_username: 'test_user_data_reader');
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayNotHasKey('givenName', $data);
        $this->assertArrayNotHasKey('familyName', $data);
    }

    public function testPublicFieldsAlwaysVisible(): void
    {
        $user = $this->createAppUser(username: 'publicuser', email: 'public@example.test');
        $user->website = 'https://example.com';
        $user->description = 'Public bio';
        $user->avatarUrl = 'https://example.com/avatar.png';
        $this->documentManager->flush();

        $client = self::loggedClientDataReader();
        $response = $client->request('GET', '/app_users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('website', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('avatarUrl', $data);
    }
}

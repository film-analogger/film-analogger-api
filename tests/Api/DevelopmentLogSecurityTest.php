<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class DevelopmentLogSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $developmentLog = $this->createDevelopmentLog();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/development_logs'],
                ['GET', '/development_logs/' . $developmentLog->getId()],
                ['PATCH', '/development_logs/' . $developmentLog->getId()],
                ['DELETE', '/development_logs/' . $developmentLog->getId()],
                ['POST', '/development_logs'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderWithoutUserRoleCannotAccess(): void
    {
        // Owned by the data-reader itself, so the ownership filter isn't what denies access here:
        // it's the missing ROLE_user that must trigger the 403.
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'test_user_data_reader']);

        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'GET', '/development_logs');
        $this->assertForbiddenAccessDenied(
            $client,
            'GET',
            '/development_logs/' . $developmentLog->getId(),
        );
    }

    public function testUserOnlySeesOwnLogsInCollection(): void
    {
        $this->createDevelopmentLog(['createdBy' => 'test_user_user']);
        $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientUser();
        $response = $client->request('GET', '/development_logs');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['hydra:totalItems']);
    }

    public function testUserCannotGetSomeoneElsesLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientUser();
        $client->request('GET', '/development_logs/' . $developmentLog->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotPatchSomeoneElsesLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientUser();
        $client->request('PATCH', '/development_logs/' . $developmentLog->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['rating' => 1],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotDeleteSomeoneElsesLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientUser();
        $client->request('DELETE', '/development_logs/' . $developmentLog->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCanManageTheirOwnLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'test_user_user']);

        $client = self::loggedClientUser();

        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/development_logs/' . $developmentLog->getId(),
            200,
        );
    }

    public function testAdminSeesEveryonesLogs(): void
    {
        $this->createDevelopmentLog(['createdBy' => 'test_user_admin']);
        $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/development_logs');

        $this->assertResponseIsSuccessful();
        $this->assertSame(2, $response->toArray()['hydra:totalItems']);
    }

    public function testAdminCanGetSomeoneElsesLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'someone_else']);

        $client = self::loggedClientAdmin();

        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/development_logs/' . $developmentLog->getId(),
            200,
        );
    }
}

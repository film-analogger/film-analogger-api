<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use ApiPlatform\Symfony\Bundle\Test\Client;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PrintWorkSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $print = $this->createPrintWork();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/prints'],
                ['GET', '/prints/' . $print->getId()],
                ['PATCH', '/prints/' . $print->getId()],
                ['DELETE', '/prints/' . $print->getId()],
                ['POST', '/prints'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    private function printPayload(string $sessionIri, int $number = 1): array
    {
        return [
            'session' => $sessionIri,
            'number' => $number,
            'paperBrand' => 'ilford',
            'paperBase' => 'rc',
            'paperSurface' => 'glossy',
            'paperWidthCm' => 18,
            'paperHeightCm' => 24,
            'exposures' => [['order' => 1, 'kind' => 'base', 'baseSeconds' => 32, 'grade' => '2']],
        ];
    }

    /**
     * Creates a session AND a print as 'owner_writer', both via authenticated
     * HTTP requests (not the raw factories) so Blameable actually stamps
     * createdBy — required for the ownership checks under test. Pass an
     * existing client to reuse the same authenticated session: creating a
     * fresh client via loggedClientDataWriter() each time would silently
     * become the "current" client the parameterless assert*() helpers read
     * from, breaking assertions made against an earlier client instance.
     */
    private function createPrintAsOwner(?Client $client = null, int $number = 1): array
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER');
        $stopBath = $this->createPaperChemistry('STOP');
        $fixer = $this->createPaperChemistry('FIXER');

        $ownerClient = $client ?? self::loggedClientDataWriter(preferred_username: 'owner_writer');

        $sessionResponse = $ownerClient->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'date' => '2026-01-15',
                'lab' => 'Garage',
                'number' => $number,
                'enlarger' => 'Durst M805',
                'temperatureCelsius' => 20.0,
                'chemicalBaths' => [
                    ['chemistry' => '/chemistries/' . $developer->getId(), 'durationSeconds' => 60],
                    ['chemistry' => '/chemistries/' . $stopBath->getId(), 'durationSeconds' => 30],
                    ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $sessionId = $sessionResponse->toArray()['id'];

        $printResponse = $ownerClient->request('POST', '/prints', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->printPayload('/print_sessions/' . $sessionId, $number),
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [$ownerClient, $printResponse->toArray()['id'], $sessionId];
    }

    public function testOwnerCanAccessOwnPrint(): void
    {
        [$ownerClient, $id] = $this->createPrintAsOwner();

        $ownerClient->request('GET', '/prints/' . $id);
        $this->assertResponseIsSuccessful();

        $ownerClient->request('PATCH', '/prints/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['notes' => 'ok'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOtherDataWriterCannotAccessSomeoneElsesPrint(): void
    {
        [, $id] = $this->createPrintAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');

        $this->assertForbiddenAccessDenied($otherClient, 'GET', '/prints/' . $id);
        $this->assertForbiddenAccessDenied($otherClient, 'DELETE', '/prints/' . $id);
    }

    public function testAdminCanAccessAnyPrint(): void
    {
        [, $id] = $this->createPrintAsOwner();

        $admin = self::loggedClientAdmin();
        $admin->request('GET', '/prints/' . $id);
        $this->assertResponseIsSuccessful();
    }

    public function testCannotCreatePrintOnSomeoneElsesSession(): void
    {
        [, , $sessionId] = $this->createPrintAsOwner();

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('POST', '/prints', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->printPayload('/print_sessions/' . $sessionId, 2),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetCollectionIsScopedToOwner(): void
    {
        $ownerClient = self::loggedClientDataWriter(preferred_username: 'owner_writer');
        $this->createPrintAsOwner($ownerClient, 1);
        $this->createPrintAsOwner($ownerClient, 2);

        $ownerClient->request('GET', '/prints');
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/prints');
        $this->assertJsonContains(['hydra:totalItems' => 0]);
    }

    public function testSubResourceIsScopedToOwner(): void
    {
        [$ownerClient, , $sessionId] = $this->createPrintAsOwner();

        $ownerClient->request('GET', '/print_sessions/' . $sessionId . '/prints');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $otherClient = self::loggedClientDataWriter(preferred_username: 'other_writer');
        $otherClient->request('GET', '/print_sessions/' . $sessionId . '/prints');
        $this->assertJsonContains(['hydra:totalItems' => 0]);
    }
}

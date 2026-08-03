<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PrintSessionTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createPrintSession();
        $this->createPrintSession(['number' => 2]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PrintSession',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetPrintSession(): void
    {
        $session = $this->createPrintSession(['lab' => 'Garage', 'enlarger' => 'Durst M805']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions/' . $session->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'lab' => 'Garage',
            'enlarger' => 'Durst M805',
        ]);
    }

    private function baseSessionPayload(array $chemicalBaths, array $overrides = []): array
    {
        return array_merge(
            [
                'date' => '2026-01-15',
                'lab' => 'Garage',
                'number' => 1,
                'enlarger' => 'Durst M805',
                'temperatureCelsius' => 20.5,
                'chemicalBaths' => $chemicalBaths,
            ],
            $overrides,
        );
    }

    public function testCreatePrintSession(): void
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER');
        $stopBath = $this->createPaperChemistry('STOP');
        $fixer = $this->createPaperChemistry('FIXER');

        $client = self::loggedClientAdmin();
        $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->baseSessionPayload([
                ['chemistry' => '/chemistries/' . $developer->getId(), 'durationSeconds' => 60],
                ['chemistry' => '/chemistries/' . $stopBath->getId(), 'durationSeconds' => 30],
                ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
            ]),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'lab' => 'Garage',
            'enlarger' => 'Durst M805',
            'temperatureCelsius' => 20.5,
        ]);
    }

    public function testCreatePrintSessionWithVariableChain(): void
    {
        // The whole point of the dynamic chain: a lab can run a two-bath
        // process (developer + fixer, no stop bath) without the API
        // second-guessing which roles must be present.
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER');
        $fixer = $this->createPaperChemistry('FIXER');

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->baseSessionPayload([
                ['chemistry' => '/chemistries/' . $developer->getId(), 'durationSeconds' => 60],
                ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
            ]),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, $response->toArray()['chemicalBaths']);
    }

    public function testCreatePrintSessionWithDilutionOverride(): void
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER', ['waterParts' => 9]);
        $stopBath = $this->createPaperChemistry('STOP');
        $fixer = $this->createPaperChemistry('FIXER');

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->baseSessionPayload([
                [
                    'chemistry' => '/chemistries/' . $developer->getId(),
                    'durationSeconds' => 60,
                    'dilutionOverride' => '1+7',
                ],
                ['chemistry' => '/chemistries/' . $stopBath->getId(), 'durationSeconds' => 30],
                ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
            ]),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('1+7', $data['chemicalBaths'][0]['effectiveDilution']);
        $this->assertSame('1+9', $data['chemicalBaths'][1]['effectiveDilution']);
    }

    public function testCreatePrintSessionWithoutEffectiveDilutionOverrideUsesCatalogDefault(): void
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER', ['waterParts' => 9]);
        $stopBath = $this->createPaperChemistry('STOP');
        $fixer = $this->createPaperChemistry('FIXER');

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->baseSessionPayload([
                ['chemistry' => '/chemistries/' . $developer->getId(), 'durationSeconds' => 60],
                ['chemistry' => '/chemistries/' . $stopBath->getId(), 'durationSeconds' => 30],
                ['chemistry' => '/chemistries/' . $fixer->getId(), 'durationSeconds' => 300],
            ]),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('1+9', $data['chemicalBaths'][0]['effectiveDilution']);
    }

    public function testCreatePrintSessionWithEmptyChainFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $this->baseSessionPayload([]),
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdatePrintSession(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/print_sessions/' . $session->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['lab' => 'Salle de bain'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['lab' => 'Salle de bain']);
    }

    public function testDeletePrintSession(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/print_sessions/' . $session->getId());
        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions/' . $session->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentPrintSessionReturns404(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testFilterByDate(): void
    {
        $this->createPrintSession(['date' => new \DateTimeImmutable('2026-01-10')]);
        $this->createPrintSession(['date' => new \DateTimeImmutable('2026-01-20'), 'number' => 2]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions', ['query' => ['date[after]' => '2026-01-15']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testPrintsSubResource(): void
    {
        $session = $this->createPrintSession();
        $this->createPrintWork(['session' => $session, 'number' => 1]);
        $this->createPrintWork(['session' => $session, 'number' => 2]);
        $otherSession = $this->createPrintSession(['number' => 2]);
        $this->createPrintWork(['session' => $otherSession, 'number' => 1]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions/' . $session->getId() . '/prints');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }
}

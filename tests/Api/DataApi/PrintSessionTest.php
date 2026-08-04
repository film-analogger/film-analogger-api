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
        $enlarger = $this->createEnlarger(['name' => 'M805']);
        $session = $this->createPrintSession(['lab' => 'Garage', 'enlarger' => $enlarger]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/print_sessions/' . $session->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['lab' => 'Garage']);
        $this->assertSame(
            '/enlargers/' . $enlarger->getId(),
            $response->toArray()['enlarger']['@id'],
        );
    }

    public function testGetPrintSessionDeepEmbedsRelatedCatalogEntries(): void
    {
        $enlargerManufacturer = $this->createManufacturer('Durst');
        $enlarger = $this->createEnlarger([
            'name' => 'M805',
            'manufacturer' => $enlargerManufacturer,
        ]);
        $paperManufacturer = $this->createManufacturer('Ilford');
        $photoPaper = $this->createPhotoPaper([
            'name' => 'Multigrade RC Pearl',
            'manufacturer' => $paperManufacturer,
        ]);
        $chemistryManufacturer = $this->createManufacturer('Kodak');
        $chemistry = $this->createPaperChemistry('BW_PAPER_DEVELOPER', [
            'manufacturer' => $chemistryManufacturer,
        ]);

        $client = self::loggedClientAdmin();
        $sessionResponse = $client->request('POST', '/print_sessions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'date' => '2026-01-15',
                'lab' => 'Garage',
                'number' => 1,
                'enlarger' => '/enlargers/' . $enlarger->getId(),
                'temperatureCelsius' => 20.0,
                'chemicalBaths' => [
                    ['chemistry' => '/chemistries/' . $chemistry->getId(), 'durationSeconds' => 60],
                ],
            ],
        ]);
        $sessionId = $sessionResponse->toArray()['id'];

        $client->request('POST', '/prints', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'session' => '/print_sessions/' . $sessionId,
                'number' => 1,
                'photoPaper' => '/photo_papers/' . $photoPaper->getId(),
                'paperWidthCm' => 18,
                'paperHeightCm' => 24,
                'exposures' => [
                    ['order' => 1, 'kind' => 'base', 'baseSeconds' => 32, 'grade' => '2'],
                ],
            ],
        ]);

        $response = $client->request('GET', '/print_sessions/' . $sessionId);
        $data = $response->toArray();

        $this->assertSame('M805', $data['enlarger']['name']);
        $this->assertSame('Durst', $data['enlarger']['manufacturer']['name']);
        $this->assertSame('Kodak', $data['chemicalBaths'][0]['chemistry']['manufacturer']['name']);
        $this->assertSame('Multigrade RC Pearl', $data['prints'][0]['photoPaper']['name']);
        $this->assertSame('Ilford', $data['prints'][0]['photoPaper']['manufacturer']['name']);
    }

    public function testGetCollectionDoesNotDeepEmbedRelatedCatalogEntries(): void
    {
        $enlarger = $this->createEnlarger(['manufacturer' => $this->createManufacturer('Durst')]);
        $this->createPrintSession(['enlarger' => $enlarger]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/print_sessions');
        $data = $response->toArray()['hydra:member'][0];

        $this->assertArrayNotHasKey('name', $data['enlarger']);
        $this->assertArrayNotHasKey('prints', $data);
    }

    private function baseSessionPayload(array $chemicalBaths, array $overrides = []): array
    {
        return array_merge(
            [
                'date' => '2026-01-15',
                'lab' => 'Garage',
                'number' => 1,
                'enlarger' => '/enlargers/' . $this->createEnlarger()->getId(),
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

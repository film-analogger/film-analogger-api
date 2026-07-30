<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PrintWorkTest extends AbstractFilmTestCase
{
    private function basePrintPayload(string $sessionIri, array $overrides = []): array
    {
        return array_merge(
            [
                'session' => $sessionIri,
                'number' => 1,
                'paperBrand' => 'ilford',
                'paperBase' => 'rc',
                'paperSurface' => 'glossy',
                'paperWidthCm' => 18,
                'paperHeightCm' => 24,
                'exposures' => [
                    [
                        'order' => 1,
                        'kind' => 'base',
                        'baseSeconds' => 32,
                        'stopOffsetNumerator' => 1,
                        'stopOffsetDenominator' => 3,
                        'grade' => '2',
                    ],
                ],
            ],
            $overrides,
        );
    }

    public function testGetCollection(): void
    {
        $session = $this->createPrintSession();
        $this->createPrintWork(['session' => $session, 'number' => 1]);
        $this->createPrintWork(['session' => $session, 'number' => 2]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/prints');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Print',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetPrint(): void
    {
        $print = $this->createPrintWork();

        $client = self::loggedClientAdmin();
        $client->request('GET', '/prints/' . $print->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['number' => 1]);
    }

    public function testCreatePrint(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $response = $client->request(
            'POST',
            '/prints',
            $this->withJsonLd($this->basePrintPayload('/print_sessions/' . $session->getId())),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('ilford', $data['paperBrand']);
        $this->assertCount(1, $data['exposures']);
        $this->assertEqualsWithDelta(40.3, $data['exposures'][0]['effectiveSeconds'], 0.05);
    }

    public function testCreatePrintWithoutBaseExposureFails(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), [
                    'exposures' => [
                        [
                            'order' => 1,
                            'kind' => 'burn',
                            'baseSeconds' => 10,
                            'grade' => '2',
                        ],
                    ],
                ]),
            ),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePrintWithDuplicateExposureOrderFails(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), [
                    'exposures' => [
                        ['order' => 1, 'kind' => 'base', 'baseSeconds' => 32, 'grade' => '2'],
                        ['order' => 1, 'kind' => 'burn', 'baseSeconds' => 16, 'grade' => '2'],
                    ],
                ]),
            ),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePrintWithoutExposuresFails(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), ['exposures' => []]),
            ),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePrintWithOtherPaperSurfaceWithoutDetailFails(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), ['paperSurface' => 'other']),
            ),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreatePrintWithOtherPaperSurfaceAndDetailSucceeds(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), [
                    'paperSurface' => 'other',
                    'paperSurfaceOther' => 'semi-matte maison',
                ]),
            ),
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreatePrintWithMultipleExposures(): void
    {
        $session = $this->createPrintSession();

        $client = self::loggedClientAdmin();
        $response = $client->request(
            'POST',
            '/prints',
            $this->withJsonLd(
                $this->basePrintPayload('/print_sessions/' . $session->getId(), [
                    'exposures' => [
                        ['order' => 1, 'kind' => 'base', 'baseSeconds' => 32, 'grade' => '2'],
                        [
                            'order' => 2,
                            'kind' => 'dodge',
                            'baseSeconds' => 16,
                            'grade' => '2',
                            'observation' => 'retenue sur le visage',
                        ],
                    ],
                ]),
            ),
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, $response->toArray()['exposures']);
    }

    public function testUpdatePrint(): void
    {
        $print = $this->createPrintWork();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/prints/' . $print->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['notes' => 'trop clair, refaire'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['notes' => 'trop clair, refaire']);
    }

    public function testDeletePrint(): void
    {
        $print = $this->createPrintWork();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/prints/' . $print->getId());
        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/prints/' . $print->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testFilterByPaperBrand(): void
    {
        $session = $this->createPrintSession();
        $this->createPrintWork(['session' => $session, 'number' => 1]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/prints', ['query' => ['paperBrand' => 'ilford']]);
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('GET', '/prints', ['query' => ['paperBrand' => 'foma']]);
        $this->assertJsonContains(['hydra:totalItems' => 0]);
    }

    public function testFilterBySession(): void
    {
        $sessionA = $this->createPrintSession(['number' => 1]);
        $sessionB = $this->createPrintSession(['number' => 2]);
        $this->createPrintWork(['session' => $sessionA, 'number' => 1]);
        $this->createPrintWork(['session' => $sessionB, 'number' => 1]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/prints', ['query' => ['session' => '/print_sessions/' . $sessionA->getId()]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    private function withJsonLd(array $json): array
    {
        return ['headers' => ['Content-Type' => 'application/ld+json'], 'json' => $json];
    }
}

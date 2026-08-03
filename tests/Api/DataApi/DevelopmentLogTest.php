<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class DevelopmentLogTest extends AbstractFilmTestCase
{
    public function testCreateDevelopmentLog(): void
    {
        $film = $this->createFilm(['name' => 'HP5 Plus', 'process' => 'B&W', 'sensibility' => 400]);
        $chemistry = $this->createChemistry(['name' => 'D-76', 'process' => 'B&W']);
        $camera = $this->createCamera(['name' => 'F100']);

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'film' => '/films/' . $film->getId(),
                'camera' => '/cameras/' . $camera->getId(),
                'shotAt' => ['year' => 2024, 'month' => 6],
                'isoShotAt' => 800,
                'shootingNotes' => 'Overcast day, pushed for a dim concert.',
                'process' => 'B&W',
                'developedAt' => '2024-06-15',
                'steps' => [
                    [
                        'chemistry' => '/chemistries/' . $chemistry->getId(),
                        'chemistryParts' => 1,
                        'waterParts' => 1,
                        'temperature' => 20.0,
                        'durationSeconds' => 720,
                        'agitationNote' => '10s initial, 5s every minute',
                    ],
                ],
                'developmentNotes' => 'Slightly denser than usual, as expected for a push.',
                'rating' => 4,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('B&W', $data['process']);
        $this->assertSame(800, $data['isoShotAt']);
        $this->assertEqualsWithDelta(1.0, $data['pushPullStops'], 0.001);
        $this->assertSame('2024-06', $data['shotAt']['label']);
        $this->assertCount(1, $data['steps']);
        $this->assertSame(720, $data['steps'][0]['durationSeconds']);
        $this->assertSame('test_user_admin', $data['createdBy']);
    }

    public function testShotAtWithYearOnly(): void
    {
        $film = $this->createFilm();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'film' => '/films/' . $film->getId(),
                'shotAt' => ['year' => 2023],
                'isoShotAt' => $film->getSensibility(),
                'process' => $film->getProcess(),
                'developedAt' => '2023-11-01',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertEqualsWithDelta(0.0, $response->toArray()['pushPullStops'], 0.001);
    }

    public function testCreateDevelopmentLogWithoutFilmFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'shotAt' => ['year' => 2024],
                'isoShotAt' => 400,
                'process' => 'B&W',
                'developedAt' => '2024-06-15',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateDevelopmentLogWithDayButNoMonthFails(): void
    {
        $film = $this->createFilm();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/development_logs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'film' => '/films/' . $film->getId(),
                'shotAt' => ['year' => 2024, 'day' => 12],
                'isoShotAt' => $film->getSensibility(),
                'process' => $film->getProcess(),
                'developedAt' => '2024-06-15',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateDevelopmentLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $response = $client->request('PATCH', '/development_logs/' . $developmentLog->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['rating' => 5],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(5, $response->toArray()['rating']);
    }

    public function testDeleteDevelopmentLog(): void
    {
        $developmentLog = $this->createDevelopmentLog(['createdBy' => 'test_user_admin']);

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/development_logs/' . $developmentLog->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}

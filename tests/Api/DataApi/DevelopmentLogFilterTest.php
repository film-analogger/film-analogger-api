<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentStep;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class DevelopmentLogFilterTest extends AbstractFilmTestCase
{
    public function testFilterByFilm(): void
    {
        $film = $this->createFilm(['name' => 'HP5 Plus']);
        $otherFilm = $this->createFilm(['name' => 'Portra 400']);
        $this->createDevelopmentLog(['film' => $film]);
        $this->createDevelopmentLog(['film' => $otherFilm]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['film' => '/films/' . $film->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByCamera(): void
    {
        $camera = $this->createCamera(['name' => 'F100']);
        $otherCamera = $this->createCamera(['name' => 'FM2']);
        $this->createDevelopmentLog(['camera' => $camera]);
        $this->createDevelopmentLog(['camera' => $otherCamera]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['camera' => '/cameras/' . $camera->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByShotAtYearAndMonth(): void
    {
        $this->createDevelopmentLog(['shotAtYear' => 2023, 'shotAtMonth' => 5]);
        $this->createDevelopmentLog(['shotAtYear' => 2024, 'shotAtMonth' => 6]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['shotAt.year' => 2024, 'shotAt.month' => 6],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByIsoShotAtIsExact(): void
    {
        $film = $this->createFilm(['sensibility' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 800]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['isoShotAt' => 800]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByShootingNotesIsPartialCaseInsensitive(): void
    {
        $this->createDevelopmentLog(['shootingNotes' => 'Overcast day, pushed for a dim concert.']);
        $this->createDevelopmentLog(['shootingNotes' => 'Sunny afternoon walk.']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['shootingNotes' => 'overcast'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByProcess(): void
    {
        $bwFilm = $this->createFilm(['process' => 'B&W', 'sensibility' => 400]);
        $colorFilm = $this->createFilm(['process' => 'C-41', 'sensibility' => 400]);
        $this->createDevelopmentLog(['film' => $bwFilm, 'process' => 'B&W']);
        $this->createDevelopmentLog(['film' => $colorFilm, 'process' => 'C-41']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['process' => 'B&W']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByDeveloperNameInSteps(): void
    {
        $developer = $this->createChemistry(['name' => 'D-76']);
        $otherDeveloper = $this->createChemistry(['name' => 'Rodinal']);

        $logWithD76 = $this->createDevelopmentLog();
        $this->addStep($logWithD76, $developer);

        $logWithRodinal = $this->createDevelopmentLog();
        $this->addStep($logWithRodinal, $otherDeveloper);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['steps.chemistry.name' => 'd-76'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByRatingExact(): void
    {
        $this->createDevelopmentLog(['rating' => 3]);
        $this->createDevelopmentLog(['rating' => 5]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['rating' => 5]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByRatingRange(): void
    {
        $this->createDevelopmentLog(['rating' => 2]);
        $this->createDevelopmentLog(['rating' => 4]);
        $this->createDevelopmentLog(['rating' => 5]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['rating[gte]' => 4]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testFilterByTags(): void
    {
        $tag = $this->createTag(['name' => 'Fogged']);
        $otherTag = $this->createTag(['name' => 'Underexposed']);

        $logWithTag = $this->createDevelopmentLog();
        $logWithTag->addTag($tag);
        $this->documentManager->persist($logWithTag);

        $logWithOtherTag = $this->createDevelopmentLog();
        $logWithOtherTag->addTag($otherTag);
        $this->documentManager->persist($logWithOtherTag);

        $this->documentManager->flush();

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['tags' => '/tags/' . $tag->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByBinderIdIsPartial(): void
    {
        $this->createDevelopmentLog(['binderId' => '00']);
        $this->createDevelopmentLog(['binderId' => '12']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['binderId' => '1']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByContactSheetNumber(): void
    {
        $this->createDevelopmentLog(['contactSheetNumber' => 87]);
        $this->createDevelopmentLog(['contactSheetNumber' => 12]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['contactSheetNumber' => 87]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByDevelopedAtRange(): void
    {
        $this->createDevelopmentLog(['developedAt' => new \DateTimeImmutable('2024-01-10')]);
        $this->createDevelopmentLog(['developedAt' => new \DateTimeImmutable('2024-06-15')]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', [
            'query' => ['developedAt[after]' => '2024-03-01'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByPulled(): void
    {
        $film = $this->createFilm(['sensibility' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 200]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 800]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['pulled' => 'true']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByPushed(): void
    {
        $film = $this->createFilm(['sensibility' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 200]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 400]);
        $this->createDevelopmentLog(['film' => $film, 'isoShotAt' => 800]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/development_logs', ['query' => ['pushed' => 'true']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testSortByShotAt(): void
    {
        $this->createDevelopmentLog(['shotAtYear' => 2024, 'shotAtMonth' => 1]);
        $this->createDevelopmentLog(['shotAtYear' => 2022, 'shotAtMonth' => 6]);
        $this->createDevelopmentLog(['shotAtYear' => 2023, 'shotAtMonth' => 3]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/development_logs', [
            'query' => ['order[shotAt]' => 'asc'],
        ]);

        $years = array_map(
            fn(array $log) => $log['shotAt']['year'],
            $response->toArray()['hydra:member'],
        );
        $this->assertSame([2022, 2023, 2024], $years);
    }

    public function testSortByDevelopedAt(): void
    {
        $this->createDevelopmentLog(['developedAt' => new \DateTimeImmutable('2024-06-15')]);
        $this->createDevelopmentLog(['developedAt' => new \DateTimeImmutable('2024-01-10')]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/development_logs', [
            'query' => ['order[developedAt]' => 'asc'],
        ]);

        $dates = array_map(
            fn(array $log) => substr((string) $log['developedAt'], 0, 10),
            $response->toArray()['hydra:member'],
        );
        $this->assertSame(['2024-01-10', '2024-06-15'], $dates);
    }

    public function testSortByCassement(): void
    {
        $this->createDevelopmentLog(['binderId' => '01', 'contactSheetNumber' => 5]);
        $this->createDevelopmentLog(['binderId' => '00', 'contactSheetNumber' => 87]);
        $this->createDevelopmentLog(['binderId' => '00', 'contactSheetNumber' => 12]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/development_logs', [
            'query' => ['order[binderId]' => 'asc', 'order[contactSheetNumber]' => 'asc'],
        ]);

        $cassements = array_map(
            fn(array $log) => [$log['binderId'], $log['contactSheetNumber']],
            $response->toArray()['hydra:member'],
        );
        $this->assertSame([['00', 12], ['00', 87], ['01', 5]], $cassements);
    }

    private function addStep(DevelopmentLog $developmentLog, Chemistry $chemistry): void
    {
        $step = new DevelopmentStep();
        $step->setChemistry($chemistry)->setTemperature(20.0)->setDurationSeconds(600);
        $developmentLog->addStep($step);
        $this->documentManager->persist($developmentLog);
        $this->documentManager->flush();
    }
}

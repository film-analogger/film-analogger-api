<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PrintSessionFilterTest extends AbstractFilmTestCase
{
    public function testFilterByLabIsPartialCaseInsensitive(): void
    {
        $this->createPrintSession(['lab' => 'Le Garage', 'number' => 1]);
        $this->createPrintSession(['lab' => 'Picto Paris', 'number' => 2]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions', ['query' => ['lab' => 'garage']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByEnlarger(): void
    {
        $enlarger = $this->createEnlarger(['name' => 'M805']);
        $otherEnlarger = $this->createEnlarger(['name' => 'D5500']);
        $this->createPrintSession(['enlarger' => $enlarger, 'number' => 1]);
        $this->createPrintSession(['enlarger' => $otherEnlarger, 'number' => 2]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions', [
            'query' => ['enlarger' => '/enlargers/' . $enlarger->getId()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByDeveloperNameInChemicalBaths(): void
    {
        $developer = $this->createPaperChemistry('BW_PAPER_DEVELOPER', ['name' => 'Ilfosol 3']);
        $otherDeveloper = $this->createPaperChemistry('BW_PAPER_DEVELOPER', ['name' => 'Dektol']);

        $this->createPrintSession([
            'number' => 1,
            'chemicalBaths' => [$this->createChemicalBath(['chemistry' => $developer])],
        ]);
        $this->createPrintSession([
            'number' => 2,
            'chemicalBaths' => [$this->createChemicalBath(['chemistry' => $otherDeveloper])],
        ]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions', [
            'query' => ['chemicalBaths.chemistry.name' => 'ilfosol'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testFilterByNotesIsPartialCaseInsensitive(): void
    {
        $this->createPrintSession(['number' => 1, 'notes' => 'Slight fogging on the edges.']);
        $this->createPrintSession(['number' => 2, 'notes' => 'Clean print, no issues.']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/print_sessions', ['query' => ['notes' => 'FOGGING']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);
    }

    public function testSortByDate(): void
    {
        $this->createPrintSession(['number' => 1, 'date' => new \DateTimeImmutable('2026-02-01')]);
        $this->createPrintSession(['number' => 2, 'date' => new \DateTimeImmutable('2026-01-01')]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/print_sessions', [
            'query' => ['order[date]' => 'asc'],
        ]);

        $numbers = array_map(
            fn(array $session) => $session['number'],
            $response->toArray()['hydra:member'],
        );
        $this->assertSame([2, 1], $numbers);
    }

    public function testSortByNumber(): void
    {
        $this->createPrintSession(['number' => 3]);
        $this->createPrintSession(['number' => 1]);
        $this->createPrintSession(['number' => 2]);

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/print_sessions', [
            'query' => ['order[number]' => 'asc'],
        ]);

        $numbers = array_map(
            fn(array $session) => $session['number'],
            $response->toArray()['hydra:member'],
        );
        $this->assertSame([1, 2, 3], $numbers);
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class FilmSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $this->createFilm();
        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = static::createClient();

        foreach (
            [
                ['GET', '/films'],
                ['GET', '/films/some-id'],
                ['PATCH', '/films/some-id'],
                ['DELETE', '/films/some-id'],
                ['POST', '/films'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testUserCanReadDataOnly(): void
    {
        $this->assertFilmSecurityByRole(self::loggedClientUser(), false);
    }

    private function assertFilmSecurityByRole($client, bool $canWrite): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $this->assertSuccessfulStatus($client, 'GET', '/films', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/films/' . $film->getId(), 200);

        $patchOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ];

        $postOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/films/' . $film->getId(),
                200,
                $patchOptions,
            );
            $this->assertSuccessfulStatus($client, 'DELETE', '/films/' . $film->getId(), 204);
            $this->assertSuccessfulStatus($client, 'POST', '/films', 201, $postOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/films/' . $film->getId(),
            $patchOptions,
        );
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/films/' . $film->getId());
        $this->assertForbiddenAccessDenied($client, 'POST', '/films', $postOptions);
    }
}

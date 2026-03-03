<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class FilmSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $this->createFilm();
        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = static::createClient();
        $client->request('GET', '/films');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('GET', '/films/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('PATCH', '/films/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('DELETE', '/films/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('POST', '/films');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);
        $client->request('GET', '/manufacturers');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('GET', '/manufacturers/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('PATCH', '/manufacturers/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('DELETE', '/manufacturers/some-id');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);

        $client->request('POST', '/manufacturers');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'Token is not present in the request headers',
        ]);
    }

    public function testAdminCanDoAnithing(): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('DELETE', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testDataWriterCanDoAnithing(): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('DELETE', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = self::loggedClientDataReader();
        $client->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);
        $client->request('DELETE', '/films/' . $film->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);
    }

    public function testUserCanReadDataOnly(): void
    {
        $film = $this->createFilm();
        $manufacturer = $this->createManufacturer();

        $this->createFilm(['name' => 'Ektar 100', 'sensibility' => 100]);

        $client = self::loggedClientUser();
        $client->request('GET', '/films');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/films/' . $film->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $client->request('PATCH', '/films/' . $film->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Portra 800',
                'sensibility' => 800,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);
        $client->request('DELETE', '/films/' . $film->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);

        $client->request('POST', '/films', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Gold 200',
                'description' => 'A consumer color negative film.',
                'process' => 'C-41',
                'sensibility' => 200,
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'detail' => 'Access Denied.',
            'status' => 403,
        ]);
    }
}

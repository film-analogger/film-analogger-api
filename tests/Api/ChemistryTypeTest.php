<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ChemistryTypeTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createChemistryType('B&W', 'BW_FILM_DEVELOPER', 'Film Developer');
        $this->createChemistryType('B&W', 'FIXER', 'Fixer');

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ChemistryType',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetChemistryType(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'process' => 'B&W',
            'typeCode' => 'BW_FILM_DEVELOPER',
            'typeLabel' => 'Film Developer',
        ]);
    }

    public function testCreateChemistryType(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'process' => 'B&W',
            'typeCode' => 'FIXER',
            'typeLabel' => 'Fixer',
        ]);
    }

    public function testCreateChemistryTypeForC41Process(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'C-41',
                'typeCode' => 'C41_COLOR_DEVELOPER',
                'typeLabel' => 'Color Developer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'process' => 'C-41',
            'typeCode' => 'C41_COLOR_DEVELOPER',
        ]);
    }

    public function testCreateChemistryTypeForE6Process(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'E-6',
                'typeCode' => 'E6_FILM_DEVELOPER',
                'typeLabel' => 'First Developer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'process' => 'E-6',
            'typeCode' => 'E6_FILM_DEVELOPER',
        ]);
    }

    public function testCreateChemistryTypeForRA4Process(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'RA4',
                'typeCode' => 'RA4_COLOR_DEVELOPER',
                'typeLabel' => 'Color Developer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'process' => 'RA4',
            'typeCode' => 'RA4_COLOR_DEVELOPER',
        ]);
    }

    public function testCreateChemistryTypeWithoutProcessFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryTypeWithInvalidProcessFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'INVALID-PROCESS',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryTypeWithInvalidTypeCodeForProcessFails(): void
    {
        $client = self::loggedClientAdmin();
        // BW_FILM_DEVELOPER is not valid for C-41 process
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'C-41',
                'typeCode' => 'BW_FILM_DEVELOPER',
                'typeLabel' => 'Film Developer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryTypeWithoutTypeLabelFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryTypeWithBlankTypeLabelFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateChemistryType(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistry_types/' . $chemistryType->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'typeLabel' => 'Film Dev',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'typeLabel' => 'Film Dev',
        ]);
    }

    public function testDeleteChemistryType(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/chemistry_types/' . $chemistryType->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/' . $chemistryType->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentChemistryTypeReturns404(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetCollectionReturnsEmptyWhenNoChemistryTypes(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistry_types');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testTimestampableBlameableChemistryType(): void
    {
        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/chemistry_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'createdBy' => 'test_user_admin',
            'updatedBy' => 'test_user_admin',
        ]);
        $this->assertArrayHasKey('createdAt', $response->toArray());
        $this->assertArrayHasKey('updatedAt', $response->toArray());
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->toArray()['createdAt'],
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->toArray()['updatedAt'],
        );
        $this->assertTrue($response->toArray()['updatedAt'] == $response->toArray()['createdAt']);

        sleep(1);

        $client = self::loggedClientDataWriter();
        $response = $client->request('PATCH', '/chemistry_types/' . $response->toArray()['id'], [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'typeLabel' => 'Fixer Updated',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'createdBy' => 'test_user_admin',
            'updatedBy' => 'test_user_data_writer',
        ]);
        $this->assertTrue($response->toArray()['updatedAt'] > $response->toArray()['createdAt']);
    }
}

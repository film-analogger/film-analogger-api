<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ManufacturerTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createManufacturer('Kodak');
        $this->createManufacturer('Ilford');

        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Manufacturer',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetManufacturer(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'name' => 'Kodak',
        ]);
    }

    public function testCreateManufacturer(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Fujifilm',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Fujifilm',
        ]);
    }

    public function testCreateManufacturerWithAllFields(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
                'primaryColor' => '#FAB617',
                'secondaryColor' => '#ED0000',
                'tertiaryColor' => '#FFFFFF',
                'website' => 'https://www.kodak.com',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Kodak',
            'primaryColor' => '#FAB617',
            'secondaryColor' => '#ED0000',
            'tertiaryColor' => '#FFFFFF',
            'website' => 'https://www.kodak.com',
        ]);
    }

    public function testCreateManufacturerWithoutNameFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'primaryColor' => '#FAB617',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithBlankNameFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithInvalidPrimaryColorFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
                'primaryColor' => 'not-a-color',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithInvalidSecondaryColorFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
                'secondaryColor' => 'invalid',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithInvalidTertiaryColorFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
                'tertiaryColor' => 'invalid',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithInvalidWebsiteUrlFails(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
                'website' => 'not-a-valid-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateManufacturerWithValidCssColorNames(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Ilford',
                'primaryColor' => 'white',
                'secondaryColor' => 'black',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'primaryColor' => 'white',
            'secondaryColor' => 'black',
        ]);
    }

    public function testCreateManufacturerWithNullOptionalFields(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Minimal Manufacturer',
                'primaryColor' => null,
                'secondaryColor' => null,
                'tertiaryColor' => null,
                'website' => null,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Minimal Manufacturer',
            'primaryColor' => null,
            'secondaryColor' => null,
            'tertiaryColor' => null,
            'website' => null,
        ]);
    }

    public function testUpdateManufacturer(): void
    {
        $manufacturer = $this->createManufacturer('Kodak');

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Kodak Alaris',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Kodak Alaris',
        ]);
    }

    public function testUpdateManufacturerColors(): void
    {
        $manufacturer = $this->createManufacturer('Ilford');

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'primaryColor' => '#FFFFFF',
                'secondaryColor' => '#231F20',
                'tertiaryColor' => '#AABBCC',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'primaryColor' => '#FFFFFF',
            'secondaryColor' => '#231F20',
            'tertiaryColor' => '#AABBCC',
        ]);
    }

    public function testUpdateManufacturerWebsite(): void
    {
        $manufacturer = $this->createManufacturer('Fujifilm');

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'website' => 'https://www.fujifilm.com',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'website' => 'https://www.fujifilm.com',
        ]);
    }

    public function testUpdateManufacturerWithInvalidWebsiteUrlFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/manufacturers/' . $manufacturer->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'website' => 'not-a-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDeleteManufacturer(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/manufacturers/' . $manufacturer->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/' . $manufacturer->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentManufacturerReturns404(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetCollectionReturnsEmptyWhenNoManufacturers(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/manufacturers');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testManufacturerIdIsReadOnly(): void
    {
        $manufacturer = $this->createManufacturer('Rollei');
        $originalId = $manufacturer->getId();

        $client = self::loggedClientAdmin();
        $response = $client->request('GET', '/manufacturers/' . $originalId);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($originalId, $data['id']);
    }

    public function testTimestampableBlameableManufacturer(): void
    {
        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/manufacturers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Kodak',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'createdBy' => 'test_user_admin',
            'updatedBy' => 'test_user_admin',
        ]);
        $this->assertArrayHasKey('createdAt', $response->toArray());
        $this->assertArrayHasKey('updatedAt', $response->toArray());
        $this->assertIsString($response->toArray()['createdAt']);
        $this->assertIsString($response->toArray()['updatedAt']);
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
        $response = $client->request('PATCH', '/manufacturers/' . $response->toArray()['id'], [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Kodak Alaris',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'createdBy' => 'test_user_admin',
            'updatedBy' => 'test_user_data_writer',
        ]);
        $this->assertArrayHasKey('createdAt', $response->toArray());
        $this->assertArrayHasKey('updatedAt', $response->toArray());
        $this->assertIsString($response->toArray()['createdAt']);
        $this->assertIsString($response->toArray()['updatedAt']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->toArray()['createdAt'],
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->toArray()['updatedAt'],
        );
        $this->assertTrue($response->toArray()['updatedAt'] > $response->toArray()['createdAt']);
    }
}

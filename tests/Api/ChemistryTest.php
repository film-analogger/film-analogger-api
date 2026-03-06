<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ChemistryTest extends AbstractFilmTestCase
{
    public function testGetCollection(): void
    {
        $this->createChemistry();
        $this->createChemistry(['name' => 'HC-110']);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Chemistry',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testGetChemistry(): void
    {
        $chemistry = $this->createChemistry();

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'name' => 'D-76',
            'process' => 'B&W',
        ]);
    }

    public function testCreateChemistry(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'ID-11',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'ID-11',
            'process' => 'B&W',
        ]);
    }

    public function testCreateChemistryWithAllFields(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'description' => 'Classic fine-grain developer.',
                'officialDocumentationUrl' => 'https://www.kodak.com/d76',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'D-76',
            'process' => 'B&W',
            'description' => 'Classic fine-grain developer.',
            'officialDocumentationUrl' => 'https://www.kodak.com/d76',
        ]);
    }

    public function testCreateChemistryWithDilutions(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'dilutions' => [
                    ['chemistryParts' => 1, 'waterParts' => 0, 'official' => true],
                    ['chemistryParts' => 1, 'waterParts' => 1, 'official' => true],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'dilutions' => [
                ['chemistryParts' => 1, 'waterParts' => 0, 'label' => 'stock', 'official' => true],
                ['chemistryParts' => 1, 'waterParts' => 1, 'label' => '1+1', 'official' => true],
            ],
        ]);
    }

    public function testCreateChemistryWithoutNameFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithBlankNameFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => '',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithoutProcessFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithInvalidProcessFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'INVALID-PROCESS',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithoutChemistryTypeFails(): void
    {
        $manufacturer = $this->createManufacturer();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithoutManufacturerFails(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithInvalidUrlFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'officialDocumentationUrl' => 'not-a-valid-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithDilutionZeroChemistryPartsFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'dilutions' => [['chemistryParts' => 0, 'waterParts' => 1, 'official' => true]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChemistryWithDilutionNegativeWaterPartsFails(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'dilutions' => [['chemistryParts' => 1, 'waterParts' => -1, 'official' => true]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateChemistry(): void
    {
        $chemistry = $this->createChemistry();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'D-76 Updated',
                'description' => 'Updated description.',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'D-76 Updated',
            'description' => 'Updated description.',
        ]);
    }

    public function testUpdateChemistryWithInvalidUrlFails(): void
    {
        $chemistry = $this->createChemistry();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'officialDocumentationUrl' => 'not-a-url',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateChemistryOfficialDocumentationUrl(): void
    {
        $chemistry = $this->createChemistry();

        $client = self::loggedClientAdmin();
        $client->request('PATCH', '/chemistries/' . $chemistry->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'officialDocumentationUrl' => 'https://www.ilfordphoto.com/id11',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'officialDocumentationUrl' => 'https://www.ilfordphoto.com/id11',
        ]);
    }

    public function testDeleteChemistry(): void
    {
        $chemistry = $this->createChemistry();

        $client = self::loggedClientAdmin();
        $client->request('DELETE', '/chemistries/' . $chemistry->getId());

        $this->assertResponseStatusCodeSame(204);

        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/' . $chemistry->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentChemistryReturns404(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries/nonexistent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetCollectionReturnsEmptyWhenNoChemistries(): void
    {
        $client = self::loggedClientAdmin();
        $client->request('GET', '/chemistries');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testDilutionLabelIsStockWhenUndiluted(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'dilutions' => [['chemistryParts' => 1, 'waterParts' => 0, 'official' => true]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('stock', $data['dilutions'][0]['label']);
    }

    public function testDilutionLabelFormat(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'HC-110',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'dilutions' => [['chemistryParts' => 1, 'waterParts' => 31, 'official' => true]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('1+31', $data['dilutions'][0]['label']);
    }

    public function testTimestampableBlameableChemistry(): void
    {
        $manufacturer = $this->createManufacturer();
        $chemistryType = $this->createChemistryType();

        $client = self::loggedClientAdmin();
        $response = $client->request('POST', '/chemistries', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'D-76',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryType->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
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
        $response = $client->request('PATCH', '/chemistries/' . $response->toArray()['id'], [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'D-76 Updated',
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

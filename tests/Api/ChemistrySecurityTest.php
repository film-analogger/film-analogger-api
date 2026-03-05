<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ChemistrySecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $chemistry = $this->createChemistry();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/chemistries'],
                ['GET', '/chemistries/' . $chemistry->getId()],
                ['PATCH', '/chemistries/' . $chemistry->getId()],
                ['DELETE', '/chemistries/' . $chemistry->getId()],
                ['POST', '/chemistries'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testUserCanReadDataOnly(): void
    {
        $this->assertChemistrySecurityByRole(self::loggedClientUser(), false);
    }

    private function assertChemistrySecurityByRole($client, bool $canWrite): void
    {
        $chemistry = $this->createChemistry();
        $manufacturer = $this->createManufacturer();
        $chemistryTypeForChemistry = $this->createChemistryType();

        $this->assertSuccessfulStatus($client, 'GET', '/chemistries', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/chemistries/' . $chemistry->getId(), 200);

        $patchChemistryOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'D-76 Updated',
            ],
        ];

        $postChemistryOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'ID-11',
                'process' => 'B&W',
                'chemistryType' => '/chemistry_types/' . $chemistryTypeForChemistry->getId(),
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/chemistries/' . $chemistry->getId(),
                200,
                $patchChemistryOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/chemistries/' . $chemistry->getId(),
                204,
            );
            $this->assertSuccessfulStatus(
                $client,
                'POST',
                '/chemistries',
                201,
                $postChemistryOptions,
            );

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/chemistries/' . $chemistry->getId(),
            $patchChemistryOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/chemistries/' . $chemistry->getId(),
        );
        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistries', $postChemistryOptions);
    }
}

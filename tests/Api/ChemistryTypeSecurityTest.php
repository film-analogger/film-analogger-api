<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api;

class ChemistryTypeSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $chemistryType = $this->createChemistryType();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/chemistry_types'],
                ['GET', '/chemistry_types/' . $chemistryType->getId()],
                ['PATCH', '/chemistry_types/' . $chemistryType->getId()],
                ['DELETE', '/chemistry_types/' . $chemistryType->getId()],
                ['POST', '/chemistry_types'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testUserCanReadDataOnly(): void
    {
        $this->assertChemistryTypeSecurityByRole(self::loggedClientUser(), false);
    }

    private function assertChemistryTypeSecurityByRole($client, bool $canWrite): void
    {
        $chemistryType = $this->createChemistryType('B&W Fixer', 'B&W', 'FIXER', 'Fixer');

        $this->assertSuccessfulStatus($client, 'GET', '/chemistry_types', 200);
        $this->assertSuccessfulStatus(
            $client,
            'GET',
            '/chemistry_types/' . $chemistryType->getId(),
            200,
        );

        $patchOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'typeLabel' => 'Fixer Updated',
            ],
        ];

        $postOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'B&W Fixer 2',
                'process' => 'B&W',
                'typeCode' => 'FIXER',
                'typeLabel' => 'Fixer',
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/chemistry_types/' . $chemistryType->getId(),
                200,
                $patchOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/chemistry_types/' . $chemistryType->getId(),
                204,
            );
            $this->assertSuccessfulStatus($client, 'POST', '/chemistry_types', 201, $postOptions);

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/chemistry_types/' . $chemistryType->getId(),
            $patchOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/chemistry_types/' . $chemistryType->getId(),
        );
        $this->assertForbiddenAccessDenied($client, 'POST', '/chemistry_types', $postOptions);
    }
}

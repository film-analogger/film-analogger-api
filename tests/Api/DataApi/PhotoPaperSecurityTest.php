<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class PhotoPaperSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $photoPaper = $this->createPhotoPaper();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/photo_papers'],
                ['GET', '/photo_papers/' . $photoPaper->getId()],
                ['PATCH', '/photo_papers/' . $photoPaper->getId()],
                ['DELETE', '/photo_papers/' . $photoPaper->getId()],
                ['POST', '/photo_papers'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testAdminCanDoAnything(): void
    {
        $this->assertPhotoPaperSecurityByRole(self::loggedClientAdmin(), true);
    }

    public function testDataWriterCanDoAnything(): void
    {
        $this->assertPhotoPaperSecurityByRole(self::loggedClientDataWriter(), true);
    }

    public function testDataReaderCanReadDataOnly(): void
    {
        $this->assertPhotoPaperSecurityByRole(self::loggedClientDataReader(), false);
    }

    public function testDataReaderAloneCannotCreate(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientDataReader();

        $this->assertForbiddenAccessDenied($client, 'POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Multigrade RC Pearl',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'pearl',
            ],
        ]);
    }

    public function testUserCannotWriteOfficialPhotoPaperOfSomeoneElse(): void
    {
        $photoPaper = $this->createPhotoPaper([
            'status' => CatalogStatus::OFFICIAL,
            'createdBy' => 'other_user',
        ]);
        $client = self::loggedClientUser(preferred_username: 'plain_user');

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/photo_papers/' . $photoPaper->getId(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['name' => 'Renamed'],
            ],
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/photo_papers/' . $photoPaper->getId(),
        );
    }

    public function testUserCanCreatePersonalPhotoPaperByDefault(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $client->request('POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Papier maison',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'glossy',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'personal']);
    }

    public function testUserCannotCreateOfficialPhotoPaperDirectly(): void
    {
        $manufacturer = $this->createManufacturer();
        $client = self::loggedClientUser();

        $this->assertForbiddenAccessDenied($client, 'POST', '/photo_papers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Papier maison',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'glossy',
                'status' => 'official',
            ],
        ]);
    }

    public function testUserCanEditOwnPersonalPhotoPaperAndSubmitForValidation(): void
    {
        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $photoPaper = $this->createPhotoPaper([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'plain_user',
        ]);

        $client->request('PATCH', '/photo_papers/' . $photoPaper->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'pending'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testUserCannotSeeOthersPersonalPhotoPaper(): void
    {
        $photoPaper = $this->createPhotoPaper([
            'status' => CatalogStatus::PERSONAL,
            'createdBy' => 'other_user',
        ]);

        $client = self::loggedClientUser(preferred_username: 'plain_user');
        $this->assertForbiddenAccessDenied($client, 'GET', '/photo_papers/' . $photoPaper->getId());
    }

    public function testDataWriterSeesEveryStatusAndCanApprovePending(): void
    {
        $photoPaper = $this->createPhotoPaper([
            'status' => CatalogStatus::PENDING,
            'createdBy' => 'plain_user',
        ]);

        $client = self::loggedClientDataWriter();
        $client->request('GET', '/photo_papers?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 1]);

        $client->request('PATCH', '/photo_papers/' . $photoPaper->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'official'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['status' => 'official']);
    }

    private function assertPhotoPaperSecurityByRole($client, bool $canWrite): void
    {
        $photoPaper = $this->createPhotoPaper();
        $manufacturer = $this->createManufacturer();

        $this->assertSuccessfulStatus($client, 'GET', '/photo_papers', 200);
        $this->assertSuccessfulStatus($client, 'GET', '/photo_papers/' . $photoPaper->getId(), 200);

        $patchPhotoPaperOptions = [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Multigrade RC Pearl Updated',
            ],
        ];

        $postPhotoPaperOptions = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Fomabrom RC Matt',
                'manufacturer' => '/manufacturers/' . $manufacturer->getId(),
                'paperBase' => 'rc',
                'paperSurface' => 'matt',
            ],
        ];

        if ($canWrite) {
            $this->assertSuccessfulStatus(
                $client,
                'PATCH',
                '/photo_papers/' . $photoPaper->getId(),
                200,
                $patchPhotoPaperOptions,
            );
            $this->assertSuccessfulStatus(
                $client,
                'DELETE',
                '/photo_papers/' . $photoPaper->getId(),
                204,
            );
            $this->assertSuccessfulStatus(
                $client,
                'POST',
                '/photo_papers',
                201,
                $postPhotoPaperOptions,
            );

            return;
        }

        $this->assertForbiddenAccessDenied(
            $client,
            'PATCH',
            '/photo_papers/' . $photoPaper->getId(),
            $patchPhotoPaperOptions,
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'DELETE',
            '/photo_papers/' . $photoPaper->getId(),
        );
        $this->assertForbiddenAccessDenied(
            $client,
            'POST',
            '/photo_papers',
            $postPhotoPaperOptions,
        );
    }
}

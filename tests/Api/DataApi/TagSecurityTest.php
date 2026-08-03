<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Api\DataApi;

use FilmAnalogger\FilmAnaloggerApi\Tests\Api\AbstractFilmTestCase;

class TagSecurityTest extends AbstractFilmTestCase
{
    public function testNoConnectedUserGetUnauthorized(): void
    {
        $tag = $this->createTag();

        $client = static::createClient();

        foreach (
            [
                ['GET', '/tags'],
                ['GET', '/tags/' . $tag->getId()],
                ['PATCH', '/tags/' . $tag->getId()],
                ['DELETE', '/tags/' . $tag->getId()],
                ['POST', '/tags'],
            ]
            as [$method, $uri]
        ) {
            $this->assertUnauthorizedMissingToken($client, $method, $uri);
        }
    }

    public function testDataReaderCannotWrite(): void
    {
        $tag = $this->createTag();

        $client = self::loggedClientDataReader();

        $this->assertSuccessfulStatus($client, 'GET', '/tags', 200);
        $this->assertForbiddenAccessDenied($client, 'DELETE', '/tags/' . $tag->getId());
    }

    public function testDataWriterCanWrite(): void
    {
        $tag = $this->createTag();

        $client = self::loggedClientDataWriter();

        $this->assertSuccessfulStatus($client, 'DELETE', '/tags/' . $tag->getId(), 204);
    }
}

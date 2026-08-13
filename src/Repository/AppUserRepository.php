<?php

namespace FilmAnalogger\FilmAnaloggerApi\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;

class AppUserRepository extends DocumentRepository
{
    public function findOneBySub(string $sub): ?AppUser
    {
        return $this->findOneBy(['keycloakSub' => $sub]);
    }

    public function findOneByUsername(string $username): ?AppUser
    {
        return $this->findOneBy(['username' => $username]);
    }
}

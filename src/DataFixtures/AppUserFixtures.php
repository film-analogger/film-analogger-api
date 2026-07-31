<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Document\AppUser;

// Mirrors the 4 Keycloak test users provisioned by terraform/test-users.tf.
// The keycloakSub values are this dev stack's actually-applied Keycloak user
// IDs (see terraform/terraform.tfstate) so that a real login by one of these
// test users matches the existing AppUser via
// UserProvisioningEventSubscriber::findOneBySub() instead of creating a
// duplicate that would collide on the unique username/email indexes.
class AppUserFixtures extends Fixture
{
    public const TEST_READER = 'app-user-test-reader';
    public const TEST_USER = 'app-user-test-user';
    public const TEST_WRITER = 'app-user-test-writer';
    public const TEST_ADMIN = 'app-user-test-admin';

    public const TEST_READER_USERNAME = 'test_reader';
    public const TEST_USER_USERNAME = 'test_user';
    public const TEST_WRITER_USERNAME = 'test_writer';
    public const TEST_ADMIN_USERNAME = 'test_admin';

    public function load(ObjectManager $manager): void
    {
        foreach (
            $this->getData()
            as [$reference, $keycloakSub, $username, $email, $givenName, $familyName]
        ) {
            $appUser = new AppUser();
            $appUser->keycloakSub = $keycloakSub;
            $appUser->username = $username;
            $appUser->email = $email;
            $appUser->name = $givenName . ' ' . $familyName;
            $appUser->givenName = $givenName;
            $appUser->familyName = $familyName;

            $manager->persist($appUser);
            $this->addReference($reference, $appUser);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                self::TEST_READER,
                'e5fc3d65-b1df-459f-9930-7121ce1eeb42',
                self::TEST_READER_USERNAME,
                'test_reader@example.test',
                'Alice',
                'Alinson',
            ],
            [
                self::TEST_USER,
                'c68faaff-d07d-4fb8-9e4c-ba7de41e5531',
                self::TEST_USER_USERNAME,
                'test_user@example.test',
                'Bob',
                'Bobinson',
            ],
            [
                self::TEST_WRITER,
                '25ad18b1-ff1b-4de0-9ac9-f677cb2452ea',
                self::TEST_WRITER_USERNAME,
                'test_writer@example.test',
                'Carol',
                'Carolinson',
            ],
            [
                self::TEST_ADMIN,
                '5254b268-cfcb-4f85-b967-9c9b3d94cede',
                self::TEST_ADMIN_USERNAME,
                'test_admin@example.test',
                'Dave',
                'Davidson',
            ],
        ];
    }
}

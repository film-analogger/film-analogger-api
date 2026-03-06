<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(repositoryClass: AppUserRepository::class)]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::APP_USER_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
            ],
        ],
        denormalizationContext: ['groups' => [SerializationGroups::APP_USER_WRITE_GROUP]],
        operations: [
            new Get(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Patch(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_READER .
                    '") and object.username === user.getUserIdentifier()',
            ),
        ],
    ),
]
class AppUser
{
    use TimestampableBlameableTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field]
    #[ODM\Index(unique: true)]
    #[ApiProperty(security: 'is_granted("' . KeycloakRoles::ADMIN . '")')]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    public string $keycloakSub;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[ODM\Index(unique: true)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    public string $username;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ODM\Index(unique: true)]
    #[
        ApiProperty(
            security: 'is_granted("' .
                KeycloakRoles::ADMIN .
                '") or object.username === user.getUserIdentifier()',
        ),
    ]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    public string $email;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    #[
        ApiProperty(
            security: 'is_granted("' .
                KeycloakRoles::ADMIN .
                '") or object.username === user.getUserIdentifier()',
        ),
    ]
    public ?string $name = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    #[
        ApiProperty(
            security: 'is_granted("' .
                KeycloakRoles::ADMIN .
                '") or object.username === user.getUserIdentifier()',
        ),
    ]
    public ?string $givenName = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    #[
        ApiProperty(
            security: 'is_granted("' .
                KeycloakRoles::ADMIN .
                '") or object.username === user.getUserIdentifier()',
        ),
    ]
    public ?string $familyName = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP, SerializationGroups::APP_USER_WRITE_GROUP])]
    public ?string $website = null;

    #[ODM\Field(nullable: true)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP, SerializationGroups::APP_USER_WRITE_GROUP])]
    public ?string $description = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP, SerializationGroups::APP_USER_WRITE_GROUP])]
    public ?string $avatarUrl = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getKeycloakSub(): string
    {
        return $this->keycloakSub;
    }

    public function setKeycloakSub(string $keycloakSub): self
    {
        $this->keycloakSub = $keycloakSub;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): self
    {
        $this->givenName = $givenName;
        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): self
    {
        $this->familyName = $familyName;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }
}

<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Repository\AppUserRepository;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use FilmAnalogger\FilmAnaloggerApi\State\AvatarUploadProcessor;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\OpenApi\Model as Model;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ODM\Document(repositoryClass: AppUserRepository::class)]
#[Uploadable]
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
            new Post(
                uriTemplate: '/app_users/{id}/avatar',
                name: 'upload_avatar',
                processor: AvatarUploadProcessor::class,
                inputFormats: ['multipart' => ['multipart/form-data']],
                openapi: new Model\Operation(
                    summary: 'Upload user avatar',
                    description: 'Upload or replace the avatar image for a user.',
                    requestBody: new Model\RequestBody(
                        content: new \ArrayObject([
                            'multipart/form-data' => new Model\MediaType(
                                schema: new \ArrayObject([
                                    'type' => 'object',
                                    'properties' => [
                                        'avatarFile' => [
                                            'type' => 'string',
                                            'format' => 'binary',
                                            'description' => 'The avatar image file',
                                        ],
                                    ],
                                    'required' => ['avatarFile'],
                                ]),
                            ),
                        ]),
                    ),
                ),
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

    #[ApiProperty(types: ['https://schema.org/contentUrl'], writable: false)]
    #[Groups([SerializationGroups::APP_USER_READ_GROUP])]
    public ?string $avatarUrl = null;

    #[ODM\Field(nullable: true)]
    public ?string $avatarPath = null;

    #[ApiProperty(writable: false)]
    #[UploadableField(mapping: 'avatar', fileNameProperty: 'avatarPath')]
    private ?File $avatarFile = null;

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

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): self
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile): self
    {
        $this->avatarFile = $avatarFile;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }
}

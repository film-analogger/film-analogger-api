<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\CatalogStatusTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::TAG_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::TAG_WRITE_GROUP,
                SerializationGroups::CATALOG_STATUS_WRITE_GROUP,
            ],
        ],
        operations: [
            new Get(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_READER .
                    '") and (object.isOfficial() or is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.getCreatedBy() === user.getUserIdentifier())',
            ),
            new GetCollection(security: 'is_granted("' . KeycloakRoles::DATA_READER . '")'),
            new Post(
                security: 'is_granted("' . KeycloakRoles::USER . '")',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
            ),
            new Patch(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and object.isOwnerEditableStatus())',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
            ),
            new Delete(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and not object.isOfficial())',
            ),
        ],
    ),
]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class Tag implements Translatable
{
    use TranslatableTrait;
    use TimestampableBlameableTrait;
    use CatalogStatusTrait;

    #[ODM\Id]
    #[Groups([SerializationGroups::TAG_READ_GROUP])]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Gedmo\Translatable]
    #[Assert\NotBlank]
    #[Groups([SerializationGroups::TAG_READ_GROUP, SerializationGroups::TAG_WRITE_GROUP])]
    private string $name;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Gedmo\Translatable]
    #[Groups([SerializationGroups::TAG_READ_GROUP, SerializationGroups::TAG_WRITE_GROUP])]
    private ?string $description = null;

    #[ODM\Field(nullable: true)]
    #[Assert\CssColor]
    #[Groups([SerializationGroups::TAG_READ_GROUP, SerializationGroups::TAG_WRITE_GROUP])]
    public ?string $primaryColor = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}

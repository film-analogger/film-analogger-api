<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\CatalogStatusTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TimestampableBlameableTrait;
use FilmAnalogger\FilmAnaloggerApi\Document\Trait\TranslatableTrait;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use ApiPlatform\OpenApi\Model as Model;
use FilmAnalogger\FilmAnaloggerApi\OpenApi\AuthenticationErrorResponse;

#[ODM\Document]
#[
    ApiResource(
        normalizationContext: [
            'skip_null_values' => false,
            'groups' => [
                SerializationGroups::MANUFACTURER_READ_GROUP,
                SerializationGroups::TIMESTAMPABLE_BLAMEABLE_READ_GROUP,
                SerializationGroups::TRANSLATABLE_READ_GROUP,
                SerializationGroups::CATALOG_STATUS_READ_GROUP,
            ],
        ],
        denormalizationContext: [
            'groups' => [
                SerializationGroups::MANUFACTURER_WRITE_GROUP,
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
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new GetCollection(
                security: 'is_granted("' . KeycloakRoles::DATA_READER . '")',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Post(
                security: 'is_granted("' . KeycloakRoles::USER . '")',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Patch(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and object.isOwnerEditableStatus())',
                securityPostDenormalize: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or object.isPersonalOrPending()',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
            new Delete(
                security: 'is_granted("' .
                    KeycloakRoles::DATA_WRITER .
                    '") or (object.getCreatedBy() === user.getUserIdentifier() and not object.isOfficial())',
                openapi: new Model\Operation(
                    responses: [
                        '401' => AuthenticationErrorResponse::RESPONSE_401,
                        '403' => AuthenticationErrorResponse::RESPONSE_403,
                    ],
                ),
            ),
        ],
    ),
]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class Manufacturer implements Translatable
{
    use TimestampableBlameableTrait;
    use TranslatableTrait;
    use CatalogStatusTrait;

    #[ODM\Id]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::FILM_READ_GROUP,
            SerializationGroups::ENLARGER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::CAMERA_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
        ]),
    ]
    private ?string $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
            SerializationGroups::FILM_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::ENLARGER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::CAMERA_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
        ]),
    ]
    public string $name;

    #[ODM\Field(nullable: true)]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $description = null;

    // writableLink: false on all six collections below forces the OpenAPI
    // write schema back to plain IRI strings. Without it, API Platform sees
    // that Film/Chemistry/Camera/Enlarger/PhotoPaper/EnlargerLens's own
    // $status shares CATALOG_STATUS_WRITE_GROUP with this resource's
    // denormalizationContext and documents each collection as an array of
    // embeddable objects requiring `status`, even though only IRIs are ever
    // needed.
    #[ODM\ReferenceMany(targetDocument: Film::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $films;

    #[ODM\ReferenceMany(targetDocument: Chemistry::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $chemistries;

    #[ODM\ReferenceMany(targetDocument: Camera::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $cameras;

    #[ODM\ReferenceMany(targetDocument: Enlarger::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $enlargers;

    #[ODM\ReferenceMany(targetDocument: PhotoPaper::class, mappedBy: 'manufacturer', storeAs: 'id')]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $photoPapers;

    #[
        ODM\ReferenceMany(
            targetDocument: EnlargerLens::class,
            mappedBy: 'manufacturer',
            storeAs: 'id',
        ),
    ]
    #[ApiProperty(writableLink: false)]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public Collection $enlargerLenses;

    #[ODM\Field(nullable: true)]
    #[Assert\CssColor]
    #[ApiProperty(example: '#FF0000')]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
            SerializationGroups::FILM_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::ENLARGER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::CAMERA_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
        ]),
    ]
    public ?string $primaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#00FF00')]
    #[Assert\CssColor]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
            SerializationGroups::FILM_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::ENLARGER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::CAMERA_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
        ]),
    ]
    public ?string $secondaryColor = null;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(example: '#0000FF')]
    #[Assert\CssColor]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
            SerializationGroups::FILM_READ_GROUP,
            SerializationGroups::CHEMISTRY_READ_GROUP,
            SerializationGroups::ENLARGER_READ_GROUP,
            SerializationGroups::PHOTO_PAPER_READ_GROUP,
            SerializationGroups::CAMERA_READ_GROUP,
            SerializationGroups::ENLARGER_LENS_READ_GROUP,
        ]),
    ]
    public ?string $tertiaryColor = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Url]
    #[Gedmo\Translatable]
    #[
        Groups([
            SerializationGroups::MANUFACTURER_READ_GROUP,
            SerializationGroups::MANUFACTURER_WRITE_GROUP,
        ]),
    ]
    public ?string $website = null;

    public function __construct()
    {
        $this->films = new ArrayCollection();
        $this->chemistries = new ArrayCollection();
        $this->cameras = new ArrayCollection();
        $this->enlargers = new ArrayCollection();
        $this->photoPapers = new ArrayCollection();
        $this->enlargerLenses = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function addFilm(Film $film): void
    {
        $film->setManufacturer($this);
        $this->films->add($film);
    }

    public function getFilms(): Collection
    {
        return $this->films;
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

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setSecondaryColor(?string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;
        return $this;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    public function setTertiaryColor(?string $tertiaryColor): static
    {
        $this->tertiaryColor = $tertiaryColor;
        return $this;
    }

    public function getTertiaryColor(): ?string
    {
        return $this->tertiaryColor;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function addChemistry(Chemistry $chemistry): static
    {
        $chemistry->setManufacturer($this);
        $this->chemistries->add($chemistry);
        return $this;
    }

    public function getChemistries(): Collection
    {
        return $this->chemistries;
    }

    public function setChemistries(Collection $chemistries): static
    {
        foreach ($chemistries as $chemistry) {
            $chemistry->setManufacturer($this);
        }
        $this->chemistries = $chemistries;
        return $this;
    }

    public function addCamera(Camera $camera): static
    {
        $camera->setManufacturer($this);
        $this->cameras->add($camera);
        return $this;
    }

    public function getCameras(): Collection
    {
        return $this->cameras;
    }

    public function setCameras(Collection $cameras): static
    {
        foreach ($cameras as $camera) {
            $camera->setManufacturer($this);
        }
        $this->cameras = $cameras;
        return $this;
    }

    public function addEnlarger(Enlarger $enlarger): static
    {
        $enlarger->setManufacturer($this);
        $this->enlargers->add($enlarger);
        return $this;
    }

    public function getEnlargers(): Collection
    {
        return $this->enlargers;
    }

    public function setEnlargers(Collection $enlargers): static
    {
        foreach ($enlargers as $enlarger) {
            $enlarger->setManufacturer($this);
        }
        $this->enlargers = $enlargers;
        return $this;
    }

    public function addPhotoPaper(PhotoPaper $photoPaper): static
    {
        $photoPaper->setManufacturer($this);
        $this->photoPapers->add($photoPaper);
        return $this;
    }

    public function getPhotoPapers(): Collection
    {
        return $this->photoPapers;
    }

    public function setPhotoPapers(Collection $photoPapers): static
    {
        foreach ($photoPapers as $photoPaper) {
            $photoPaper->setManufacturer($this);
        }
        $this->photoPapers = $photoPapers;
        return $this;
    }

    public function addEnlargerLens(EnlargerLens $enlargerLens): static
    {
        $enlargerLens->setManufacturer($this);
        $this->enlargerLenses->add($enlargerLens);
        return $this;
    }

    public function getEnlargerLenses(): Collection
    {
        return $this->enlargerLenses;
    }

    public function setEnlargerLenses(Collection $enlargerLenses): static
    {
        foreach ($enlargerLenses as $enlargerLens) {
            $enlargerLens->setManufacturer($this);
        }
        $this->enlargerLenses = $enlargerLenses;
        return $this;
    }
}

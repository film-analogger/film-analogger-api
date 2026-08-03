<?php

namespace FilmAnalogger\FilmAnaloggerApi\Doctrine;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use FilmAnalogger\FilmAnaloggerApi\Constant\CatalogStatus;
use FilmAnalogger\FilmAnaloggerApi\Document\Camera;
use FilmAnalogger\FilmAnaloggerApi\Document\Chemistry;
use FilmAnalogger\FilmAnaloggerApi\Document\ChemistryType;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;
use FilmAnalogger\FilmAnaloggerApi\Document\Manufacturer;
use FilmAnalogger\FilmAnaloggerApi\Document\Tag;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Film/Manufacturer/Camera/Tag/Chemistry/ChemistryType are shared catalog
 * data, but a ROLE_user can now contribute personal/pending/rejected entries
 * (see CatalogStatusTrait). A collection listing must only surface "official"
 * entries plus the current user's own, non-official ones — ROLE_data_writer/
 * ROLE_admin see everything unfiltered, since they need the full cross-user
 * review queue (e.g. GET /films?status=pending).
 */
final class CatalogVisibilityExtension implements AggregationCollectionExtensionInterface
{
    private const CATALOG_RESOURCE_CLASSES = [
        Film::class,
        Manufacturer::class,
        Camera::class,
        Tag::class,
        Chemistry::class,
        ChemistryType::class,
    ];

    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = [],
    ): void {
        if (!in_array($resourceClass, self::CATALOG_RESOURCE_CLASSES, true)) {
            return;
        }

        if ($this->security->isGranted(KeycloakRoles::DATA_WRITER)) {
            return;
        }

        $user = $this->security->getUser();
        $identifier = $user?->getUserIdentifier() ?? '__no_user__';

        $aggregationBuilder->match()->addOr(
            $aggregationBuilder->matchExpr()->field('status')->equals(CatalogStatus::OFFICIAL->value),
            $aggregationBuilder->matchExpr()->field('createdBy')->equals($identifier),
        );
    }
}

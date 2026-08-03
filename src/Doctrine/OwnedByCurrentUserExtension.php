<?php

namespace FilmAnalogger\FilmAnaloggerApi\Doctrine;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintSession;
use FilmAnalogger\FilmAnaloggerApi\Document\PrintWork;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * PrintSession/PrintWork are personal darkroom-journal data, not shared
 * catalog data: a collection listing must only return the current user's
 * own records (ROLE_admin sees everything). API Platform's `security`
 * expression on GetCollection has no `object` to check per row, so the
 * scoping has to happen here instead.
 */
final class OwnedByCurrentUserExtension implements AggregationCollectionExtensionInterface
{
    private const OWNED_RESOURCE_CLASSES = [PrintSession::class, PrintWork::class];

    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = [],
    ): void {
        if (!in_array($resourceClass, self::OWNED_RESOURCE_CLASSES, true)) {
            return;
        }

        if ($this->security->isGranted(KeycloakRoles::ADMIN)) {
            return;
        }

        $user = $this->security->getUser();
        $identifier = $user?->getUserIdentifier() ?? '__no_user__';

        $aggregationBuilder->match()->field('createdBy')->equals($identifier);
    }
}

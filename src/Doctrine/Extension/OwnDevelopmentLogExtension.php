<?php

namespace FilmAnalogger\FilmAnaloggerApi\Doctrine\Extension;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use FilmAnalogger\FilmAnaloggerApi\Document\DevelopmentLog;
use FilmAnalogger\FilmAnaloggerApi\Security\KeycloakRoles;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * DevelopmentLog entries are personal: restrict every query to the ones created by the
 * currently authenticated user, unless they hold the admin role.
 */
final class OwnDevelopmentLogExtension implements
    AggregationCollectionExtensionInterface,
    AggregationItemExtensionInterface
{
    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = [],
    ): void {
        $this->restrictToOwner($aggregationBuilder, $resourceClass);
    }

    public function applyToItem(
        Builder $aggregationBuilder,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array &$context = [],
    ): void {
        $this->restrictToOwner($aggregationBuilder, $resourceClass);
    }

    private function restrictToOwner(Builder $aggregationBuilder, string $resourceClass): void
    {
        if (DevelopmentLog::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted(KeycloakRoles::ADMIN)) {
            return;
        }

        $user = $this->security->getUser();

        // No authenticated user: match nothing rather than everything.
        $identifier = $user?->getUserIdentifier() ?? '__no_authenticated_user__';

        $aggregationBuilder->match()->field('createdBy')->equals($identifier);
    }
}

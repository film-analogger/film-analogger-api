<?php

namespace FilmAnalogger\FilmAnaloggerApi\Doctrine\Filter;

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use FilmAnalogger\FilmAnaloggerApi\Document\Film;

// Compares isoShotAt against the referenced Film's nominal sensibility via a
// $lookup, since that comparison can't be expressed as a match against a
// single mapped field. Relies on DevelopmentLog::$film being storeAs: 'id'.
final class PushPullFilter implements FilterInterface
{
    public function apply(
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = [],
    ): void {
        $pulled = $context['filters']['pulled'] ?? null;
        $pushed = $context['filters']['pushed'] ?? null;

        if (null === $pulled && null === $pushed) {
            return;
        }

        $aggregationBuilder
            ->lookup(Film::class)
            ->localField('film')
            ->foreignField('_id')
            ->alias('film_lkup');
        $aggregationBuilder->unwind('$film_lkup');

        if (null !== $pulled && filter_var($pulled, FILTER_VALIDATE_BOOLEAN)) {
            $aggregationBuilder
                ->addFields()
                ->field('isPulled')
                ->lt('$isoShotAt', '$film_lkup.sensibility');
            $aggregationBuilder->match()->field('isPulled')->equals(true);
        }

        if (null !== $pushed && filter_var($pushed, FILTER_VALIDATE_BOOLEAN)) {
            $aggregationBuilder
                ->addFields()
                ->field('isPushed')
                ->gt('$isoShotAt', '$film_lkup.sensibility');
            $aggregationBuilder->match()->field('isPushed')->equals(true);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'pulled' => [
                'property' => null,
                'type' => 'bool',
                'required' => false,
                'description' =>
                    'Filters development logs shot below the film\'s nominal sensibility (isoShotAt < film.sensibility).',
                'schema' => ['type' => 'boolean'],
            ],
            'pushed' => [
                'property' => null,
                'type' => 'bool',
                'required' => false,
                'description' =>
                    'Filters development logs shot above the film\'s nominal sensibility (isoShotAt > film.sensibility).',
                'schema' => ['type' => 'boolean'],
            ],
        ];
    }
}

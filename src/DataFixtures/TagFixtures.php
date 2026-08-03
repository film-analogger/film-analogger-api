<?php

namespace FilmAnalogger\FilmAnaloggerApi\DataFixtures;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use FilmAnalogger\FilmAnaloggerApi\Document\Tag;

class TagFixtures extends Fixture
{
    public const FOGGED = 'tag-fogged';
    public const THIN_NEGATIVES = 'tag-thin-negatives';
    public const DENSE_NEGATIVES = 'tag-dense-negatives';
    public const UNEVEN_DEVELOPMENT = 'tag-uneven-development';
    public const SCRATCHES = 'tag-scratches';
    public const RETICULATION = 'tag-reticulation';

    public function load(ObjectManager $manager): void
    {
        $entities = [];

        foreach (
            $this->getData()
            as [$reference, $name, $description, $primaryColor, $translations]
        ) {
            $tag = new Tag();
            $tag->setName($name);
            $tag->setDescription($description);
            $tag->primaryColor = $primaryColor;

            $manager->persist($tag);
            $this->addReference($reference, $tag);
            $entities[] = [$tag, $translations];
        }

        $manager->flush();

        foreach ($entities as [$tag, $translations]) {
            foreach ($translations as $locale => $translation) {
                $tag->setTranslatableLocale($locale);
                if (isset($translation['description'])) {
                    $tag->setDescription($translation['description']);
                }
                $manager->persist($tag);
            }
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                self::FOGGED,
                'Fogged',
                'The film shows unwanted exposure, often from a light leak.',
                '#E48157',
                [
                    'fr' => [
                        'description' =>
                            'Le film présente une exposition non désirée, souvent due à une fuite de lumière.',
                    ],
                ],
            ],
            [
                self::THIN_NEGATIVES,
                'Thin negatives',
                'Underdeveloped or underexposed negatives lacking density.',
                '#B0B0B0',
                [
                    'fr' => [
                        'description' =>
                            'Négatifs sous-développés ou sous-exposés manquant de densité.',
                    ],
                ],
            ],
            [
                self::DENSE_NEGATIVES,
                'Dense negatives',
                'Overdeveloped or overexposed negatives, hard to print.',
                '#4B1311',
                [
                    'fr' => [
                        'description' =>
                            'Négatifs sur-développés ou sur-exposés, difficiles à tirer.',
                    ],
                ],
            ],
            [
                self::UNEVEN_DEVELOPMENT,
                'Uneven development',
                'Streaks or patches of uneven density, often from poor agitation.',
                '#9B79D8',
                [
                    'fr' => [
                        'description' =>
                            'Stries ou zones de densité inégale, souvent dues à une agitation insuffisante.',
                    ],
                ],
            ],
            [
                self::SCRATCHES,
                'Scratches',
                'Physical scratches on the film surface, often from the reel or a dirty tank.',
                '#0B3C9A',
                [
                    'fr' => [
                        'description' =>
                            'Rayures physiques sur la surface du film, souvent dues à la spire ou à une cuve sale.',
                    ],
                ],
            ],
            [
                self::RETICULATION,
                'Reticulation',
                'A cracked, textured grain pattern caused by a large temperature shock between baths.',
                '#0898D0',
                [
                    'fr' => [
                        'description' =>
                            'Un motif de grain craquelé causé par un choc thermique important entre les bains.',
                    ],
                ],
            ],
        ];
    }
}

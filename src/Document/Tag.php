<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

class Tag implements Translatable
{
    #[ODM\Field(type: 'string')]
    #[Gedmo\Translatable]
    #[Assert\NotBlank]
    private string $name;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[ODM\Field]
    #[Assert\CssColor]
    public ?string $primaryColor = null;
}

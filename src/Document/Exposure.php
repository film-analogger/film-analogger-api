<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Constant\ExposureKind;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperGrade;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

// No #[ApiResource]: an Exposure has no lifecycle of its own — it is only
// ever written in cascade from PrintWork (see PrintWork::$exposures,
// cascade: ['persist'], orphanRemoval: true) and read as a nested part of
// the PrintWork read/write payload.
#[ODM\Document]
class Exposure
{
    #[ODM\Id]
    #[Groups([SerializationGroups::PRINT_READ_GROUP])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: PrintWork::class, inversedBy: 'exposures')]
    #[Assert\NotNull(message: 'Print must be set.')]
    public PrintWork $print;

    #[ODM\Field(type: 'int')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public int $order;

    #[ODM\Field(enumType: ExposureKind::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public ExposureKind $kind;

    #[ODM\Field(type: 'float')]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public float $baseSeconds;

    #[ODM\Field(type: 'int')]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public int $stopOffsetNumerator = 0;

    #[ODM\Field(type: 'int')]
    #[Assert\Positive]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public int $stopOffsetDenominator = 1;

    #[ODM\Field(enumType: PaperGrade::class)]
    #[Assert\NotNull]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public PaperGrade $grade;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public ?string $aperture = null;

    #[ODM\Field(nullable: true)]
    #[
        Groups([
            SerializationGroups::PRINT_READ_GROUP,
            SerializationGroups::PRINT_WRITE_GROUP,
        ]),
    ]
    public ?string $observation = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPrint(): PrintWork
    {
        return $this->print;
    }

    public function setPrint(PrintWork $print): static
    {
        $this->print = $print;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getKind(): ExposureKind
    {
        return $this->kind;
    }

    public function setKind(ExposureKind $kind): static
    {
        $this->kind = $kind;
        return $this;
    }

    public function getBaseSeconds(): float
    {
        return $this->baseSeconds;
    }

    public function setBaseSeconds(float $baseSeconds): static
    {
        $this->baseSeconds = $baseSeconds;
        return $this;
    }

    public function getStopOffsetNumerator(): int
    {
        return $this->stopOffsetNumerator;
    }

    public function setStopOffsetNumerator(int $stopOffsetNumerator): static
    {
        $this->stopOffsetNumerator = $stopOffsetNumerator;
        return $this;
    }

    public function getStopOffsetDenominator(): int
    {
        return $this->stopOffsetDenominator;
    }

    public function setStopOffsetDenominator(int $stopOffsetDenominator): static
    {
        $this->stopOffsetDenominator = $stopOffsetDenominator;
        return $this;
    }

    public function getGrade(): PaperGrade
    {
        return $this->grade;
    }

    public function setGrade(PaperGrade $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getAperture(): ?string
    {
        return $this->aperture;
    }

    public function setAperture(?string $aperture): static
    {
        $this->aperture = $aperture;
        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    /**
     * f-stop printing: adjustments are made in fractions of a stop applied
     * to the base time, not on the lens aperture. E.g. "32s + 1/3"
     * (baseSeconds=32, stopOffsetNumerator=1, stopOffsetDenominator=3)
     * gives 32 x 2^(1/3) =~ 40.3s.
     */
    #[Groups([SerializationGroups::PRINT_READ_GROUP])]
    public function getEffectiveSeconds(): float
    {
        return $this->baseSeconds * 2 ** ($this->stopOffsetNumerator / $this->stopOffsetDenominator);
    }
}

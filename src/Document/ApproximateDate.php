<?php

namespace FilmAnalogger\FilmAnaloggerApi\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use FilmAnalogger\FilmAnaloggerApi\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A date known with variable precision: only the year, the year and month, or a full date.
 */
#[ODM\EmbeddedDocument]
class ApproximateDate
{
    #[ODM\Field]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public int $year;

    #[ODM\Field(nullable: true)]
    #[Assert\Range(min: 1, max: 12)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?int $month = null;

    #[ODM\Field(nullable: true)]
    #[Assert\Range(min: 1, max: 31)]
    #[
        Groups([
            SerializationGroups::DEVELOPMENT_LOG_READ_GROUP,
            SerializationGroups::DEVELOPMENT_LOG_WRITE_GROUP,
        ]),
    ]
    public ?int $day = null;

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): static
    {
        $this->month = $month;
        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(?int $day): static
    {
        $this->day = $day;
        return $this;
    }

    #[Assert\Callback]
    public function validateDayRequiresMonth(ExecutionContextInterface $context): void
    {
        if (null !== $this->day && null === $this->month) {
            $context
                ->buildViolation('A day cannot be set without a month.')
                ->atPath('day')
                ->addViolation();
        }
    }

    /**
     * Human-readable label matching the known precision: "2024", "2024-03" or "2024-03-12".
     */
    #[Groups([SerializationGroups::DEVELOPMENT_LOG_READ_GROUP])]
    public function getLabel(): string
    {
        if (null !== $this->day) {
            return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
        }

        if (null !== $this->month) {
            return sprintf('%04d-%02d', $this->year, $this->month);
        }

        return sprintf('%04d', $this->year);
    }
}

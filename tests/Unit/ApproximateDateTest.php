<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Unit;

use FilmAnalogger\FilmAnaloggerApi\Document\ApproximateDate;
use PHPUnit\Framework\TestCase;

class ApproximateDateTest extends TestCase
{
    private ApproximateDate $approximateDate;

    protected function setUp(): void
    {
        $this->approximateDate = new ApproximateDate();
    }

    public function testGetLabelWithYearOnly(): void
    {
        $this->approximateDate->setYear(2024);

        self::assertSame('2024', $this->approximateDate->getLabel());
    }

    public function testGetLabelWithYearAndMonth(): void
    {
        $this->approximateDate->setYear(2024)->setMonth(3);

        self::assertSame('2024-03', $this->approximateDate->getLabel());
    }

    public function testGetLabelWithFullDate(): void
    {
        $this->approximateDate->setYear(2024)->setMonth(3)->setDay(12);

        self::assertSame('2024-03-12', $this->approximateDate->getLabel());
    }

    public function testFluentInterface(): void
    {
        $result = $this->approximateDate->setYear(2024)->setMonth(6)->setDay(1);

        self::assertSame($this->approximateDate, $result);
        self::assertSame(2024, $this->approximateDate->getYear());
        self::assertSame(6, $this->approximateDate->getMonth());
        self::assertSame(1, $this->approximateDate->getDay());
    }

    public function testDayWithoutMonthIsInvalid(): void
    {
        $this->approximateDate->setYear(2024)->setDay(12);

        $violations = $this->getValidator()->validate($this->approximateDate);

        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'A day cannot be set without a month.',
            $violations[0]->getMessage(),
        );
    }

    public function testYearOnlyIsValid(): void
    {
        $this->approximateDate->setYear(2024);

        $violations = $this->getValidator()->validate($this->approximateDate);

        self::assertCount(0, $violations);
    }

    public function testYearAndMonthIsValid(): void
    {
        $this->approximateDate->setYear(2024)->setMonth(3);

        $violations = $this->getValidator()->validate($this->approximateDate);

        self::assertCount(0, $violations);
    }

    public function testFullDateIsValid(): void
    {
        $this->approximateDate->setYear(2024)->setMonth(3)->setDay(12);

        $violations = $this->getValidator()->validate($this->approximateDate);

        self::assertCount(0, $violations);
    }

    public function testInvalidMonthFails(): void
    {
        $this->approximateDate->setYear(2024)->setMonth(13);

        $violations = $this->getValidator()->validate($this->approximateDate);

        self::assertCount(1, $violations);
    }

    private function getValidator(): \Symfony\Component\Validator\Validator\ValidatorInterface
    {
        return \Symfony\Component\Validator\Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}

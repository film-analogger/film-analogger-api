<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Unit;

use FilmAnalogger\FilmAnaloggerApi\Constant\ExposureKind;
use FilmAnalogger\FilmAnaloggerApi\Constant\PaperGrade;
use FilmAnalogger\FilmAnaloggerApi\Document\Exposure;
use PHPUnit\Framework\TestCase;

class ExposureTest extends TestCase
{
    private Exposure $exposure;

    protected function setUp(): void
    {
        $this->exposure = new Exposure();
        $this->exposure->setKind(ExposureKind::BASE)->setGrade(PaperGrade::G2)->setOrder(1);
    }

    public function testEffectiveSecondsWithNoOffset(): void
    {
        $this->exposure->setBaseSeconds(20.0)->setStopOffsetNumerator(0)->setStopOffsetDenominator(1);

        self::assertSame(20.0, $this->exposure->getEffectiveSeconds());
    }

    public function testEffectiveSecondsWithThirdStopOffset(): void
    {
        // "32s + 1/3" from the spec: 32 x 2^(1/3) =~ 40.3s
        $this->exposure->setBaseSeconds(32.0)->setStopOffsetNumerator(1)->setStopOffsetDenominator(3);

        self::assertEqualsWithDelta(40.3, $this->exposure->getEffectiveSeconds(), 0.05);
    }

    public function testEffectiveSecondsWithNegativeOffset(): void
    {
        // A dodge pulling light: negative offset shortens the effective time.
        $this->exposure->setBaseSeconds(16.0)->setStopOffsetNumerator(-1)->setStopOffsetDenominator(2);

        self::assertEqualsWithDelta(11.31, $this->exposure->getEffectiveSeconds(), 0.01);
    }

    public function testFluentInterface(): void
    {
        $result = $this->exposure
            ->setBaseSeconds(10.0)
            ->setStopOffsetNumerator(1)
            ->setStopOffsetDenominator(2)
            ->setAperture('f/8')
            ->setObservation('pull le public');

        self::assertSame($this->exposure, $result);
        self::assertSame('f/8', $this->exposure->getAperture());
        self::assertSame('pull le public', $this->exposure->getObservation());
    }

    public function testStopOffsetDenominatorMustBePositive(): void
    {
        $this->exposure->setStopOffsetDenominator(0);

        // validateProperty (rather than validate()) so the still-uninitialized
        // `print` relation (out of scope here) isn't touched.
        $violations = $this->getValidator()->validateProperty($this->exposure, 'stopOffsetDenominator');

        self::assertGreaterThanOrEqual(1, count($violations));
    }

    private function getValidator(): \Symfony\Component\Validator\Validator\ValidatorInterface
    {
        return \Symfony\Component\Validator\Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}

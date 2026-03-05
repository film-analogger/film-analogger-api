<?php

namespace FilmAnalogger\FilmAnaloggerApi\Tests\Unit;

use FilmAnalogger\FilmAnaloggerApi\Document\Dilution;
use PHPUnit\Framework\TestCase;

class DilutionTest extends TestCase
{
    private Dilution $dilution;

    protected function setUp(): void
    {
        $this->dilution = new Dilution();
    }

    public function testGetChemistryPartsDefault(): void
    {
        self::assertSame(1, $this->dilution->getChemistryParts());
    }

    public function testSetChemistryParts(): void
    {
        $result = $this->dilution->setChemistryParts(2);

        self::assertSame(2, $this->dilution->getChemistryParts());
        self::assertSame($this->dilution, $result);
    }

    public function testGetWaterPartsDefault(): void
    {
        self::assertSame(0, $this->dilution->getWaterParts());
    }

    public function testSetWaterParts(): void
    {
        $result = $this->dilution->setWaterParts(3);

        self::assertSame(3, $this->dilution->getWaterParts());
        self::assertSame($this->dilution, $result);
    }

    public function testIsOfficialDefault(): void
    {
        self::assertFalse($this->dilution->isOfficial());
    }

    public function testSetOfficial(): void
    {
        $result = $this->dilution->setOfficial(true);

        self::assertTrue($this->dilution->isOfficial());
        self::assertSame($this->dilution, $result);
    }

    public function testGetLabelStock(): void
    {
        self::assertSame('stock', $this->dilution->getLabel());
    }

    public function testGetLabelDiluted(): void
    {
        $this->dilution->setChemistryParts(1)->setWaterParts(1);

        self::assertSame('1+1', $this->dilution->getLabel());
    }

    public function testGetLabelDilutedMultiple(): void
    {
        $this->dilution->setChemistryParts(2)->setWaterParts(3);

        self::assertSame('2+3', $this->dilution->getLabel());
    }

    public function testFluentInterface(): void
    {
        $result = $this->dilution->setChemistryParts(1)->setWaterParts(2)->setOfficial(true);

        self::assertSame($this->dilution, $result);
        self::assertSame(1, $this->dilution->getChemistryParts());
        self::assertSame(2, $this->dilution->getWaterParts());
        self::assertTrue($this->dilution->isOfficial());
    }

    public function testChemistryPartsValidation(): void
    {
        $this->dilution->setChemistryParts(0);
        $violations = $this->getValidator()->validate($this->dilution);
        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'This value should be positive.',
            $violations[0]->getMessage(),
        );
    }

    public function testWaterPartsValidation(): void
    {
        $this->dilution->setWaterParts(-1);
        $violations = $this->getValidator()->validate($this->dilution);
        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'This value should be either positive or zero.',
            $violations[0]->getMessage(),
        );
    }

    public function testValidDilution(): void
    {
        $this->dilution->setChemistryParts(1)->setWaterParts(1);
        $violations = $this->getValidator()->validate($this->dilution);
        self::assertCount(0, $violations);
    }

    private function getValidator(): \Symfony\Component\Validator\Validator\ValidatorInterface
    {
        return \Symfony\Component\Validator\Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}

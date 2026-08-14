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

    public function testGetDescriptionDefault(): void
    {
        self::assertNull($this->dilution->getDescription());
    }

    public function testSetDescription(): void
    {
        $result = $this->dilution->setDescription('1+4 film, 1+9 paper');

        self::assertSame('1+4 film, 1+9 paper', $this->dilution->getDescription());
        self::assertSame($this->dilution, $result);
    }

    public function testIsReusableDefault(): void
    {
        self::assertFalse($this->dilution->isReusable());
    }

    public function testSetReusable(): void
    {
        $result = $this->dilution->setReusable(true);

        self::assertTrue($this->dilution->isReusable());
        self::assertSame($this->dilution, $result);
    }

    public function testIsTopUpRequiredDefault(): void
    {
        self::assertFalse($this->dilution->isTopUpRequired());
    }

    public function testSetTopUpRequired(): void
    {
        $result = $this->dilution->setReusable(true)->setTopUpRequired(true);

        self::assertTrue($this->dilution->isTopUpRequired());
        self::assertSame($this->dilution, $result);
    }

    public function testGetNotesDefault(): void
    {
        self::assertNull($this->dilution->getNotes());
    }

    public function testSetNotes(): void
    {
        $result = $this->dilution->setNotes('Reusable for 8-10 films per litre, add 10% to the time for each film.');

        self::assertSame(
            'Reusable for 8-10 films per litre, add 10% to the time for each film.',
            $this->dilution->getNotes(),
        );
        self::assertSame($this->dilution, $result);
    }

    public function testGetCapacityDefault(): void
    {
        self::assertNull($this->dilution->getCapacity());
    }

    public function testSetCapacity(): void
    {
        $result = $this->dilution->setCapacity('10 films/litre');

        self::assertSame('10 films/litre', $this->dilution->getCapacity());
        self::assertSame($this->dilution, $result);
    }

    public function testGetDilutedShelfLifeDaysDefault(): void
    {
        self::assertNull($this->dilution->getDilutedShelfLifeDays());
    }

    public function testSetDilutedShelfLifeDays(): void
    {
        $result = $this->dilution->setDilutedShelfLifeDays(30);

        self::assertSame(30, $this->dilution->getDilutedShelfLifeDays());
        self::assertSame($this->dilution, $result);
    }

    public function testDilutedShelfLifeDaysValidation(): void
    {
        $this->dilution->setDilutedShelfLifeDays(-1);
        $violations = $this->getValidator()->validate($this->dilution);
        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'This value should be either positive or zero.',
            $violations[0]->getMessage(),
        );
    }

    public function testTopUpRequiredWithoutReusableFails(): void
    {
        $this->dilution->setTopUpRequired(true);
        $violations = $this->getValidator()->validate($this->dilution);
        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'topUpRequired can only be set when reusable is true.',
            $violations[0]->getMessage(),
        );
    }

    public function testTopUpRequiredWithReusableIsValid(): void
    {
        $this->dilution->setReusable(true)->setTopUpRequired(true);
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

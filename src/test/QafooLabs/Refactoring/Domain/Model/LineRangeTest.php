<?php

namespace QafooLabs\Refactoring\Domain\Model;

class LineRangeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromSingleLine()
    {
        $range = LineRange::fromSingleLine(1);

        $this->assertEquals(1, $range->getStart());
        $this->assertEquals(1, $range->getEnd());

        $this->assertTrue($range->isInRange(1));
        $this->assertFalse($range->isInRange(2));
    }

    public function testCreateFromString()
    {
        $range = LineRange::fromString("1-4");

        $this->assertEquals(1, $range->getStart());
        $this->assertEquals(4, $range->getEnd());

        $this->assertTrue($range->isInRange(1));
        $this->assertFalse($range->isInRange(5));
    }

    public function testCreateFromLines()
    {
        $range = LineRange::fromLines(1, 4);

        $this->assertEquals(1, $range->getStart());
        $this->assertEquals(4, $range->getEnd());

        $this->assertTrue($range->isInRange(1));
        $this->assertFalse($range->isInRange(5));
    }

    public function testSliceCode()
    {
        $range = LineRange::fromLines(2, 5);

        $code = "line1\n"
            . "line2\n"
            . "line3\n"
            . "line4\n"
            . "line5\n"
            . "line6\n";

        $this->assertEquals(
            array('line2', 'line3', 'line4', 'line5'),
            $range->sliceCode($code)
        );
    }
}

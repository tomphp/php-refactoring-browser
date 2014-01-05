<?php

namespace QafooLabs\Refactoring\Application;

use QafooLabs\Refactoring\Domain\Model\File;
use QafooLabs\Refactoring\Domain\Model\LineRange;

class ExtractMethodObjectTest extends \PHPUnit_Framework_TestCase
{
    private $refactoring;

    private $variableScanner;

    private $codeAnalysis;

    private $editor;

    protected function setUp()
    {
        $this->variableScanner = $this->getMock('QafooLabs\Refactoring\Domain\Services\VariableScanner');

        $this->codeAnalysis = $this->getMock('QafooLabs\Refactoring\Domain\Services\CodeAnalysis');

        $this->editor = $this->getMock('QafooLabs\Refactoring\Domain\Services\Editor');

        $this->refactoring = new ExtractMethodObject(
            $this->variableScanner,
            $this->codeAnalysis,
            $this->editor
        );
    }

    public function testThrowsIfNotInsideMethod()
    {
        $this->setMethodRange();

        $this->codeAnalysis
             ->expects($this->once())
             ->method('isInsideMethod')
             ->will($this->returnValue(false));

        $this->setExpectedException('QafooLabs\Refactoring\Domain\Model\RefactoringException');

        $this->refactoring->refactor(new File('', ''), LineRange::fromSingleLine(1), '', '');
    }

    public function testFindsMethodRangeInGivenFile()
    {
        $file = new File('xxx', 'yyy');
        $extractRange = LineRange::fromLines(5, 45);

        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $this->codeAnalysis
             ->expects($this->once())
             ->method('findMethodRange')
             ->with($this->equalTo($file), $this->equalTo($extractRange));

        $this->refactoring->refactor($file, $extractRange, '', '');
    }

    public function testExtractRangeIsExtractedFromTheCode()
    {
        $code = 'xyz';
        $file = new File('', $code);

        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $extractRange = $this->getMock('QafooLabs\Refactoring\Domain\Model\LineRange');

        $extractRange->expects($this->once())
                     ->method('sliceCode')
                     ->with($this->equalTo($code));

        $this->refactoring->refactor($file, $extractRange, '', '');
    }

    public function testScansForExtractVariables()
    {
        $file = new File('xxx', 'yyy');
        $extractRange = LineRange::fromLines(5, 45);

        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $this->variableScanner
             ->expects($this->at(0))
             ->method('scanForVariables')
             ->with($this->equalTo($file), $this->equalTo($extractRange));

        $this->refactoring->refactor($file, $extractRange, '', '');
    }

    public function testScansForMethodVariables()
    {
        $file = new File('xxx', 'yyy');
        $methodRange = LineRange::fromLines(5, 8);

        $this->setRangeIsInsideMethod();

        $this->codeAnalysis
             ->expects($this->once())
             ->method('findMethodRange')
             ->will($this->returnValue($methodRange));

        $this->variableScanner
             ->expects($this->at(0))
             ->method('scanForVariables');

        $this->variableScanner
             ->expects($this->at(1))
             ->method('scanForVariables')
             ->with($this->equalTo($file), $this->equalTo($methodRange));

        $this->refactoring->refactor($file, LineRange::fromSingleLine(5), '', '');
    }

    public function testItOpensAToEdit()
    {
        $file = new File('edit-file', '');

        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $this->editor
             ->expects($this->once())
             ->method('openBuffer')
             ->with($this->equalTo($file));

        $this->refactoring->refactor(
            $file,
            LineRange::fromSingleLine(5),
            $filename,
            ''
        );
    }

    public function testItOpensABufferForTheNewClass()
    {
        $filename = 'newfile';

        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $this->editor
             ->expects($this->once())
             ->method('openBuffer')
             ->with($this->equalTo(new File($filename, '')));

        $this->refactoring->refactor(
            new File('', ''),
            LineRange::fromSingleLine(5),
            $filename,
            ''
        );
    }

    public function testItSaves()
    {
        $this->setRangeIsInsideMethod();
        $this->setMethodRange();

        $this->editor
             ->expects($this->once())
             ->method('save');

         $this->refactoring->refactor(new File('', ''), LineRange::fromSingleLine(5), '', '');
    }

    private function setMethodRange()
    {
        $this->codeAnalysis
             ->expects($this->any())
             ->method('findMethodRange')
             ->will($this->returnValue(LineRange::fromSingleLine(5)));
    }

    private function setRangeIsInsideMethod()
    {
        $this->codeAnalysis
             ->expects($this->any())
             ->method('isInsideMethod')
             ->will($this->returnValue(true));
    }
}

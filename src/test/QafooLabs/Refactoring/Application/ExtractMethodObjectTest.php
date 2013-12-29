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
        $this->codeAnalysis
             ->expects($this->once())
             ->method('isInsideMethod')
             ->will($this->returnValue(false));

        $this->setExpectedException('QafooLabs\Refactoring\Domain\Model\RefactoringException');

        $this->refactoring->refactor(new File('', ''), LineRange::fromSingleLine(1), '', '');
    }
}

<?php

namespace QafooLabs\Refactoring\Application;

use QafooLabs\Refactoring\Domain\Model\LineRange;
use QafooLabs\Refactoring\Domain\Model\File;
use QafooLabs\Refactoring\Domain\Model\MethodSignature;
use QafooLabs\Refactoring\Domain\Model\EditingSession;
use QafooLabs\Refactoring\Domain\Model\RefactoringException;

use QafooLabs\Refactoring\Domain\Services\VariableScanner;
use QafooLabs\Refactoring\Domain\Services\CodeAnalysis;
use QafooLabs\Refactoring\Domain\Services\Editor;
use QafooLabs\Refactoring\Domain\Model\LineCollection;
use QafooLabs\Refactoring\Domain\Model\EditingAction\AddMethod;
use QafooLabs\Refactoring\Domain\Model\EditingAction\ReplaceWithMethodCall;

/**
 * Extract Method Refactoring
 */
class ExtractMethod extends SingleFileRefactoring
{
    /**
     * @var LineRange
     */
    protected $extractRange;

    /**
     * @var MethodSignature
     */
    protected $newMethod;

    /**
     * @var string
     */
    protected $newMethodName;

    public function setFile(File $file)
    {
        $this->file = $file;
    }

    public function setExtractRange(LineRange $extractRange)
    {
        $this->extractRange = $extractRange;
    }

    public function setNewMethodName($name)
    {
        $this->newMethodName = $name;
    }

    /**
     * @param string $newMethodName
     */
    public function refactor()
    {
        $this->assertIsInsideMethod();

        $this->createNewMethodSignature();

        $this->startEditingSession();
        $this->replaceCodeWithMethodCall();
        $this->addNewMethod();
        $this->completeEditingSession();
    }

    protected function assertIsInsideMethod()
    {
        if ( ! $this->codeAnalysis->isInsideMethod($this->file, $this->extractRange)) {
            throw RefactoringException::rangeIsNotInsideMethod($this->extractRange);
        }
    }

    protected function createNewMethodSignature()
    {
        $extractVariables = $this->variableScanner
                                 ->scanForVariables($this->file, $this->extractRange);
        $methodVariables = $this->variableScanner
                                ->scanForVariables($this->file, $this->findMethodRange());

        $isStatic = $this->codeAnalysis
                         ->isMethodStatic($this->file, $this->extractRange);

        $methodFlags = ($isStatic ? MethodSignature::IS_STATIC : 0);

        $methodFlags |= $this->getMethodAccessSpecifier();

        $this->newMethod = new MethodSignature(
            $this->newMethodName,
            $methodFlags,
            $methodVariables->variablesFromSelectionUsedBefore($extractVariables),
            $methodVariables->variablesFromSelectionUsedAfter($extractVariables)
        );
    }

    /**
     * @return int
     */
    protected function getMethodAccessSpecifier()
    {
        return MethodSignature::IS_PRIVATE;
    }

    protected function addNewMethod()
    {
        $this->session->addEdit(new AddMethod(
            $this->findMethodRange()->getEnd(),
            $this->newMethod,
            $this->getSelectedCode()
        ));
    }

    protected function replaceCodeWithMethodCall()
    {
        $this->session->addEdit(new ReplaceWithMethodCall(
            $this->extractRange,
            $this->newMethod
        ));
    }

    protected function findMethodRange()
    {
        return $this->codeAnalysis->findMethodRange($this->file, $this->extractRange);
    }

    protected function getSelectedCode()
    {
        return LineCollection::createFromArray(
            $this->extractRange->sliceCode($this->file->getCode())
        );
    }
}

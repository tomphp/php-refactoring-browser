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

/**
 * Extract Method Refactoring
 */
class ExtractMethodObject
{
    const DEFAULT_METHOD_NAME = 'invoke';

    /**
     * @var VariableScanner
     */
    private $variableScanner;

    /**
     * @var CodeAnalysis
     */
    private $codeAnalysis;

    /**
     * @var Editor
     */
    private $editor;

    private $file;
    private $extractRange;
    private $newFileName;
    private $newClassName;

    public function __construct(VariableScanner $variableScanner, CodeAnalysis $codeAnalysis, Editor $editor)
    {
        $this->variableScanner = $variableScanner;
        $this->codeAnalysis = $codeAnalysis;
        $this->editor = $editor;
    }

    /**
     * @param string $newFileName
     * @param string $newClassName
     */
    public function refactor(File $file, LineRange $extractRange, $newFileName, $newClassName)
    {
        $this->file         = $file;
        $this->extractRange = $extractRange;
        $this->newFileName  = $newFileName;
        $this->newClassName = $this->newClassName;

        $this->assertSelectedRangeIsInsideMethod();

        $methodRange = $this->codeAnalysis->findMethodRange($this->file, $this->extractRange);
        $selectedCode = $this->extractRange->sliceCode($this->file->getCode());

        $extractVariables = $this->variableScanner->scanForVariables($this->file, $this->extractRange);
        $methodVariables = $this->variableScanner->scanForVariables($this->file, $methodRange);

        $newMethod = new MethodSignature(
            self::DEFAULT_METHOD_NAME,
            MethodSignature::IS_PUBLIC,
            $methodVariables->variablesFromSelectionUsedBefore($extractVariables),
            $methodVariables->variablesFromSelectionUsedAfter($extractVariables)
        );

        $this->replaceCodeWithCall($file, $newClassName);
        $this->writeNewClass($file, $newClassName, $newFileName, $newMethod, $selectedCode);

        $this->editor->save();
    }

    private function assertSelectedRangeIsInsideMethod()
    {
        if ( ! $this->codeAnalysis->isInsideMethod($this->file, $this->extractRange)) {
            throw RefactoringException::rangeIsNotInsideMethod($this->extractRange);
        }
    }

    private function writeNewClass($file, $newClassName, $newFileName, $newMethod, $selectedCode)
    {
        $buffer = $this->editor->openBuffer(new File($newFileName, ''));

        $buffer->append(0, array(
            '<?php',
            '',
            'class ' . $newClassName,
            ' {',
        ));

        $session = new EditingSession($buffer);
        $session->addMethod(0, $newMethod, $selectedCode);

        $buffer->append(0, array('}'));
    }

    private function replaceCodeWithCall($file, $newClassName)
    {
        $buffer = $this->editor->openBuffer($file);
        $buffer->replace($this->extractRange, array(
            '        $object = new ' . $newClassName . '();',
            '        $object->invoke();'
        ));

    }

}

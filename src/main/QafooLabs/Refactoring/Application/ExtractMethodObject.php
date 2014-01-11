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
use QafooLabs\Refactoring\Domain\Model\EditingAction\AddMethod;

/**
 * Extract Method Refactoring
 */
class ExtractMethodObject extends ExtractMethod
{
    const DEFAULT_METHOD_NAME = 'invoke';

    /**
     * @var string
     */
    private $newFileName;

    /**
     * @var string
     */
    private $newClassName;

    /**
     * @param string $name
     */
    public function setNewFileName($name)
    {
        $this->newFileName = $name;
    }

    public function setNewClassName($name)
    {
        $this->newClassName = $name;
    }

    public function refactor()
    {
        $this->assertIsInsideMethod();

        $this->setNewMethodName(self::DEFAULT_METHOD_NAME);
        $this->createNewMethodSignature();

        $this->startEditingSession();

        $this->replaceCodeWithClassMethodCall();

        $this->writeNewClass();

        $this->completeEditingSession();
    }

    /**
     * @return int
     */
    protected function getMethodAccessSpecifier()
    {
        return MethodSignature::IS_PUBLIC;
    }

    private function writeNewClass()
    {
        $buffer = $this->editor->openBuffer(new File($this->newFileName, ''));

        // Mixture of direct buffer edits and EditingSession here is not
        // ideal. Really should all be done via EditingSession.

        $buffer->append(0, array(
            '<?php',
            '',
            'class ' . $this->newClassName,
            '{',
        ));

        $session = new EditingSession($buffer);

        $session->addEdit(new AddMethod(0, $this->newMethod, $this->getSelectedCode()));

        $session->performEdits();

        $buffer->append(0, array('}'));
    }

    private function replaceCodeWithClassMethodCall()
    {
        // Should be done via and EditingAction

        $buffer = $this->editor->openBuffer($this->file);

        $buffer->replace($this->extractRange, array(
            '        $object = new ' . $this->newClassName . '();',
            '        $object->' . $this->newMethodName . '();'
        ));
    }
}

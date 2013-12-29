<?php
/**
 * Qafoo PHP Refactoring Browser
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace QafooLabs\Refactoring\Adapters\Symfony\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use QafooLabs\Refactoring\Application\ExtractMethodObject;
use QafooLabs\Refactoring\Adapters\PHPParser\ParserVariableScanner;
use QafooLabs\Refactoring\Adapters\TokenReflection\StaticCodeAnalysis;
use QafooLabs\Refactoring\Adapters\Patches\PatchEditor;
use QafooLabs\Refactoring\Adapters\Symfony\OutputPatchCommand;

use QafooLabs\Refactoring\Domain\Model\LineRange;
use QafooLabs\Refactoring\Domain\Model\File;

/**
 * Symfony Adapter to execute the Extract Method Object Refactoring
 */
class ExtractMethodObjectCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('extract-method-object')
            ->setDescription('Extract a list of statements into a new class.')
            ->addArgument('file', InputArgument::REQUIRED, 'File that contains list of statements to extract')
            ->addArgument('range', InputArgument::REQUIRED, 'Line Range of statements that should be extracted.')
            ->addArgument('newfile', InputArgument::REQUIRED, 'The name of the file to store the new class in.')
            ->addArgument('newclass', InputArgument::REQUIRED, 'The name of the new class.')
            ->setHelp(<<<HELP
Extract a range of lines from one method into its own method in a newly created class.
This refactoring is usually used during cleanup of code into single units.

This refactoring automatically detects all necessary inputs and outputs from the
function and generates the argument list and return statement accordingly.

<comment>Operations:</comment>

1. Create a new class with a method containing the selected code.
2. Add a return statement with all variables necessary to make caller work.
3. Pass all arguments to make the method work.

<comment>Pre-Conditions:</comment>

1. Selected code is inside a single method.
2. New class does not exist (NOT YET CHECKED).

<comment>Usage:</comment>

    <info>php refactor.phar extract-method-object file.php 10-16 newfile.php NewClassName</info>

Will extract lines <info>10-16</info> from <info>file.php</info> into a new class
called <info>NewClassName</info> store in the file <info>newfile.php</info>.
HELP
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $refactoring = new ExtractMethodObject(
            new ParserVariableScanner(),
            new StaticCodeAnalysis(),
            new PatchEditor(new OutputPatchCommand($output))
        );

        $refactoring->refactor(
            File::createFromPath($input->getArgument('file'), getcwd()),
            LineRange::fromString($input->getArgument('range')),
            $input->getArgument('newfile'),
            $input->getArgument('newclass')
        );
    }
}

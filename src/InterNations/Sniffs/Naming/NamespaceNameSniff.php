<?php
namespace InterNations\Sniffs\Naming;

// @codingStandardsIgnoreFile
require_once __DIR__ . '/../NamespaceSniffTrait.php';

use InterNations\Sniffs\NamespaceSniffTrait;
use PHP_CodeSniffer_File as CodeSnifferFile;
use PHP_CodeSniffer_Sniff as CodeSnifferSniff;

class NamespaceNameSniff implements CodeSnifferSniff
{
    use NamespaceSniffTrait;

    public function register()
    {
        return [T_NAMESPACE];
    }

    public function process(CodeSnifferFile $file, $stackPtr)
    {
        list(, , $fqNs) = static::getNamespace($stackPtr, $file);

        $declarationPtr = $file->findNext([T_CLASS, T_INTERFACE, T_TRAIT], $stackPtr + 1);
        $symbolNamePtr = $file->findNext(T_STRING, $declarationPtr + 1);
        $symbol = $file->getTokens()[$declarationPtr]['content'];
        $namespace = $fqNs . '\\' . $file->getTokens()[$symbolNamePtr]['content'];

        $expectedPath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace, $namespaceLength) . '.php';
        $fileName = $file->getFilename();

        // PSR-1
        if (strrpos($fileName, $expectedPath) === strlen($fileName) - strlen($expectedPath)) {
            return;
        }

        // PSR-4
        $sourceDirectory = DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        if (strpos($fileName, $sourceDirectory) !== false) {
            $path = substr($fileName, strpos($fileName, $sourceDirectory) + strlen($sourceDirectory));
            $partialNamespace = implode(
                '\\',
                array_slice(explode('\\', $namespace), substr_count($path, DIRECTORY_SEPARATOR))
            );

            if (strrpos($namespace, $partialNamespace) + strlen($partialNamespace) === strlen($namespace)) {
                return;
            }
        }

        $file->addError(
            sprintf(
                'Namespace of %s "%s" does not match file "%s"',
                $symbol,
                $namespace,
                implode(
                    DIRECTORY_SEPARATOR,
                    array_slice(explode(DIRECTORY_SEPARATOR, $fileName), - $namespaceLength - 1)
                )
            ),
            $stackPtr,
            'InvalidNamespaceName'
        );
    }
}

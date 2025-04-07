<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
;
return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setIndent("\t")
    ->setLineEnding("\n")
    ->setFinder($finder)
    ;

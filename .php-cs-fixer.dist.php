<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/benchmarks', __DIR__ . '/packages'])
    ->name(['*.php', '*.phpt']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS3x0' => true,
        '@PHP8x4Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => ['elements' => ['property' => 'one', 'method' => 'one']],
        'blank_line_before_statement' => ['statements' => ['if', 'return', 'try']],
    ])
    ->setIndent("\t")
    ->setLineEnding("\n")
    ->setFinder($finder);

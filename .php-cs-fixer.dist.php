<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/benchmarks', __DIR__ . '/packages'])
    ->name(['*.php', '*.phpt']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => ['elements' => ['property' => 'one', 'method' => 'one']],
        'blank_line_before_statement' => ['statements' => ['if', 'return', 'try']],
        'single_line_empty_body' => false,
    ])
    ->setIndent("\t")
    ->setLineEnding("\n")
    ->setFinder($finder);

<?php

$paths = array_filter(
    [__DIR__.'/src', __DIR__.'/tests', __DIR__.'/examples'],
    'is_dir',
);

$finder = (new PhpCsFixer\Finder())
    ->in($paths)
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP84Migration' => true,
        'declare_strict_types' => false,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'phpdoc_to_comment' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');

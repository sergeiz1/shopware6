<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(__DIR__ . '/src');

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,

        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,

        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,

        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
    ])
    ->setFinder($finder);

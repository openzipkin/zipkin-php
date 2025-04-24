<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests')
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules(
        [
            'nullable_type_declaration_for_default_null_value' => true,
        ]
    )
    ->setFinder($finder)
;
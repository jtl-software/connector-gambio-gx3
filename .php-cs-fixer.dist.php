<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude(__DIR__ . '/vendor')
    ->in([__DIR__ . '/src', __DIR__ . '/install'])
    ->name('*.php')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setLineEnding("\n")
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => true
    ])
    ->setFinder($finder)
;
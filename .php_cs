<?php

$finder = \Symfony\CS\Finder\DefaultFinder::create()
    ->exclude(['tests', 'vendor', 'storage', 'resources', 'public', 'bootstrap/cache'])
    ->in(__DIR__);

return Symfony\CS\Config\Config::create()
    ->setUsingLinter(false)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'namespace_no_leading_whitespace',
        'short_array_syntax',
        'phpdoc_no_empty_return',
        'phpdoc_inline_tag',
        'phpdoc_no_access',
        'phpdoc_scalar',
        'phpdoc_var_without_name',
        'self_accessor',
        'single_blank_line_before_namespace',
        'spaces_cast',
        'phpdoc_short_description',
        'no_empty_lines_after_phpdocs',
        'short_echo_tag',
        'phpdoc_order',
        'whitespacy_lines'
    ])
    ->finder($finder);

<?php

$header = <<<'EOF'
This file is part of the Guestbook application.

(c) Your Company Name

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
  ->setRules([
    '@PHP80Migration:risky' => true,
    '@PHP81Migration' => true,
    '@PSR12' => true,
    '@PSR12:risky' => true,
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
    'not_operator_with_successor_space' => true,
    'trailing_comma_in_multiline' => true,
    'phpdoc_scalar' => true,
    'unary_operator_spaces' => true,
    'binary_operator_spaces' => true,
    'blank_line_before_statement' => true,
    'cast_spaces' => true,
    'declare_equal_normalize' => true,
    'function_typehint_space' => true,
    'single_trait_insert_per_statement' => true,
    'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']], // one may also override the defaults
    'header_comment' => ['header' => $header],
  ])
  ->setFinder(
    PhpCsFixer\Finder::create()
      ->exclude('tests')
      ->in(__DIR__ . '/src')
  )
  ->setUsingCache(true);

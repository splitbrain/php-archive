<?php

/**
 * Doctum configuration for the API documentation of the public library interface
 */

use Doctum\Doctum;
use Doctum\Parser\Filter\PublicFilter;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/src');

$doctum = new Doctum($iterator, [
    'title' => 'PHPArchive',
    'build_dir' => __DIR__ . '/docs',
    'cache_dir' => sys_get_temp_dir() . '/php-archive-doctum',
]);
$doctum['filter'] = function () {
    return new PublicFilter();
};

return $doctum;

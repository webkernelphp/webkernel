<?php declare(strict_types=1);
/** @var WebkernelToolRunner $runner */

require __DIR__ . '/clear.php';

$runner->artisan('filament:optimize');
$runner->artisan('icons:cache');

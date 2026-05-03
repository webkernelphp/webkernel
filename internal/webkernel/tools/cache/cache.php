<?php declare(strict_types=1);
/** @var WebkernelToolRunner $runner */

require __DIR__ . '/clear.php';

$runner->artisan('config:cache');
$runner->artisan('route:cache');
$runner->artisan('view:cache');
$runner->artisan('event:cache');
$runner->artisan('optimize');
$runner->artisan('filament:cache-components');
$runner->artisan('filament:optimize');
$runner->artisan('icons:cache');
$runner->artisan('queue:restart');

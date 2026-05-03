<?php declare(strict_types=1);
/** @var WebkernelToolRunner $runner */

$runner->artisan('cache:clear');
$runner->artisan('config:clear');
$runner->artisan('route:clear');
$runner->artisan('view:clear');
$runner->artisan('event:clear');
$runner->artisan('clear-compiled');
$runner->artisan('optimize:clear');
$runner->artisan('schedule:clear-cache');
$runner->artisan('queue:clear');

// Filament (always present in Webkernel)
$runner->artisan('filament:clear-cached-components');
$runner->artisan('filament:optimize-clear');
$runner->artisan('icons:clear');

// Debugbar (dev only - skip gracefully if not installed)
if (class_exists('Barryvdh\Debugbar\ServiceProvider')) {
    $runner->artisan('debugbar:clear');
}

// Composer autoload
$composer = trim((string)(getenv('COMPOSER_BINARY') ?: 'composer'));
WebkernelToolRunner::proc($composer, '', 'dump-autoload', []);

<?php declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Paths bootstrap loader
|--------------------------------------------------------------------------
|
| This file MUST only orchestrate sub-files.
| No constant definitions here.
|
*/

foreach (glob(__DIR__ . '/paths/0*-paths-*.php') as $file) {
    require_once $file;
}

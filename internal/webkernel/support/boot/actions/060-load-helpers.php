<?php declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Platform helpers loader
|--------------------------------------------------------------------------
| This file MUST only orchestrate sub-files.
| No constant definitions here.
*/

foreach (glob(__DIR__ . '/../platform-helpers/0*-*.php') as $file) {
    require_once $file;
}

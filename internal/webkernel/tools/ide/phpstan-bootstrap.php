<?php declare(strict_types=1);
// Locate and load call-tools.php walking up from this file
(static function (): void {
    for ($d = __DIR__; $d !== dirname($d); $d = dirname($d)) {
        $f = $d . '/call-tools.php';
        if (file_exists($f) && str_ends_with($f, 'webkernel/call-tools.php')) {
            require_once $f;
            WebkernelToolRunner::loadAutoload();
            return;
        }
    }
    fwrite(STDERR, '[phpstan-bootstrap] call-tools.php not found.' . PHP_EOL);
    exit(1);
})();

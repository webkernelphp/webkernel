<?php declare(strict_types=1);
/** @var WebkernelToolRunner $runner */
/** @var array<string>       $args   */
/** @phpstan-constant BASE_PATH string */

(static function (): void {
    $dirs = ['node_modules', 'packages'];
    foreach ($dirs as $dir) {
        $path = BASE_PATH . '/' . $dir . '/.gitkeep';
        if (!file_exists($path)) {
            @mkdir(dirname($path), 0o2775, true);
            @touch($path);
            echo '[init-gitkeeps] Created: ' . $path . PHP_EOL;
        } else {
            echo '[init-gitkeeps] Already exists: ' . $path . PHP_EOL;
        }
    }
})();

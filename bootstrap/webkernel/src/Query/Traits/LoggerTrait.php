<?php declare(strict_types=1);
namespace Webkernel\Query\Traits;

use Illuminate\Container\Container;

trait LoggerTrait
{
    private function warn(string $message): void   { $this->log($message, 'warning'); }
    private function emitNotice(string $message): void { $this->log($message, 'notice'); }

    private function log(string $message, string $level = 'info'): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "[Webkernel] {$message}\n");
            return;
        }
        if (class_exists(Container::class, false) && ($c = Container::getInstance()) && $c->bound('log')) {
            logger()->{$level}("[Webkernel] {$message}");
            return;
        }
        error_log("[Webkernel] {$message}");
    }
}

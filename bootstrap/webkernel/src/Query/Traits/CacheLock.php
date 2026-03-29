<?php declare(strict_types=1);
namespace Webkernel\Query\Traits;

trait CacheLock
{
    private function withLock(string $lockPath, callable $fn): void
    {
        $this->ensureDirectory(dirname($lockPath));
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            $fn();
            return;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            flock($lock, LOCK_EX);
            flock($lock, LOCK_UN);
            fclose($lock);
            return;
        }
        try {
            $fn();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

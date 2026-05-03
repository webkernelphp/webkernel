<?php declare(strict_types=1);
namespace Webkernel\Query\Traits;

trait CacheLock
{
    private int $lockTimeout = 300;
    private int $staleLockThreshold = 3600;

    private function withLock(string $lockPath, callable $fn): void
    {
        $this->ensureDirectory(dirname($lockPath));
        $this->cleanStaleLock($lockPath);

        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            $fn();
            return;
        }

        $acquired = $this->acquireWithTimeout($handle);

        if (!$acquired) {
            @fclose($handle);
            return;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode([
            'pid'       => getmypid(),
            'timestamp' => time(),
            'hostname'  => (string) gethostname(),
        ], JSON_THROW_ON_ERROR));
        fflush($handle);

        try {
            $fn();
        } finally {
            flock($handle, LOCK_UN);
            @fclose($handle);
            @unlink($lockPath);
        }
    }

    private function acquireWithTimeout(mixed $handle): bool
    {
        if (@flock($handle, LOCK_EX | LOCK_NB)) {
            return true;
        }

        $deadline = time() + $this->lockTimeout;
        while (time() < $deadline) {
            usleep(100_000);
            if (@flock($handle, LOCK_EX | LOCK_NB)) {
                return true;
            }
        }

        return false;
    }

    private function cleanStaleLock(string $lockPath): void
    {
        if (!file_exists($lockPath)) {
            return;
        }

        $age = time() - (int) filemtime($lockPath);
        if ($age < $this->staleLockThreshold) {
            return;
        }

        $content = @file_get_contents($lockPath);
        if ($content === false) {
            @unlink($lockPath);
            return;
        }

        $data = @json_decode($content, true);
        $pid  = isset($data['pid']) ? (int) $data['pid'] : null;

        if ($pid === null || !$this->isProcessRunning($pid)) {
            @unlink($lockPath);
        }
    }

    private function isProcessRunning(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $out = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
            return $out !== null && str_contains($out, (string) $pid);
        }

        return @posix_kill($pid, 0);
    }
}

<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Dto;

/**
 * Operation execution context.
 *
 * Tracks the state of a do() operation from start to completion.
 * Supports rollback of file system operations (backup/swap).
 */
final class OperationContext
{
    private ?string $backupPath = null;
    private ?string $targetPath = null;
    private array $rateLimit = [];

    public function __construct(
        public readonly bool   $success,
        public readonly string $message,
        public readonly array  $releases = [],
        public readonly array  $steps = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $releases = [], array $steps = []): self
    {
        return new self(
            success: true,
            message: 'Operation completed successfully',
            releases: $releases,
            steps: $steps,
        );
    }

    public static function failure(string $error, array $steps = []): self
    {
        return new self(
            success: false,
            message: 'Operation failed',
            error: $error,
            steps: $steps,
        );
    }

    /**
     * Set backup path for potential rollback.
     *
     * @internal
     */
    public function setBackupPaths(string $backupPath, string $targetPath): self
    {
        $this->backupPath = $backupPath;
        $this->targetPath = $targetPath;
        return $this;
    }

    /**
     * Get rate limit info from API response.
     *
     * @return array{remaining: ?int, reset: ?int}
     */
    public function rateLimit(): array
    {
        return $this->rateLimit ?: ['remaining' => null, 'reset' => null];
    }

    /**
     * Set rate limit info from API response headers.
     *
     * @internal
     */
    public function setRateLimit(array $limit): self
    {
        $this->rateLimit = $limit;
        return $this;
    }

    /**
     * Rollback to backed-up version.
     *
     * Restores the previous state if something went wrong.
     *
     * Usage:
     *   $result = webkernel()->do()->from(...)->backup(...)->extract()->swap()->run();
     *   if (!$result->success) {
     *       $result->rollback();
     *   }
     */
    public function rollback(): void
    {
        if (!$this->backupPath || !is_dir($this->backupPath)) {
            throw new \RuntimeException("No backup available for rollback");
        }

        if (!$this->targetPath) {
            throw new \RuntimeException('Target path not set for rollback');
        }

        // Remove current broken state
        if (is_dir($this->targetPath)) {
            $this->recursiveRemove($this->targetPath);
        }

        // Restore from backup
        if (!rename($this->backupPath, $this->targetPath)) {
            throw new \RuntimeException("Failed to rollback to backup: $this->backupPath");
        }

        \Illuminate\Support\Facades\Log::info("[Operation] Rollback complete: restored from $this->backupPath");
    }

    /**
     * Recursively remove directory and contents.
     *
     * @internal
     */
    private function recursiveRemove(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->recursiveRemove($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($path);
    }
}

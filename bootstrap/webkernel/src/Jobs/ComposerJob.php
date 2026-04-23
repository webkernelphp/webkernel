<?php

declare(strict_types=1);

namespace Webkernel\Jobs;

use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;

class ComposerJob extends Job
{
    public const STRATEGY_UPDATE_SINGLE = 'update-single';
    public const STRATEGY_UPDATE_ALL = 'update-all';
    public const STRATEGY_REQUIRE = 'require';

    protected ?int $timeout = 600;

    public function __construct(
        private string $taskId,
        private string $strategy,
        private ?string $packageName = null,
        private ?string $version = null,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $task = WebkernelBackgroundTask::find($this->taskId);
        if (!$task) {
            return;
        }

        $task->markRunning();

        try {
            $composerBinary = config('dependency-manager.composer_binary', 'composer');
            $result = $this->executeComposerCommand($composerBinary, 0);

            if (!$result->successful) {
                // If dependency conflict, retry with --with-all-dependencies for single-package updates
                if ($this->strategy === self::STRATEGY_REQUIRE && str_contains($result->error, 'could not be resolved')) {
                    $result = $this->executeComposerCommand($composerBinary, 1);
                }
            }

            if (!$result->successful) {
                $task->markFailed($result->error);
                return;
            }

            try {
                if (class_exists('Webkernel\\BackOffice\\System\\Presentation\\Resources\\DependencyManager\\Services\\ComposerService')) {
                    app('Webkernel\\BackOffice\\System\\Presentation\\Resources\\DependencyManager\\Services\\ComposerService')?->clearCache();
                }
            } catch (\Throwable) {
                // Cache clear not available in this context
            }

            $task->markCompleted($result->output);
        } catch (\Throwable $e) {
            $task->markFailed('Composer operation failed: ' . $e->getMessage());
        }
    }

    private function executeComposerCommand(string $composerBinary, int $attempt = 0): ProcessResult
    {
        $command = $this->buildCommand($composerBinary, $attempt);
        return $this->runProcess($command);
    }

    private function buildCommand(string $composerBinary, int $attempt = 0): array
    {
        $binary = str_contains($composerBinary, ' ')
            ? explode(' ', $composerBinary)
            : [$composerBinary];

        return match ($this->strategy) {
            self::STRATEGY_UPDATE_ALL => [
                ...$binary,
                'update',
                '--no-interaction',
            ],
            self::STRATEGY_REQUIRE => [
                ...$binary,
                'require',
                "{$this->packageName}:{$this->version}",
                '--no-interaction',
                ...($attempt > 0 ? ['--with-all-dependencies'] : []),
            ],
            self::STRATEGY_UPDATE_SINGLE => [
                ...$binary,
                'update',
                $this->packageName ?? 'all',
                '--no-interaction',
                ...($attempt > 0 ? ['--with-all-dependencies'] : []),
            ],
            default => throw new \InvalidArgumentException("Unknown strategy: {$this->strategy}"),
        };
    }
}

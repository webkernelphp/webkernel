<?php

declare(strict_types=1);

namespace Webkernel\Jobs;

use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;

class NpmJob extends Job
{
    public const STRATEGY_UPDATE_SINGLE = 'update-single';
    public const STRATEGY_UPDATE_ALL = 'update-all';
    public const STRATEGY_INSTALL = 'install';

    protected ?int $timeout = 600; // Property Webkernel\Jobs\NpmJob::$timeout must have type null|int

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
            $npmClient = config('dependency-manager.npm_client', 'npm');
            $result = $this->executeNpmCommand($npmClient);

            if (!$result->successful) {
                $task->markFailed($result->error);
                return;
            }

            try {
                if (class_exists('Webkernel\\BackOffice\\System\\Presentation\\Resources\\DependencyManager\\Services\\NpmService')) {
                    app('Webkernel\\BackOffice\\System\\Presentation\\Resources\\DependencyManager\\Services\\NpmService')?->clearCache();
                }
            } catch (\Throwable) {
                // Cache clear not available in this context
            }

            $task->markCompleted($result->output);
        } catch (\Throwable $e) {
            $task->markFailed('NPM operation failed: ' . $e->getMessage());
        }
    }

    private function executeNpmCommand(string $npmClient): ProcessResult
    {
        $command = $this->buildCommand($npmClient);
        return $this->runProcess($command);
    }

    private function buildCommand(string $npmClient): array
    {
        return match ($this->strategy) {
            self::STRATEGY_UPDATE_ALL => [
                $npmClient,
                ...match ($npmClient) {
                    'yarn' => ['upgrade'],
                    'pnpm' => ['update'],
                    default => ['update'],
                },
            ],
            self::STRATEGY_UPDATE_SINGLE => [
                $npmClient,
                ...($npmClient === 'yarn' ? ['upgrade'] : ['update']),
                $this->packageName ?? 'all',
            ],
            self::STRATEGY_INSTALL => [
                $npmClient,
                ...match ($npmClient) {
                    'yarn' => ['add', "{$this->packageName}@{$this->version}"],
                    'pnpm' => ['add', "{$this->packageName}@{$this->version}"],
                    default => ['install', "{$this->packageName}@{$this->version}"],
                },
            ],
            default => throw new \InvalidArgumentException("Unknown strategy: {$this->strategy}"),
        };
    }
}

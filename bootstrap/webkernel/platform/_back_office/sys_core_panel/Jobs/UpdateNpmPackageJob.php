<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;

class UpdateNpmPackageJob
{

    public function __construct(
        private string $taskId,
        private string $packageName,
        private string $version,
    ) {}

    public function handle(): void
    {
        $task = WebkernelBackgroundTask::find($this->taskId);
        if (!$task) {
            return;
        }

        $task->markRunning();

        try {
            $npmClient = config('dependency-manager.npm_client', 'npm');

            $command = match ($npmClient) {
                'yarn' => ['yarn', 'add', "{$this->packageName}@{$this->version}"],
                'pnpm' => ['pnpm', 'add', "{$this->packageName}@{$this->version}"],
                default => ['npm', 'install', "{$this->packageName}@{$this->version}"],
            };

            $process = new Process(
                $command,
                base_path(),
                ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME') ?: '/root']
            );

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $task->markFailed($process->getErrorOutput() ?: 'Update failed');
                return;
            }

            app(NpmService::class)->clearCache();
            $task->markCompleted($process->getOutput());
        } catch (\Throwable $e) {
            $task->markFailed("Failed to update {$this->packageName}: " . $e->getMessage());
        }
    }
}

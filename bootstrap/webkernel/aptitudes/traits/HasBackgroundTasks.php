<?php declare(strict_types=1);

namespace Webkernel\Traits;

use Webkernel\Process;
use Webkernel\BackOffice\System\Jobs\UpdateAllComposerPackagesJob;
use Webkernel\BackOffice\System\Jobs\UpdateAllNpmPackagesJob;
use Webkernel\BackOffice\System\Jobs\UpdateComposerPackageJob;
use Webkernel\BackOffice\System\Jobs\UpdateNpmPackageJob;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;

trait HasBackgroundTasks
{
    protected function createBackgroundTask(string $type, string $label, ?array $payload = null): WebkernelBackgroundTask
    {
        return WebkernelBackgroundTask::create([
            'type' => $type,
            'label' => $label,
            'payload' => $payload,
            'status' => 'pending',
            'user_id' => filament()->auth()->id(),
        ]);
    }

    protected function dispatchComposerPackageUpdate(string $taskId, string $packageName, string $version): void
    {
        $this->runJobInBackground(UpdateComposerPackageJob::class, [$taskId, $packageName, $version]);
    }

    protected function dispatchAllComposerPackagesUpdate(string $taskId): void
    {
        $this->runJobInBackground(UpdateAllComposerPackagesJob::class, [$taskId]);
    }

    protected function dispatchNpmPackageUpdate(string $taskId, string $packageName, string $version): void
    {
        $this->runJobInBackground(UpdateNpmPackageJob::class, [$taskId, $packageName, $version]);
    }

    protected function dispatchAllNpmPackagesUpdate(string $taskId): void
    {
        $this->runJobInBackground(UpdateAllNpmPackagesJob::class, [$taskId]);
    }

    private function runJobInBackground(string $jobClass, array $args): void
    {
        $this->runJobWithProcess($jobClass, $args);
    }

    private function runJobWithProcess(string $jobClass, array $args): void
    {
        $jobJson = json_encode(['class' => $jobClass, 'args' => $args]);
        $env = array_merge(
            getenv(),
            [
                'COMPOSER_MEMORY_LIMIT' => '-1',
                'APP_ENV' => env('APP_ENV', 'production'),
            ]
        );

        $logFile = storage_path('logs/background-jobs.log');

        // Use nohup to truly detach from parent process
        $command = sprintf(
            'nohup %s %s webkernel:run-job %s >> %s 2>&1 &',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
            escapeshellarg($jobJson),
            escapeshellarg($logFile)
        );

        Process::fromShell($command, base_path(), $env)
            ->run();
    }
}

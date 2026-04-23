<?php declare(strict_types=1);

namespace Webkernel\Traits;

use Symfony\Component\Process\Process;
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
        $jobJson = escapeshellarg(json_encode(['class' => $jobClass, 'args' => $args]));
        $command = [PHP_BINARY, base_path('artisan'), 'webkernel:run-job', $jobJson];

        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->start();
    }
}

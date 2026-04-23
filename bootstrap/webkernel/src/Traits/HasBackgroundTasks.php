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
        $jobJson = json_encode(['class' => $jobClass, 'args' => $args]);

        Process::fromArray([
            PHP_BINARY,
            BASE_PATH . '/artisan',
            'webkernel:run-job',
            $jobJson,
        ], base_path(), null, null, null)
            ->start();
    }
}

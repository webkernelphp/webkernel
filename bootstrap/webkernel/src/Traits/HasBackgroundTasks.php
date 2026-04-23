<?php declare(strict_types=1);

namespace Webkernel\Traits;

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
        dispatch(new UpdateComposerPackageJob($taskId, $packageName, $version));
    }

    protected function dispatchAllComposerPackagesUpdate(string $taskId): void
    {
        dispatch(new UpdateAllComposerPackagesJob($taskId));
    }

    protected function dispatchNpmPackageUpdate(string $taskId, string $packageName, string $version): void
    {
        dispatch(new UpdateNpmPackageJob($taskId, $packageName, $version));
    }

    protected function dispatchAllNpmPackagesUpdate(string $taskId): void
    {
        dispatch(new UpdateAllNpmPackagesJob($taskId));
    }
}

<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;

class UpdateAllNpmPackagesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $taskId,
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
                'yarn' => ['yarn', 'upgrade'],
                'pnpm' => ['pnpm', 'update'],
                default => ['npm', 'update'],
            };

            $process = new Process(
                $command,
                base_path(),
                ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME') ?: '/root']
            );

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                $task->markFailed($process->getErrorOutput() ?: 'Update failed');
                return;
            }

            app(NpmService::class)->clearCache();
            $task->markCompleted($process->getOutput());
        } catch (\Throwable $e) {
            $task->markFailed('Failed to update packages: ' . $e->getMessage());
        }
    }
}

<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;

class UpdateComposerPackageJob implements ShouldQueue
{
    use Queueable;

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
            $composerBinary = config('dependency-manager.composer_binary', 'composer');

            $command = str_contains($composerBinary, ' ')
                ? array_merge(explode(' ', $composerBinary), ['require', "{$this->packageName}:{$this->version}", '--no-interaction', '--no-dev'])
                : [$composerBinary, 'require', "{$this->packageName}:{$this->version}", '--no-interaction', '--no-dev'];

            $process = new Process(
                $command,
                base_path(),
                [
                    'PATH' => dirname(PHP_BINARY) . ':/usr/local/bin:/usr/bin:/bin',
                    'HOME' => getenv('HOME') ?: '/root',
                    'COMPOSER_HOME' => getenv('HOME') . '/.composer',
                ]
            );

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $task->markFailed($process->getErrorOutput() ?: 'Update failed');
                return;
            }

            app(ComposerService::class)->clearCache();
            $task->markCompleted($process->getOutput());
        } catch (\Throwable $e) {
            $task->markFailed("Failed to update {$this->packageName}: " . $e->getMessage());
        }
    }
}

<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;

class UpdateAllComposerPackagesJob
{

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
            $composerBinary = config('dependency-manager.composer_binary', 'composer');

            $command = str_contains($composerBinary, ' ')
                ? array_merge(explode(' ', $composerBinary), ['update', '--no-interaction', '--no-dev'])
                : [$composerBinary, 'update', '--no-interaction', '--no-dev'];

            $process = new Process(
                $command,
                base_path(),
                [
                    'PATH' => dirname(PHP_BINARY) . ':/usr/local/bin:/usr/bin:/bin',
                    'HOME' => getenv('HOME') ?: '/root',
                    'COMPOSER_HOME' => getenv('HOME') . '/.composer',
                ]
            );

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                $task->markFailed($process->getErrorOutput() ?: 'Update failed');
                return;
            }

            app(ComposerService::class)->clearCache();
            $task->markCompleted($process->getOutput());
        } catch (\Throwable $e) {
            $task->markFailed('Failed to update packages: ' . $e->getMessage());
        }
    }
}

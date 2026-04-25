<?php declare(strict_types=1);

namespace Webkernel\Console;

use Illuminate\Console\Command;

class RunJobCommand extends Command
{
    protected $signature = 'webkernel:run-job {payload}';

    protected $description = 'Execute a background job';

    public function handle(): int
    {
        try {
            $payload = json_decode($this->argument('payload'), true);

            if (!isset($payload['class']) || !isset($payload['args'])) {
                \Illuminate\Support\Facades\Log::error('RunJobCommand: Missing class or args', ['payload' => $payload]);
                return 1;
            }

            $jobClass = $payload['class'];
            $args = $payload['args'];

            if (!class_exists($jobClass)) {
                \Illuminate\Support\Facades\Log::error('RunJobCommand: Class not found', ['class' => $jobClass]);
                return 1;
            }

            \Illuminate\Support\Facades\Log::info('RunJobCommand: Executing job', ['class' => $jobClass, 'args' => $args]);

            $job = new $jobClass(...$args);
            $job->handle();

            \Illuminate\Support\Facades\Log::info('RunJobCommand: Job completed', ['class' => $jobClass]);
            return 0;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RunJobCommand: Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }
}

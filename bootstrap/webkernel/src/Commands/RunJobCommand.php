<?php declare(strict_types=1);

namespace Webkernel\Commands;

use Illuminate\Console\Command;

class RunJobCommand extends Command
{
    protected $signature = 'webkernel:run-job {payload}';

    protected $description = 'Execute a background job';

    public function handle(): int
    {
        $payload = json_decode($this->argument('payload'), true);

        if (!isset($payload['class']) || !isset($payload['args'])) {
            return 1;
        }

        try {
            $jobClass = $payload['class'];
            $args = $payload['args'];

            if (!class_exists($jobClass)) {
                return 1;
            }

            $job = new $jobClass(...$args);
            $job->handle();

            return 0;
        } catch (\Throwable $e) {
            return 1;
        }
    }
}

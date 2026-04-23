<?php

declare(strict_types=1);

namespace Webkernel\Jobs;

use Webkernel\Process;

abstract class Job
{
    protected ?int $timeout = null;
    protected array $env = [];

    public function __construct() {}

    abstract public function handle(): void;

    protected function runProcess(
        array $command,
        ?string $cwd = null,
        ?int $timeout = null,
    ): ProcessResult {
        $env = array_merge(
            $_SERVER,
            $this->env,
            [
                'COMPOSER_MEMORY_LIMIT' => '-1',
            ]
        );

        $process = Process::fromArray(
            $command,
            $cwd ?? base_path(),
            $env,
            null,
            (float)($timeout ?? $this->timeout ?? 600),
        );

        try {
            $process->run();
            return new ProcessResult(
                successful: $process->isSuccessful(),
                output: $process->getOutput(),
                error: $process->getErrorOutput(),
                exitCode: $process->getExitCode() ?? 1,
            );
        } catch (\Exception $e) {
            return new ProcessResult(
                successful: false,
                output: '',
                error: $e->getMessage(),
                exitCode: 1,
            );
        }
    }
}

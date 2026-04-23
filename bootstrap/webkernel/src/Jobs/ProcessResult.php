<?php

declare(strict_types=1);

namespace Webkernel\Jobs;

readonly class ProcessResult
{
    public function __construct(
        public bool $successful,
        public string $output,
        public string $error,
        public int $exitCode,
    ) {}

    public function getOutput(): string
    {
        return $this->successful ? $this->output : $this->error;
    }
}

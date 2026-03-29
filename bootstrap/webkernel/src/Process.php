<?php

declare(strict_types=1);

namespace Webkernel;

use Symfony\Component\Process\Process as SymfonyProcess;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;

/**
 * Thin, stable abstraction over Symfony Process.
 *
 * - Isolates vendor dependency
 * - Octane safe: no static mutable state
 * - Minimal surface: only what Webkernel actually uses
 * - Composable: ready for ProcessPool, async, observability, retry
 */
final class Process
{
    private SymfonyProcess $process;

    private function __construct(SymfonyProcess $process)
    {
        $this->process = $process;
    }

    /**
     * Create from an array command (preferred — no shell injection risk).
     *
     * @param list<string> $command
     */
    public static function fromArray(
        array $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 60.0,
    ): self {
        return new self(new SymfonyProcess($command, $cwd, $env, $input, $timeout));
    }

    /**
     * Create from a shell command string.
     */
    public static function fromShell(
        string $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 60.0,
    ): self {
        return new self(
            SymfonyProcess::fromShellCommandline($command, $cwd, $env, $input, $timeout),
        );
    }

    /**
     * Run synchronously, return exit code.
     *
     * @throws ProcessTimedOutException
     * @throws ProcessSignaledException
     * @throws ProcessStartFailedException
     */
    public function run(?callable $callback = null, array $env = []): int
    {
        return $this->process->run($callback, $env);
    }

    /**
     * Run synchronously, throw ProcessFailedException on non-zero exit.
     */
    public function mustRun(?callable $callback = null, array $env = []): self
    {
        $this->run($callback, $env);

        if (! $this->isSuccessful()) {
            throw new ProcessFailedException($this);
        }

        return $this;
    }

    /**
     * Start the process asynchronously.
     */
    public function start(?callable $callback = null, array $env = []): self
    {
        $this->process->start($callback, $env);

        return $this;
    }

    /**
     * Block until the process finishes (after start()).
     */
    public function wait(?callable $callback = null): int
    {
        return $this->process->wait($callback);
    }

    /**
     * Send a signal or SIGKILL after $timeout seconds.
     */
    public function stop(float $timeout = 10.0, ?int $signal = null): ?int
    {
        return $this->process->stop($timeout, $signal);
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }

    public function getExitCode(): ?int
    {
        return $this->process->getExitCode();
    }

    public function getExitCodeText(): ?string
    {
        return $this->process->getExitCodeText();
    }

    public function getCommandLine(): string
    {
        return $this->process->getCommandLine();
    }

    public function getWorkingDirectory(): ?string
    {
        return $this->process->getWorkingDirectory();
    }

    /**
     * Escape hatch — use only when the wrapper surface is insufficient.
     */
    public function raw(): SymfonyProcess
    {
        return $this->process;
    }
}

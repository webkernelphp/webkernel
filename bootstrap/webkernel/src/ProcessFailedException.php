<?php

declare(strict_types=1);

namespace Webkernel;

use RuntimeException;

/**
 * Thrown when a Process exits with a non-zero code.
 *
 * Wraps Webkernel\Process, never Symfony directly.
 */
final class ProcessFailedException extends RuntimeException
{
    private Process $process;

    public function __construct(Process $process)
    {
        if ($process->isSuccessful()) {
            throw new \InvalidArgumentException(
                'ProcessFailedException requires a failed process, but the given process succeeded.',
            );
        }

        $message = sprintf(
            "Command failed\n\nCommand : %s\nExit code: %s (%s)\nDirectory: %s",
            $process->getCommandLine(),
            (string) $process->getExitCode(),
            (string) $process->getExitCodeText(),
            (string) $process->getWorkingDirectory(),
        );

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        if ($stdout !== '' || $stderr !== '') {
            $message .= sprintf(
                "\n\nOutput:\n================\n%s\n\nError output:\n================\n%s",
                $stdout,
                $stderr,
            );
        }

        parent::__construct($message);

        $this->process = $process;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}

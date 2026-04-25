<?php declare(strict_types=1);
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

    /**
     * Builds a detailed exception message from the failed process, including
     * the command line, exit code, working directory, and any stdout/stderr
     * output captured during execution. Throws an InvalidArgumentException
     * immediately if the given process actually succeeded, since wrapping a
     * successful process in a failure exception would be a programming error.
     *
     * @param Process $process The process that exited with a non-zero code.
     * @throws \InvalidArgumentException When the given process did not fail.
     */
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

    /**
     * Returns the failed process instance that triggered this exception.
     * Useful for callers that need to inspect exit codes, output, or the
     * original command after catching the exception.
     *
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }
}

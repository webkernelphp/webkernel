<?php declare(strict_types=1);
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

    /**
     * Private constructor — use the named static factories instead.
     * Wraps the given Symfony process so the rest of the codebase
     * never depends on the vendor type directly.
     *
     * @param SymfonyProcess $process The underlying Symfony process to wrap.
     */
    private function __construct(SymfonyProcess $process)
    {
        $this->process = $process;
    }

    /**
     * Creates a process from an array of command parts. This is the preferred
     * factory because each element is passed directly to the OS without going
     * through a shell, which eliminates shell-injection risks entirely.
     *
     * @param list<string>       $command The command and its arguments as discrete elements.
     * @param string|null        $cwd     Working directory for the process, or null to inherit.
     * @param array<string,string>|null $env Additional environment variables, merged with the current env.
     * @param mixed              $input   Stdin input for the process.
     * @param float|null         $timeout Maximum seconds to wait before timing out. Null disables the timeout.
     * @return self
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
     * Creates a process from a raw shell command string. Convenient for
     * commands that rely on shell features like pipes or redirects, but
     * callers are responsible for escaping user input to avoid injection.
     *
     * @param string             $command The full shell command string.
     * @param string|null        $cwd     Working directory for the process, or null to inherit.
     * @param array<string,string>|null $env Additional environment variables, merged with the current env.
     * @param mixed              $input   Stdin input for the process.
     * @param float|null         $timeout Maximum seconds to wait before timing out. Null disables the timeout.
     * @return self
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
     * Runs the process synchronously and blocks until it finishes.
     * The optional callback receives output lines in real time as they
     * are produced, useful for streaming progress to the user.
     * Returns the exit code directly so the caller can decide how to react.
     *
     * @param callable|null      $callback Optional real-time output handler.
     * @param array<string,string> $env    Additional environment variables for this run.
     * @return int                         The process exit code.
     * @throws ProcessTimedOutException    When the process exceeds the configured timeout.
     * @throws ProcessSignaledException    When the process is killed by a signal.
     * @throws ProcessStartFailedException When the process cannot be started at all.
     */
    public function run(?callable $callback = null, array $env = []): int
    {
        return $this->process->run($callback, $env);
    }

    /**
     * Runs the process synchronously and throws a ProcessFailedException
     * when the exit code is non-zero, making it suitable for fire-and-forget
     * calls where any failure should bubble up as an exception immediately.
     * Returns the current instance on success to allow method chaining.
     *
     * @param callable|null      $callback Optional real-time output handler.
     * @param array<string,string> $env    Additional environment variables for this run.
     * @return self
     * @throws ProcessFailedException When the process exits with a non-zero code.
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
     * Starts the process in the background without blocking. Use wait()
     * afterwards to block until it completes, or poll isRunning() to check
     * progress. The callback receives output lines as they arrive.
     *
     * @param callable|null      $callback Optional real-time output handler.
     * @param array<string,string> $env    Additional environment variables for this run.
     * @return self
     */
    public function start(?callable $callback = null, array $env = []): self
    {
        $this->process->start($callback, $env);

        return $this;
    }

    /**
     * Blocks the current thread until the background process finishes.
     * Must be called after start(). The optional callback continues to
     * receive any remaining output lines while waiting.
     *
     * @param callable|null $callback Optional real-time output handler.
     * @return int                    The process exit code.
     */
    public function wait(?callable $callback = null): int
    {
        return $this->process->wait($callback);
    }

    /**
     * Requests the process to stop gracefully, then forcibly kills it
     * with SIGKILL if it has not exited within the given timeout.
     * The optional signal overrides the initial termination signal sent
     * before the timeout elapses (defaults to SIGTERM on Unix).
     *
     * @param float    $timeout Seconds to wait for graceful exit before killing.
     * @param int|null $signal  Signal to send first, e.g. SIGTERM or SIGINT.
     * @return int|null         The exit code, or null if it cannot be determined.
     */
    public function stop(float $timeout = 10.0, ?int $signal = null): ?int
    {
        return $this->process->stop($timeout, $signal);
    }

    /**
     * Returns true while the process is still executing in the background.
     * Only meaningful after a call to start().
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    /**
     * Returns true when the process has finished and exited with code zero.
     * Returns false both for non-zero exits and for processes not yet started.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    /**
     * Returns all content written to stdout during the process execution.
     * Output is buffered internally and available in full after the process ends.
     *
     * @return string
     */
    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    /**
     * Returns all content written to stderr during the process execution.
     * Kept separate from stdout so callers can distinguish errors from normal output.
     *
     * @return string
     */
    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }

    /**
     * Returns the exit code the process produced, or null when the process
     * has not yet finished or was terminated by a signal with no exit code.
     *
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->process->getExitCode();
    }

    /**
     * Returns a human-readable description of the exit code, e.g. "General error"
     * for code 1, or null when no description is available or the process has not ended.
     *
     * @return string|null
     */
    public function getExitCodeText(): ?string
    {
        return $this->process->getExitCodeText();
    }

    /**
     * Returns the command string as it was passed to the OS, useful for
     * logging and error messages. For array commands this is the joined form;
     * for shell commands it is the original string.
     *
     * @return string
     */
    public function getCommandLine(): string
    {
        return $this->process->getCommandLine();
    }

    /**
     * Returns the working directory the process was started in, or null
     * when it inherited the working directory of the parent process.
     *
     * @return string|null
     */
    public function getWorkingDirectory(): ?string
    {
        return $this->process->getWorkingDirectory();
    }

    /**
     * Escape hatch that exposes the underlying Symfony process directly.
     * Use only when this wrapper does not expose a feature you need, and
     * consider adding a proper method here instead of reaching through repeatedly.
     *
     * @return SymfonyProcess
     */
    public function raw(): SymfonyProcess
    {
        return $this->process;
    }
}

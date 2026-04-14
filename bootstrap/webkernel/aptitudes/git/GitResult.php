<?php

declare(strict_types=1);

namespace Webkernel\Git;
use Webkernel\Process;

/**
 * Immutable value object returned by every GitRunner operation.
 *
 * Never throws. Callers inspect ->ok before consuming output.
 */
final class GitResult
{
    private function __construct(
        /** True when exit code is 0 and no exception occurred. */
        public readonly bool   $ok,
        /** Raw stdout, trimmed. */
        public readonly string $stdout,
        /** Raw stderr, trimmed. */
        public readonly string $stderr,
        /** Process exit code; null when the process never started. */
        public readonly ?int   $exitCode,
        /** The command that was run, as a list. */
        public readonly array  $command,
        /** Failure category for structured error handling. */
        public readonly string $failureKind,  // 'none' | 'exit' | 'timeout' | 'signal' | 'error'
        /** Human-readable failure detail. */
        public readonly string $failureDetail,
    ) {}

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    public static function from(Process $process, array $command): self
    {
        $exitCode = $process->getExitCode();
        $ok       = $process->isSuccessful();

        return new self(
            ok           : $ok,
            stdout       : trim($process->getOutput()),
            stderr       : trim($process->getErrorOutput()),
            exitCode     : $exitCode,
            command      : $command,
            failureKind  : $ok ? 'none' : 'exit',
            failureDetail: $ok ? '' : sprintf('exit code %d', $exitCode ?? -1),
        );
    }

    public static function timedOut(array $command, float $timeout, string $message): self
    {
        return new self(
            ok           : false,
            stdout       : '',
            stderr       : '',
            exitCode     : null,
            command      : $command,
            failureKind  : 'timeout',
            failureDetail: sprintf('timed out after %.0fs — %s', $timeout, $message),
        );
    }

    public static function signaled(array $command, int $signal, string $message): self
    {
        return new self(
            ok           : false,
            stdout       : '',
            stderr       : '',
            exitCode     : null,
            command      : $command,
            failureKind  : 'signal',
            failureDetail: sprintf('killed by signal %d — %s', $signal, $message),
        );
    }

    public static function error(array $command, string $message): self
    {
        return new self(
            ok           : false,
            stdout       : '',
            stderr       : '',
            exitCode     : null,
            command      : $command,
            failureKind  : 'error',
            failureDetail: $message,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * stdout value, or $fallback when not ok.
     */
    public function value(string $fallback = 'unknown'): string
    {
        return $this->ok && $this->stdout !== '' ? $this->stdout : $fallback;
    }

    /**
     * Human-readable summary of what went wrong.
     */
    public function summary(): string
    {
        if ($this->ok) {
            return 'ok';
        }

        $cmd = implode(' ', $this->command);

        return match ($this->failureKind) {
            'timeout' => sprintf("TIMEOUT running [%s]: %s", $cmd, $this->failureDetail),
            'signal'  => sprintf("SIGNAL running [%s]: %s",  $cmd, $this->failureDetail),
            'exit'    => sprintf(
                "FAILED [%s] exit=%d stderr=%s",
                $cmd,
                $this->exitCode ?? -1,
                $this->stderr !== '' ? $this->stderr : '(none)',
            ),
            default   => sprintf("ERROR [%s]: %s", $cmd, $this->failureDetail),
        };
    }

    public function isTimeout(): bool  { return $this->failureKind === 'timeout'; }
    public function isSignal(): bool   { return $this->failureKind === 'signal'; }
    public function isExitError(): bool{ return $this->failureKind === 'exit'; }
    public function nothingToCommit(): bool
    {
        // git commit exits 1 with this message when the index is clean.
        return !$this->ok
            && str_contains($this->stdout . $this->stderr, 'nothing to commit');
    }
}

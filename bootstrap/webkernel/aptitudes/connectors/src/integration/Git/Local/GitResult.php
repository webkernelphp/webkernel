<?php declare(strict_types=1);

namespace Webkernel\Integration\Git\Local;

use Webkernel\Process;

/**
 * Immutable value object returned by every GitRunner operation.
 * Never throws — callers inspect ->ok before consuming output.
 */
final class GitResult
{
    private function __construct(
        public readonly bool   $ok,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly ?int   $exitCode,
        public readonly array  $command,
        public readonly string $failureKind,   // 'none' | 'exit' | 'timeout' | 'signal' | 'error'
        public readonly string $failureDetail,
    ) {}

    public static function from(Process $process, array $command): self
    {
        $exitCode = $process->getExitCode();
        $ok       = $process->isSuccessful();

        return new self(
            ok:            $ok,
            stdout:        trim($process->getOutput()),
            stderr:        trim($process->getErrorOutput()),
            exitCode:      $exitCode,
            command:       $command,
            failureKind:   $ok ? 'none' : 'exit',
            failureDetail: $ok ? '' : sprintf('exit code %d', $exitCode ?? -1),
        );
    }

    public static function timedOut(array $command, float $timeout, string $message): self
    {
        return new self(
            ok: false, stdout: '', stderr: '', exitCode: null,
            command: $command,
            failureKind: 'timeout',
            failureDetail: sprintf('timed out after %.0fs — %s', $timeout, $message),
        );
    }

    public static function signaled(array $command, int $signal, string $message): self
    {
        return new self(
            ok: false, stdout: '', stderr: '', exitCode: null,
            command: $command,
            failureKind: 'signal',
            failureDetail: sprintf('killed by signal %d — %s', $signal, $message),
        );
    }

    public static function error(array $command, string $message): self
    {
        return new self(
            ok: false, stdout: '', stderr: '', exitCode: null,
            command: $command,
            failureKind: 'error',
            failureDetail: $message,
        );
    }

    public function value(string $fallback = 'unknown'): string
    {
        return $this->ok && $this->stdout !== '' ? $this->stdout : $fallback;
    }

    public function summary(): string
    {
        if ($this->ok) {
            return 'ok';
        }

        $cmd = implode(' ', $this->command);

        return match ($this->failureKind) {
            'timeout' => sprintf("TIMEOUT running [%s]: %s", $cmd, $this->failureDetail),
            'signal'  => sprintf("SIGNAL running [%s]: %s",  $cmd, $this->failureDetail),
            'exit'    => sprintf("FAILED [%s] exit=%d stderr=%s", $cmd, $this->exitCode ?? -1, $this->stderr ?: '(none)'),
            default   => sprintf("ERROR [%s]: %s", $cmd, $this->failureDetail),
        };
    }

    public function isTimeout(): bool   { return $this->failureKind === 'timeout'; }
    public function isSignal(): bool    { return $this->failureKind === 'signal'; }
    public function isExitError(): bool { return $this->failureKind === 'exit'; }

    public function nothingToCommit(): bool
    {
        return !$this->ok && str_contains($this->stdout . $this->stderr, 'nothing to commit');
    }
}

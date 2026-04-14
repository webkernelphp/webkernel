<?php

declare(strict_types=1);

namespace Webkernel\Git;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\ProcessSignaledException;

/**
 * Deterministic, observable git runner.
 *
 * Design constraints
 * ------------------
 * - No passthru(), no shell_exec(), no backtick operator.
 * - Every operation returns a GitResult; nothing throws by default.
 * - Network operations (fetch, push, pull) use a separate, larger timeout.
 * - Verbosity is per-call; callers decide what to surface.
 * - Octane-safe: zero static mutable state.
 * - Usable from CLI, HTTP (Filament panel), or API — no STDOUT assumptions.
 */
final class GitRunner
{
    // Default timeouts (seconds).
    public const TIMEOUT_LOCAL   = 30.0;
    public const TIMEOUT_NETWORK = 300.0;  // 5 min — covers slow pushes over HTTPS

    // Commands that talk to a remote and need the larger timeout.
    private const NETWORK_COMMANDS = ['push', 'pull', 'fetch', 'clone', 'ls-remote'];

    public function __construct(
        private readonly string $cwd,
        private readonly bool   $verbose = false,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Run an arbitrary git sub-command.
     *
     * @param list<string> $args   git arguments, e.g. ['commit', '-m', 'msg']
     * @param float|null   $timeout override auto-detected timeout
     */
    public function run(array $args, ?float $timeout = null): GitResult
    {
        $subcommand = $args[0] ?? '';
        $timeout  ??= $this->timeoutFor($subcommand);
        $command    = ['git', ...$args];

        if ($this->verbose) {
            $this->log('RUN', implode(' ', $command));
        }

        $process = Process::fromArray($command, cwd: $this->cwd, timeout: $timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            return GitResult::timedOut(
                command : $command,
                timeout : $timeout,
                message : $e->getMessage(),
            );
        } catch (ProcessSignaledException $e) {
            return GitResult::signaled(
                command : $command,
                signal  : $e->getProcess()->getTermSignal(),
                message : $e->getMessage(),
            );
        } catch (\Throwable $e) {
            return GitResult::error(
                command : $command,
                message : $e->getMessage(),
            );
        }

        $result = GitResult::from($process, $command);

        if ($this->verbose) {
            $this->log(
                $result->ok ? 'OK' : 'FAIL',
                sprintf(
                    'exit=%d stdout=%s stderr=%s',
                    $result->exitCode ?? -1,
                    $this->truncate($result->stdout),
                    $this->truncate($result->stderr),
                )
            );
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Convenience wrappers
    // -------------------------------------------------------------------------

    public function revParseShort(string $ref = 'HEAD'): GitResult
    {
        return $this->run(['rev-parse', '--short', $ref]);
    }

    public function revParseFull(string $ref = 'HEAD'): GitResult
    {
        return $this->run(['rev-parse', $ref]);
    }

    public function symbolicRef(string $ref = 'HEAD'): GitResult
    {
        return $this->run(['symbolic-ref', '--short', $ref]);
    }

    public function describeExactTag(string $ref = 'HEAD'): GitResult
    {
        return $this->run(['describe', '--exact-match', '--tags', $ref]);
    }

    public function remoteGetUrl(string $remote = 'origin'): GitResult
    {
        return $this->run(['remote', 'get-url', $remote]);
    }

    public function revParseGitDir(): GitResult
    {
        return $this->run(['rev-parse', '--git-dir']);
    }

    public function add(string $pathspec = '.'): GitResult
    {
        return $this->run(['add', $pathspec]);
    }

    public function commit(string $message): GitResult
    {
        return $this->run(['commit', '-m', $message]);
    }

    public function tag(string $name): GitResult
    {
        return $this->run(['tag', $name]);
    }

    /**
     * Push a ref to a remote.
     *
     * Uses TIMEOUT_NETWORK automatically.
     */
    public function push(string $remote, string $ref): GitResult
    {
        return $this->run(['push', $remote, $ref]);
    }

    // -------------------------------------------------------------------------
    // Introspection helpers (return plain values, not GitResult)
    // -------------------------------------------------------------------------

    public function isRepo(): bool
    {
        return $this->revParseGitDir()->ok;
    }

    public function currentBranch(): string
    {
        $r = $this->symbolicRef('HEAD');
        if ($r->ok && $r->stdout !== '') {
            return $r->stdout;
        }
        $fallback = $this->run(['describe', '--all', '--exact-match', 'HEAD']);
        return $fallback->ok ? $fallback->stdout : 'detached';
    }

    public function currentTag(): string
    {
        $r = $this->describeExactTag('HEAD');
        return $r->ok ? $r->stdout : '';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function timeoutFor(string $subcommand): float
    {
        return in_array($subcommand, self::NETWORK_COMMANDS, true)
            ? self::TIMEOUT_NETWORK
            : self::TIMEOUT_LOCAL;
    }

    private function log(string $level, string $message): void
    {
        // Single, predictable output channel — STDERR.
        // Works from CLI, does not pollute HTTP response bodies.
        fwrite(STDERR, sprintf("[GitRunner][%s] %s\n", $level, $message));
    }

    private function truncate(string $s, int $max = 120): string
    {
        $s = trim($s);
        return strlen($s) > $max ? substr($s, 0, $max) . '...' : $s;
    }
}

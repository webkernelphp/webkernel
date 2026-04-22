<?php declare(strict_types=1);

namespace Webkernel\Async;

/**
 * Fiber-based cooperative promise for PHP 8.1+.
 *
 * Goals:
 *   - Expressive, English-like chain: then() / catch() / finally() / await()
 *   - No event loop required — each promise runs synchronously in its own Fiber
 *   - Safe for Octane: no static mutable state, no global registries
 *   - Composable with Pool for parallel execution
 *
 * Usage:
 *
 *   $result = Promise::resolve(fn() => downloadFile($url))
 *       ->then(fn($content) => verify($content))
 *       ->then(fn($content) => extract($content, $dir))
 *       ->catch(fn(\Throwable $e) => logError($e))
 *       ->await();
 *
 * The promise is lazy: execution begins only when await() is called,
 * or when Pool::all() drives a batch.
 */
final class Promise
{
    private \Fiber|null $fiber  = null;
    private mixed       $result = null;
    private \Throwable|null $error = null;
    private bool        $settled = false;

    /** @var list<callable> */
    private array $thenCallbacks    = [];
    /** @var list<callable> */
    private array $catchCallbacks   = [];
    /** @var list<callable> */
    private array $finallyCallbacks = [];

    private function __construct(private readonly \Closure $task) {}

    // ── Static constructors ───────────────────────────────────────────────────

    /**
     * Create a promise from a callable.
     * The callable receives no arguments; it returns the resolved value.
     */
    public static function resolve(callable $task): self
    {
        return new self(\Closure::fromCallable($task));
    }

    /**
     * Create an already-rejected promise (useful for short-circuit testing).
     */
    public static function reject(\Throwable $reason): self
    {
        $p        = new self(static fn() => null);
        $p->error = $reason;
        $p->settled = true;
        return $p;
    }

    // ── Chain ─────────────────────────────────────────────────────────────────

    /**
     * Register a callback to run when the promise resolves.
     * The callback receives the resolved value and must return a new value.
     */
    public function then(callable $callback): self
    {
        $this->thenCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register an error handler.
     * The callback receives the \Throwable. If it does NOT re-throw,
     * the chain continues with the callback's return value.
     */
    public function catch(callable $callback): self
    {
        $this->catchCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register a callback that runs whether the promise resolved or rejected.
     * The finally callback receives no arguments and its return value is ignored.
     */
    public function finally(callable $callback): self
    {
        $this->finallyCallbacks[] = $callback;
        return $this;
    }

    // ── Execution ─────────────────────────────────────────────────────────────

    /**
     * Drive the promise to completion and return the resolved value.
     *
     * @throws \Throwable  when the promise rejects and no catch handler swallows the error
     */
    public function await(): mixed
    {
        if (!$this->settled) {
            $this->run();
        }

        foreach ($this->finallyCallbacks as $cb) {
            try { ($cb)(); } catch (\Throwable) {}
        }

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->result;
    }

    /**
     * Drive the promise without returning a value or throwing.
     * Useful when the side-effect is all that matters (fire-and-forget).
     */
    public function run(): void
    {
        if ($this->settled) {
            return;
        }

        $this->fiber = new \Fiber(function (): void {
            try {
                $value = ($this->task)();

                foreach ($this->thenCallbacks as $cb) {
                    $value = ($cb)($value);
                }

                $this->result  = $value;
            } catch (\Throwable $e) {
                foreach ($this->catchCallbacks as $cb) {
                    try {
                        $this->result = ($cb)($e);
                        $e            = null;
                        break;
                    } catch (\Throwable $re) {
                        $e = $re;
                    }
                }

                if ($e !== null) {
                    $this->error = $e;
                }
            } finally {
                $this->settled = true;
            }
        });

        $this->fiber->start();

        // Resume until the fiber completes (cooperative: yield points are optional)
        while ($this->fiber->isSuspended()) {
            $this->fiber->resume();
        }
    }

    /**
     * Whether the promise has completed (resolved or rejected).
     */
    public function isSettled(): bool
    {
        return $this->settled;
    }

    /**
     * Whether the promise resolved without error.
     */
    public function isResolved(): bool
    {
        return $this->settled && $this->error === null;
    }

    /**
     * Whether the promise rejected.
     */
    public function isRejected(): bool
    {
        return $this->settled && $this->error !== null;
    }

    /**
     * Return the resolved value without throwing.
     * Returns null when not yet settled or when rejected.
     */
    public function value(): mixed
    {
        return $this->result;
    }

    /**
     * Return the rejection error without throwing.
     */
    public function reason(): ?\Throwable
    {
        return $this->error;
    }
}

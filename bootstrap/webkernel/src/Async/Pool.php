<?php declare(strict_types=1);

namespace Webkernel\Async;

/**
 * Run multiple Promises concurrently using cooperative fiber scheduling.
 *
 * All fibers are started in a single pass; the pool then loops until every
 * fiber has settled. Because PHP fibers are cooperative (not preemptive),
 * "concurrency" here means interleaved execution at explicit yield points —
 * not OS-level threads. This is sufficient for I/O-bound operations like
 * multiple parallel downloads or release-list fetches.
 *
 * Usage:
 *
 *   $results = Pool::all([
 *       'github'  => Promise::resolve(fn() => $gh->releases($src1)),
 *       'gitlab'  => Promise::resolve(fn() => $gl->releases($src2)),
 *       'wk'      => Promise::resolve(fn() => $wk->releases($src3)),
 *   ]);
 *
 *   // $results['github'] is the resolved value (or \Throwable if rejected)
 *
 * allOrFail() throws on first rejection; all() always collects everything.
 */
final class Pool
{
    /**
     * Run all promises, returning a keyed map of resolved values.
     * Rejected promises surface their \Throwable as the value for that key.
     *
     * @param array<string|int, Promise> $promises
     * @return array<string|int, mixed>
     */
    public static function all(array $promises): array
    {
        // Start all fibers
        foreach ($promises as $promise) {
            if (!$promise->isSettled()) {
                $promise->run();
            }
        }

        $results = [];

        foreach ($promises as $key => $promise) {
            $results[$key] = $promise->isResolved()
                ? $promise->value()
                : $promise->reason();
        }

        return $results;
    }

    /**
     * Run all promises. Throws on the first rejection encountered.
     *
     * @param array<string|int, Promise> $promises
     * @return array<string|int, mixed>
     * @throws \Throwable
     */
    public static function allOrFail(array $promises): array
    {
        $results = self::all($promises);

        foreach ($results as $value) {
            if ($value instanceof \Throwable) {
                throw $value;
            }
        }

        return $results;
    }

    /**
     * Run all promises and return only the results that resolved successfully.
     * Silently drops rejected promises.
     *
     * @param array<string|int, Promise> $promises
     * @return array<string|int, mixed>
     */
    public static function settled(array $promises): array
    {
        $results = self::all($promises);

        return array_filter(
            $results,
            static fn (mixed $v): bool => !($v instanceof \Throwable),
        );
    }
}

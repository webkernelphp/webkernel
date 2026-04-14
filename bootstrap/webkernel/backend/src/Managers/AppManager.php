<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Webkernel\System\Contracts\Managers\AppManagerInterface;

/**
 * Laravel application configuration surface.
 *
 * Reads from the booted Laravel container — zero file I/O.
 * All values are memoised after first read.
 *
 * @internal
 */
final class AppManager implements AppManagerInterface
{
    public function environment(): string
    {
        return (string) app()->environment();
    }

    public function debug(): bool
    {
        return (bool) config('app.debug', false);
    }

    public function isProduction(): bool
    {
        return app()->isProduction();
    }

    public function cacheDriver(): string
    {
        return (string) config('cache.default', 'file');
    }

    public function queueDriver(): string
    {
        return (string) config('queue.default', 'sync');
    }

    public function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    public function url(): string
    {
        return (string) config('app.url', '');
    }

    public function laravelVersion(): string
    {
        return app()->version();
    }
}

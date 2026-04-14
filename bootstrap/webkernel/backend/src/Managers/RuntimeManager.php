<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Illuminate\Http\Request;
use Webkernel\System\Enums\RuntimeSapi;
use Webkernel\System\Contracts\Managers\RuntimeManagerInterface;

/**
 * PHP runtime context manager.
 *
 * OCTANE SAFETY: never reads $_SERVER directly.
 * Under Swoole/RoadRunner $_SERVER is a Swoole\Http\Server object, not an array.
 * All superglobal reads go through Laravel's Request facade which is Octane-aware.
 *
 * Bound as `scoped()` so it is re-resolved per request under Octane.
 */
final class RuntimeManager implements RuntimeManagerInterface
{
    public function __construct(private readonly Request $request) {}

    /** @return RuntimeSapi */
    public function sapi(): RuntimeSapi
    {
        return RuntimeSapi::current();
    }

    public function isFpm(): bool
    {
        return $this->sapi()->isFpm();
    }

    public function isCli(): bool
    {
        return $this->sapi()->isCli();
    }

    public function isAsync(): bool
    {
        return $this->sapi()->isAsync();
    }

    /** @return string|null SERVER_SOFTWARE header, null in CLI context */
    public function serverSoftware(): ?string
    {
        $v = $this->server('SERVER_SOFTWARE');

        return $v !== '' ? $v : null;
    }

    /** @return string|null Bound server IP, null in CLI context */
    public function serverAddress(): ?string
    {
        $v = $this->server('SERVER_ADDR');

        return $v !== '' ? $v : null;
    }

    /** @return int|null Bound server port, null in CLI context */
    public function serverPort(): ?int
    {
        $v = $this->server('SERVER_PORT');

        return is_numeric($v) ? (int) $v : null;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function server(string $key): string
    {
        if ($this->isCli()) {
            return '';
        }

        // request()->server() is Octane-safe; it reads the per-request context
        // not the global $_SERVER superglobal which becomes a Swoole object.
        $v = $this->request->server($key);

        return is_string($v) ? $v : '';
    }
}

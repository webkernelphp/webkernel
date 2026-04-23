<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Domain\Updates;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Webkernel\BackOffice\System\Domain\Updates\Models\WebkernelRelease;
use Webkernel\BackOffice\System\Domain\Updates\Models\WebkernelUpdateCheck;
use Webkernel\System\Operations\Providers\GitHubProvider;

/**
 * Checks for available updates for any target (webkernel core or a module).
 *
 * - Syncs all releases/tags into inst_webkernel_releases (local mirror)
 * - Logs every attempt into inst_webkernel_update_checks (audit + rate-limit guard)
 * - is_up_to_date is never stored — always computed at runtime by isUpToDate()
 *
 * Designed to be called from:
 *   - Filament page action  (manual "Check for Updates")
 *   - Laravel scheduler     (auto-check cron)
 *   - Artisan command       (webkernel:check-updates)
 *   - HTTP webhook          (custom update server push)
 *
 * Rate-limit policy:
 *   GitHub free tier: 60 req/h unauthenticated, 5000/h authenticated.
 *   MIN_CHECK_INTERVAL_SECONDS enforces a minimum gap between auto-checks.
 *   Manual checks via force() bypass this guard.
 */
final class WebkernelUpdateChecker
{
    public const WEBKERNEL_TARGET_TYPE = 'webkernel';
    public const MODULE_TARGET_TYPE    = 'module';

    public const WEBKERNEL_SLUG        = 'foundation';
    public const WEBKERNEL_OWNER       = 'webkernelphp';
    public const WEBKERNEL_REGISTRY    = 'github';

    private const MIN_CHECK_INTERVAL_SECONDS = 300; // 5 min

    private string  $targetType;
    private string  $targetSlug;
    private string  $owner;
    private string  $registry;
    private ?string $explicitToken = null;
    private bool    $forceCheck    = false;

    private function __construct(
        string $targetType,
        string $targetSlug,
        string $owner,
        string $registry,
    ) {
        $this->targetType = $targetType;
        $this->targetSlug = $targetSlug;
        $this->owner      = $owner;
        $this->registry   = $registry;
    }

    // ── Factories ─────────────────────────────────────────────────────────────

    public static function forWebkernel(): self
    {
        return new self(
            targetType: self::WEBKERNEL_TARGET_TYPE,
            targetSlug: self::WEBKERNEL_SLUG,
            owner:      self::WEBKERNEL_OWNER,
            registry:   self::WEBKERNEL_REGISTRY,
        );
    }

    public static function forModule(string $owner, string $slug, string $registry = 'github'): self
    {
        return new self(
            targetType: self::MODULE_TARGET_TYPE,
            targetSlug: $slug,
            owner:      $owner,
            registry:   $registry,
        );
    }

    // ── Options ───────────────────────────────────────────────────────────────

    public function withToken(string $token): self
    {
        $this->explicitToken = $token;
        return $this;
    }

    /**
     * Bypass rate-limit guard and minimum interval enforcement.
     * Use only for manual admin actions — not in schedulers.
     */
    public function force(): self
    {
        $this->forceCheck = true;
        return $this;
    }

    // ── Main entry point ──────────────────────────────────────────────────────

    /**
     * Run the update check: fetch from registry, sync local DB, write check log.
     * Returns the persisted WebkernelUpdateCheck row.
     *
     * Never throws — all errors are logged in the DB and returned via the check record.
     */
    public function check(): WebkernelUpdateCheck
    {
        if (!$this->forceCheck && WebkernelUpdateCheck::isRateLimited($this->targetType, $this->targetSlug)) {
            return $this->logSkip(WebkernelUpdateCheck::STATUS_RATE_LIMITED, 'GitHub rate limit active — will retry after reset.');
        }

        if (!$this->forceCheck && $this->checkedTooRecently()) {
            return $this->logSkip(WebkernelUpdateCheck::STATUS_SKIPPED, 'Checked too recently — respecting minimum interval.');
        }

        try {
            $provider = new GitHubProvider($this->owner, $this->targetSlug, $this->explicitToken);
            $targetType = $this->targetType;
            $targetSlug = $this->targetSlug;

            $context = webkernel()->do()
                ->from($provider)
                ->stepAfter('Persist releases', function ($ctx) use ($targetType, $targetSlug) {
                    WebkernelRelease::syncFromProvider($ctx->releases, $targetType, $targetSlug);
                    return true;
                })
                ->run();

            if (!$context->success) {
                return $this->logError($context->error ?? 'Unknown error');
            }

            $latestTag = WebkernelRelease::latestStable($this->targetType, $this->targetSlug)?->tag_name;
            $synced = count($context->releases);

            return $this->logSuccess($latestTag, $synced, null, null);
        } catch (\Throwable $e) {
            return $this->logError($e->getMessage());
        }
    }

    // ── Read-only helpers (no network) ────────────────────────────────────────

    /**
     * Latest stable release from local DB — no network call.
     */
    public function latestKnownRelease(): ?WebkernelRelease
    {
        return WebkernelRelease::latestStable($this->targetType, $this->targetSlug);
    }

    /**
     * Whether $installedVersion is current against the local DB.
     * Never stored — always computed here at runtime.
     */
    public function isUpToDate(string $installedVersion): bool
    {
        $latest = $this->latestKnownRelease();

        if ($latest === null) {
            return true; // no data yet — assume ok until first check runs
        }

        return version_compare(
            $this->normalizeVersion($installedVersion),
            $this->normalizeVersion($latest->version),
            '>='
        );
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function checkedTooRecently(): bool
    {
        $last = WebkernelUpdateCheck::forTarget($this->targetType, $this->targetSlug)
            ->where('status', WebkernelUpdateCheck::STATUS_SUCCESS)
            ->orderByDesc('checked_at')
            ->first();

        return $last !== null
            && $last->checked_at->diffInSeconds(now()) < self::MIN_CHECK_INTERVAL_SECONDS;
    }

    private function logSuccess(?string $latestTag, int $synced, ?int $rateRemaining = null, mixed $rateResetAt = null): WebkernelUpdateCheck
    {
        return $this->writeLog([
            'status'               => WebkernelUpdateCheck::STATUS_SUCCESS,
            'latest_tag_found'     => $latestTag,
            'releases_synced'      => $synced,
            'rate_limit_remaining' => $rateRemaining,
            'rate_limit_reset_at'  => $rateResetAt,
        ]);
    }

    private function logError(string $message, int $httpStatus = 0): WebkernelUpdateCheck
    {
        return $this->writeLog([
            'status'        => WebkernelUpdateCheck::STATUS_ERROR,
            'error_message' => $message,
            'http_status'   => $httpStatus ?: null,
        ]);
    }

    private function logSkip(string $status, string $reason): WebkernelUpdateCheck
    {
        return $this->writeLog([
            'status'        => $status,
            'error_message' => $reason,
        ]);
    }

    private function writeLog(array $data): WebkernelUpdateCheck
    {
        $row = new WebkernelUpdateCheck(array_merge([
            'id'          => Str::ulid()->toBase32(),
            'target_type' => $this->targetType,
            'target_slug' => $this->targetSlug,
            'registry'    => $this->registry,
            'checked_at'  => now(),
        ], $data));

        $row->save();

        return $row;
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(explode('+', $version)[0], 'v');
    }
}

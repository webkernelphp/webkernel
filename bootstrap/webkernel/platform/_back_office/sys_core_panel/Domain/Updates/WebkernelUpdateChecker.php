<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Domain\Updates;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Webkernel\Integration\Git\AdapterResolver;
use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\Integration\Git\Hosting\GitHubAdapter;
use Webkernel\BackOffice\System\Domain\Updates\Models\WebkernelRelease;
use Webkernel\BackOffice\System\Domain\Updates\Models\WebkernelUpdateCheck;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

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

    private AdapterResolver $resolver;
    private string          $targetType;
    private string          $targetSlug;
    private string          $owner;
    private string          $registry;
    private ?string         $explicitToken = null;
    private bool            $forceCheck    = false;

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
        $this->resolver   = new AdapterResolver(new Token());
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

        $rateRemaining = null;
        $rateResetAt   = null;

        try {
            $source  = $this->buildSource();
            $adapter = $this->resolver->resolve($source);

            if ($this->explicitToken !== null) {
                $adapter = $adapter->withToken($this->explicitToken);
            }

            [$tags, $releases] = $this->fetchFromRegistry($adapter, $source);

            $synced    = $this->syncToDatabase($tags, $releases);
            $latestTag = WebkernelRelease::latestStable($this->targetType, $this->targetSlug)?->tag_name;

            if ($adapter instanceof GitHubAdapter) {
                try {
                    $rl            = $adapter->rateLimit($source);
                    $rateRemaining = $rl['remaining'];
                    $rateResetAt   = Carbon::instance($rl['reset_at']);
                } catch (\Throwable) {
                    // Non-fatal — rate limit info is best-effort
                }
            }

            return $this->logSuccess($latestTag, $synced, $rateRemaining, $rateResetAt);
        } catch (NetworkException $e) {
            return $this->logError($e->getMessage());
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

    private function fetchFromRegistry(GitHostAdapter $adapter, Source $source): array
    {
        if ($adapter instanceof GitHubAdapter) {
            return [$adapter->tags($source), $adapter->releases($source)];
        }

        return [[], $adapter->releases($source)];
    }

    private function syncToDatabase(array $tags, array $releases): int
    {
        $releasesByTag = [];
        foreach ($releases as $release) {
            $releasesByTag[$release['tag_name'] ?? ''] = $release;
        }

        $synced = 0;

        foreach ($tags as $tag) {
            $tagName = $tag['name'] ?? '';
            if ($tagName === '') {
                continue;
            }

            $existing = WebkernelRelease::where('target_type', $this->targetType)
                ->where('target_slug', $this->targetSlug)
                ->where('tag_name', $tagName)
                ->first();

            if ($existing !== null) {
                if (isset($releasesByTag[$tagName])) {
                    $existing->applyGitHubRelease($releasesByTag[$tagName])->save();
                }
                continue;
            }

            $record     = WebkernelRelease::fromGitHubTag($tag, $this->targetType, $this->targetSlug);
            $record->id = Str::ulid()->toBase32();

            if (isset($releasesByTag[$tagName])) {
                $record->applyGitHubRelease($releasesByTag[$tagName]);
            } elseif ($tag['zipball_url'] ?? false) {
                $record->published_at = now();
            }

            $record->save();
            $synced++;
        }

        foreach ($releases as $release) {
            $tagName = $release['tag_name'] ?? '';
            if ($tagName === '') {
                continue;
            }

            $exists = WebkernelRelease::where('target_type', $this->targetType)
                ->where('target_slug', $this->targetSlug)
                ->where('tag_name', $tagName)
                ->exists();

            if (!$exists) {
                $record = new WebkernelRelease([
                    'target_type' => $this->targetType,
                    'target_slug' => $this->targetSlug,
                    'registry'    => $this->registry,
                    'tag_name'    => $tagName,
                    'version'     => explode('+', $tagName)[0],
                    'build'       => str_contains($tagName, '+') ? explode('+', $tagName, 2)[1] : null,
                ]);
                $record->id = Str::ulid()->toBase32();
                $record->applyGitHubRelease($release)->save();
                $synced++;
            }
        }

        return $synced;
    }

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

    private function buildSource(): Source
    {
        return Source::from(
            provider: Providers::GitHub,
            vendor:   $this->owner,
            slug:     $this->targetSlug,
            party:    'first',
            version:  null,
        );
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(explode('+', $version)[0], 'v');
    }
}

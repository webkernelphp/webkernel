<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Domain\Updates\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Append-only log of every update-check attempt.
 *
 * Serves three purposes:
 *   1. Audit trail — what was checked, when, result
 *   2. Rate-limit enforcement — don't hammer GitHub; check remaining quota before firing
 *   3. Scheduler / auto-update hook — query last successful check to decide whether to act
 *
 * @property string       $id
 * @property string       $target_type
 * @property string       $target_slug
 * @property string       $registry
 * @property string       $status            "success" | "error" | "rate_limited" | "skipped"
 * @property string|null  $latest_tag_found
 * @property int          $releases_synced
 * @property string|null  $error_message
 * @property int|null     $http_status
 * @property int|null     $rate_limit_remaining
 * @property Carbon|null  $rate_limit_reset_at
 * @property Carbon       $checked_at
 */
class WebkernelUpdateCheck extends Model
{
    protected $table      = 'inst_webkernel_update_checks';
    protected $connection = 'webkernel_sqlite';
    protected $keyType    = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id',
        'target_type',
        'target_slug',
        'registry',
        'status',
        'latest_tag_found',
        'releases_synced',
        'error_message',
        'http_status',
        'rate_limit_remaining',
        'rate_limit_reset_at',
        'checked_at',
    ];

    protected $casts = [
        'releases_synced'      => 'integer',
        'http_status'          => 'integer',
        'rate_limit_remaining' => 'integer',
        'rate_limit_reset_at'  => 'datetime',
        'checked_at'           => 'datetime',
    ];

    public const STATUS_SUCCESS      = 'success';
    public const STATUS_ERROR        = 'error';
    public const STATUS_RATE_LIMITED = 'rate_limited';
    public const STATUS_SKIPPED      = 'skipped';

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForTarget(Builder $query, string $type, string $slug): Builder
    {
        return $query->where('target_type', $type)->where('target_slug', $slug);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function lastSuccess(string $targetType, string $targetSlug): ?self
    {
        return static::forTarget($targetType, $targetSlug)
            ->successful()
            ->orderByDesc('checked_at')
            ->first();
    }

    public static function isRateLimited(string $targetType, string $targetSlug): bool
    {
        $last = static::forTarget($targetType, $targetSlug)
            ->orderByDesc('checked_at')
            ->first();

        if ($last === null) {
            return false;
        }

        return $last->rate_limit_remaining !== null
            && $last->rate_limit_remaining === 0
            && $last->rate_limit_reset_at !== null
            && $last->rate_limit_reset_at->isFuture();
    }
}

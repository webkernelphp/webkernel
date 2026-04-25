<?php declare(strict_types=1);

namespace Webkernel\Audit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Users\Models\User;

/**
 * AuditLog — append-only instance-level action trail.
 *
 * Records are never updated after creation. No updated_at column.
 * actor_id is nullable to support system-initiated actions (queue workers,
 * cron jobs, CLI commands) that have no authenticated user.
 *
 * Usage:
 *   AuditLog::record('domain_created', $domain, $diff);
 *
 * @property string          $id
 * @property string|null     $actor_id       FK → users.id; NULL = system
 * @property string          $action         Verb-noun: 'domain_created'
 * @property string|null     $resource_type  Polymorphic: 'domain', 'module'
 * @property string|null     $resource_id    FK to the resource (logical, no DB constraint)
 * @property array|null      $changes_json   {before: {...}, after: {...}}
 * @property string|null     $ip_address
 * @property string|null     $user_agent
 * @property-read User|null  $actor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static Builder<static> forResource(string $type, string $id)
 * @method static Builder<static> forActor(string $actorId)
 * @method static Builder<static> forAction(string $action)
 * @mixin \Eloquent
 */
class AuditLog extends Model
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'audit_log';
    protected $keyType     = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id',
        'actor_id',
        'action',
        'resource_type',
        'resource_id',
        'changes_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'changes_json' => 'array',
        'created_at'   => 'datetime',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = webkernel_id_generator()
                    ->makeUniqueIdentifier()
                    ->using('cuid2')
                    ->get();
            }

            $model->created_at ??= now();
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param Builder<static> $query */
    public function scopeForResource(Builder $query, string $type, string $id): Builder
    {
        return $query->where('resource_type', $type)->where('resource_id', $id);
    }

    /** @param Builder<static> $query */
    public function scopeForActor(Builder $query, string $actorId): Builder
    {
        return $query->where('actor_id', $actorId);
    }

    /** @param Builder<static> $query */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    // ── Factory method ────────────────────────────────────────────────────────

    /**
     * Append a new audit entry using the current request context.
     */
    public static function record(
        string $action,
        ?Model $resource = null,
        ?array $changes  = null,
    ): self {
        return static::create([
            'actor_id'      => auth()->id(),
            'action'        => $action,
            'resource_type' => $resource ? class_basename($resource) : null,
            'resource_id'   => $resource?->getKey(),
            'changes_json'  => $changes,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);
    }
}

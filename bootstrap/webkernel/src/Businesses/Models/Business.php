<?php declare(strict_types=1);

namespace Webkernel\Businesses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Businesses\Enum\BusinessStatus;
use Webkernel\Users\Models\User;

/**
 * Business — one tenant unit inside a Webkernel instance.
 *
 * The App Owner owns the instance at the infrastructure level.
 * A Business is the isolated workspace they create for a client or
 * internal team. Each Business has exactly one Business-Admin who
 * manages their own users, departments, and modules without touching
 * the infrastructure layer.
 *
 * The App Owner never maps directly to a Business — that separation
 * keeps infrastructure authority out of business-level scope.
 *
 * @property string          $id            cuid2 string PK
 * @property string          $name          Display name
 * @property string          $slug          URL-safe unique identifier
 * @property BusinessStatus  $status        pending | active | suspended
 * @property string          $admin_email   Invited Business-Admin email
 * @property string|null     $created_by    FK → users.id (App Owner or Super-User)
 * @property-read User|null  $creator
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forSlug(string $slug)
 * @mixin \Eloquent
 * @mixin IdeHelperBusiness
 */
class Business extends Model
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'businesses';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'admin_email',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => BusinessStatus::class,
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

            if (empty($model->status)) {
                $model->status = BusinessStatus::PENDING;
            }

            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', BusinessStatus::ACTIVE->value);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', BusinessStatus::PENDING->value);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeForSlug(\Illuminate\Database\Eloquent\Builder $query, string $slug): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('slug', $slug);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === BusinessStatus::ACTIVE;
    }

    public function activate(): bool
    {
        $this->status = BusinessStatus::ACTIVE;
        return $this->save();
    }

    public function suspend(): bool
    {
        $this->status = BusinessStatus::SUSPENDED;
        return $this->save();
    }

    /**
     * Generate a URL-safe slug from a name, ensuring uniqueness.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        $slug = $base;
        $n    = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}

<?php declare(strict_types=1);

namespace Webkernel\Modules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkernel\Businesses\Models\Business;
use Webkernel\Domains\Models\Domain;
use Webkernel\Modules\Enum\ModuleStatus;

/**
 * Module — a first-class feature package installed on a Webkernel instance.
 *
 * Each module can:
 *   - Expose its own Filament panel (domain-routed)
 *   - Have its own database connection per business
 *   - Define its own resources, pages, and widgets
 *
 * @property string       $id
 * @property string       $name        Human label: "CRM", "HR Suite"
 * @property string       $vendor      Composer vendor: "webkernel", "acme"
 * @property string       $slug        URL slug: "crm", "hr-suite" (unique)
 * @property string       $version     Semver: "1.0.0"
 * @property ModuleStatus $status      enabled | disabled | installing | error
 * @property array|null   $config_json Module-level configuration
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Business> $businesses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Domain>   $domains
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static> enabled()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forVendor(string $vendor)
 * @mixin \Eloquent
 */
class Module extends Model
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'modules';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'vendor',
        'slug',
        'version',
        'status',
        'config_json',
    ];

    protected $casts = [
        'status'      => ModuleStatus::class,
        'config_json' => 'array',
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
                $model->status = ModuleStatus::ENABLED;
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_module_map', 'module_id', 'business_id')
                    ->withPivot(['is_enabled', 'config_json'])
                    ->withTimestamps();
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'module_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeEnabled(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', ModuleStatus::ENABLED->value);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeForVendor(\Illuminate\Database\Eloquent\Builder $query, string $vendor): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('vendor', $vendor);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->status === ModuleStatus::ENABLED;
    }

    public function enable(): bool
    {
        $this->status = ModuleStatus::ENABLED;
        return $this->save();
    }

    public function disable(): bool
    {
        $this->status = ModuleStatus::DISABLED;
        return $this->save();
    }

    public function composerPackageName(): string
    {
        return "{$this->vendor}/{$this->slug}";
    }
}

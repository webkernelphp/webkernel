<?php declare(strict_types=1);

namespace Webkernel\Base\Arcanes\Modules\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Webkernel\Base\Businesses\Models\Business;

/**
 * BusinessModuleMap — pivot between businesses and modules.
 *
 * Tracks per-business module enablement and configuration overrides.
 * config_json overrides are merged with the module-level config_json at runtime.
 *
 * @property string     $id
 * @property string     $business_id
 * @property string     $module_id
 * @property bool       $is_enabled
 * @property array|null $config_json  Per-business config overrides
 * @property-read Business $business
 * @property-read Module   $module
 * @property \Illuminate\Support\Carbon|null $created_at
 * @mixin \Eloquent
 */
class BusinessModuleMap extends Pivot
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'business_module_map';
    protected $keyType     = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id',
        'business_id',
        'module_id',
        'is_enabled',
        'config_json',
        'created_at',
    ];

    protected $casts = [
        'is_enabled'  => 'boolean',
        'config_json' => 'array',
        'created_at'  => 'datetime',
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

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function resolvedConfig(): array
    {
        $moduleConfig   = $this->module?->config_json ?? [];
        $businessConfig = $this->config_json ?? [];

        return array_merge($moduleConfig, $businessConfig);
    }
}

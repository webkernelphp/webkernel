<?php declare(strict_types=1);

namespace Webkernel\Domains\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Businesses\Models\Business;
use Webkernel\Domains\Enum\PanelType;
use Webkernel\Modules\Models\Module;

/**
 * Domain — maps a fully-qualified hostname to a business panel.
 *
 * This is the hot-path routing table. Every HTTP request resolves its Host
 * header against this table via ResolveDomainContext middleware.
 *
 * @property string          $id
 * @property string          $domain           Fully-qualified domain name
 * @property string          $business_id      FK → businesses.id
 * @property PanelType       $panel_type       system | business | module
 * @property string|null     $module_id        FK → modules.id (module panels only)
 * @property bool            $is_primary       Whether this is the primary domain
 * @property bool            $is_active        Soft-disable without deletion
 * @property string|null     $ssl_cert_path    Path to TLS cert (null = wildcard)
 * @property string|null     $ssl_key_path     Path to TLS key (null = wildcard)
 * @property \Illuminate\Support\Carbon|null $ssl_expires_at
 * @property-read Business   $business
 * @property-read Module|null $module
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forHost(string $host)
 * @mixin \Eloquent
 */
class Domain extends Model
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'domains';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'domain',
        'business_id',
        'panel_type',
        'module_id',
        'is_primary',
        'is_active',
        'ssl_cert_path',
        'ssl_key_path',
        'ssl_expires_at',
    ];

    protected $casts = [
        'panel_type'     => PanelType::class,
        'is_primary'     => 'boolean',
        'is_active'      => 'boolean',
        'ssl_expires_at' => 'datetime',
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

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeForHost(\Illuminate\Database\Eloquent\Builder $query, string $host): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('domain', $host)->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSystemPanel(): bool
    {
        return $this->panel_type === PanelType::SYSTEM;
    }

    public function isBusinessPanel(): bool
    {
        return $this->panel_type === PanelType::BUSINESS;
    }

    public function isModulePanel(): bool
    {
        return $this->panel_type === PanelType::MODULE;
    }

    public function hasSslCert(): bool
    {
        return $this->ssl_cert_path !== null && $this->ssl_key_path !== null;
    }

    public function isSslExpired(): bool
    {
        return $this->ssl_expires_at !== null && $this->ssl_expires_at->isPast();
    }
}

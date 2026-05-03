<?php declare(strict_types=1);

namespace Webkernel\Base\Databases\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\Base\Databases\Enum\DbDriver;
use Webkernel\Base\Arcanes\Modules\Models\Module;

/**
 * DbConnection — encrypted database credentials per business (and optionally module).
 *
 * Passwords are stored as ciphertext via Laravel's encrypt() helper (APP_KEY).
 * The 'password_encrypted' cast handles transparent encrypt/decrypt so callers
 * always work with plaintext strings — decrypted values exist only in memory.
 *
 * Resolution order (DatabaseConnectionResolver cascade):
 *   1. business_id + module_id  → module-specific connection
 *   2. business_id, module NULL → business default connection
 *   3. (fallback)               → Laravel config database.default
 *
 * @property string       $id
 * @property string       $business_id
 * @property string|null  $module_id       NULL = business default
 * @property DbDriver     $driver          mysql | pgsql | sqlite
 * @property string|null  $host
 * @property int|null     $port
 * @property string       $database        Database name (or file path for SQLite)
 * @property string|null  $username
 * @property string       $password_encrypted  Ciphertext; cast decrypts on access
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property-read Business $business
 * @property-read Module|null $module
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class DbConnection extends Model
{
    protected $connection  = 'webkernel_sqlite';
    protected $table       = 'base_database_connections';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'business_id',
        'module_id',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password_encrypted',
        'verified_at',
    ];

    protected $casts = [
        'driver'               => DbDriver::class,
        'port'                 => 'integer',
        'password_encrypted'   => 'encrypted',
        'verified_at'          => 'datetime',
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isBusinessDefault(): bool
    {
        return $this->module_id === null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markVerified(): bool
    {
        $this->verified_at = now();
        return $this->save();
    }

    /**
     * Build a Laravel database config array from this record.
     * Password is decrypted by the 'encrypted' cast.
     */
    public function toLaravelConfig(): array
    {
        $config = [
            'driver'   => $this->driver->value,
            'database' => $this->database,
        ];

        if ($this->driver !== DbDriver::SQLITE) {
            $config['host']     = $this->host;
            $config['port']     = $this->port ?? $this->driver->defaultPort();
            $config['username'] = $this->username;
            $config['password'] = $this->password_encrypted; // cast decrypts
            $config['charset']  = 'utf8mb4';
        }

        if ($this->driver === DbDriver::MYSQL) {
            $config['collation'] = 'utf8mb4_unicode_ci';
        }

        $config['prefix'] = '';

        return $config;
    }
}

<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebkernelSetting extends Model
{
    protected $table      = 'inst_webkernel_settings';
    protected $connection = 'webkernel_sqlite';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = [
        'id',
        'category',
        'key',
        'type',
        'label',
        'description',
        'value',
        'default_value',
        'options_json',
        'is_sensitive',
        'introduced_in_version',
        'last_modified_by',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(WebkernelSettingHistory::class, 'setting_id');
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public static function get(string $dotKey, mixed $default = null): mixed
    {
        [$category, $key] = static::parseDotKey($dotKey);
        $setting = static::forCategory($category)->where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->resolvedValue();
    }

    public static function set(string $dotKey, mixed $value, ?string $modifier = null): void
    {
        [$category, $key] = static::parseDotKey($dotKey);

        $resolved = static::forCategory($category)->where('key', $key)->first();

        if ($resolved) {
            $oldValue = $resolved->value;
            $newValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

            if ($resolved->is_sensitive && $newValue) {
                $newValue = encrypt($newValue);
            }

            $resolved->update([
                'value' => $newValue,
                'last_modified_by' => $modifier ?? filament()->auth()?->user()?->email ?? 'system',
            ]);

            WebkernelSettingHistory::create([
                'id' => Str::ulid()->toBase32(),
                'setting_id' => $resolved->id,
                'category' => $category,
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $modifier ?? filament()->auth()?->user()?->email ?? 'system',
            ]);
        }
    }

    public function resolvedValue(): mixed
    {
        $val = $this->value ?? $this->default_value;

        if ($this->is_sensitive && $val && str_starts_with($val, 'eyJ')) {
            try {
                $val = decrypt($val);
            } catch (\Throwable) {
                // Already decrypted or invalid
            }
        }

        return match($this->type) {
            'boolean' => (bool) $val,
            'integer' => (int) $val,
            'json' => json_decode($val ?? '{}', true),
            default => $val,
        };
    }

    private static function parseDotKey(string $dotKey): array
    {
        $parts = explode('.', $dotKey, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Setting key must be in format 'category.key', got: {$dotKey}");
        }
        return $parts;
    }

    public static function seedDefaults(): void
    {
        $defaults = include __DIR__ . '/../Presentation/Pages/Data/InstanceSettingsDefaults.php';
        $version = WEBKERNEL_VERSION;

        foreach ($defaults as $category => $items) {
            foreach ($items as $data) {
                $data['id'] ??= Str::ulid()->toBase32();
                $data['category'] = $category;
                $data['introduced_in_version'] = $version;
                $data['is_sensitive'] = (bool) ($data['is_sensitive'] ?? false);

                static::updateOrCreate(
                    ['category' => $category, 'key' => $data['key']],
                    $data
                );
            }
        }
    }
}

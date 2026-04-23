<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'last_modified_by',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

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
            $resolved->update([
                'value'             => is_array($value) || is_object($value) ? json_encode($value) : (string) $value,
                'last_modified_by'  => $modifier ?? filament()->auth()?->user()?->email ?? 'system',
            ]);
        }
    }

    public function resolvedValue(): mixed
    {
        $val = $this->value ?? $this->default_value;

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

    public static function seed(array $defaults): void
    {
        foreach ($defaults as $data) {
            $data['id'] ??= Str::ulid()->toBase32();
            static::updateOrCreate(
                ['category' => $data['category'], 'key' => $data['key']],
                $data
            );
        }
    }
}

<?php declare(strict_types=1);

namespace Webkernel\CP\System\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

use Webkernel\CP\System\Models\WebkernelSettingCategory;
use Webkernel\CP\System\Models\Traits\ManagesSettings;

class WebkernelSetting extends Model
{
    use ManagesSettings;

    protected $table      = 'inst_webkernel_settings';
    protected $connection = 'webkernel_sqlite';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = [
        'id',
        'category',
        'registry',
        'vendor',
        'module',
        'key',
        'type',
        'label',
        'description',
        'value',
        'default_value',
        'options_json',
        'is_sensitive',
        'is_custom',
        'meta_json',
        'enum_class',
        'introduced_in_version',
        'last_modified_by',
        'last_touched_at',
        'depends_on_key',
        'depends_on_value',
        'sort_order',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'is_custom' => 'boolean',
        'options_json' => 'array',
        'meta_json' => 'array',
        'last_touched_at' => 'datetime',
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

        return $setting?->resolvedValue() ?? $default;
    }

    public static function set(string $dotKey, mixed $value, ?string $modifier = null): void
    {
        [$category, $key] = static::parseDotKey($dotKey);

        $setting = static::forCategory($category)->where('key', $key)->first();

        if (!$setting) {
            return;
        }

        $oldValue = $setting->value;

        $newValue = static::normalizeValue($value, $setting);

        if ($setting->is_sensitive && $newValue !== null) {
            $newValue = Crypt::encryptString($newValue);
        }

        $modifier ??= filament()->auth()?->user()?->email ?? 'system';

        $setting->update([
            'value' => $newValue,
            'last_modified_by' => $modifier,
        ]);

        WebkernelSettingHistory::create([
            'id' => Str::ulid()->toBase32(),
            'setting_id' => $setting->id,
            'category' => $category,
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $modifier,
        ]);
    }

    public function resolvedValue(): mixed
    {
        $val = $this->value ?? $this->default_value;

        if ($this->is_sensitive && $val) {
            try {
                $val = Crypt::decryptString($val);
            } catch (\Throwable) {
                // ignore
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $val,
            'json'    => is_string($val) ? json_decode($val, true) : $val,
            default   => $this->resolveEnum($val),
        };
    }

    private function resolveEnum(mixed $val): mixed
    {
        if (!$this->enum_class || !enum_exists($this->enum_class)) {
            return $val;
        }

        return $this->enum_class::from($val);
    }

    private static function normalizeValue(mixed $value, self $setting): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($setting->enum_class && enum_exists($setting->enum_class)) {
            return $value instanceof \BackedEnum ? $value->value : (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    private static function parseDotKey(string $dotKey): array
    {
        $parts = explode('.', $dotKey, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid key: {$dotKey}");
        }

        return $parts;
    }

    public static function seedDefaults(): void
    {
        $defaults = include WEBKERNEL_PATH . '/support/data/defaults/instance-settings.php';
        $version = WEBKERNEL_VERSION;

        foreach ($defaults as $categoryKey => $data) {

            // 1. CATEGORY
            WebkernelSettingCategory::updateOrCreate(
                ['key' => $categoryKey],
                [
                    'label' => $data['meta']['label'] ?? $categoryKey,
                    'icon' => $data['meta']['icon'] ?? null,
                    'sort_order' => $data['meta']['sort_order'] ?? 0,
                    'is_system' => $data['meta']['is_system'] ?? false,
                    'meta_json' => $data['meta'] ?? [],
                ]
            );

            // 2. SETTINGS
            foreach ($data['items'] as $item) {

                $item['id'] ??= Str::ulid()->toBase32();
                $item['category'] = $categoryKey;
                $item['introduced_in_version'] = $version;

                static::updateOrCreate(
                    ['category' => $categoryKey, 'key' => $item['key']],
                    $item
                );
            }
        }
    }
}

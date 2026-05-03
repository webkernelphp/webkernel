<?php declare(strict_types=1);

namespace Webkernel\CP\System\Models\Traits;

use Webkernel\CP\System\Models\WebkernelSetting;
use Webkernel\CP\System\Models\WebkernelSettingCategory;
use Illuminate\Support\Str;

trait ManagesSettings
{
    public static function createSetting(array $data): WebkernelSetting
    {
        static::validateSettingInput($data);

        $data['id'] ??= Str::ulid()->toBase32();
        $data['introduced_in_version'] = $data['introduced_in_version'] ?? WEBKERNEL_VERSION;
        $data['registry'] = $data['registry'] ?? 'webkernel';
        $data['is_custom'] = $data['is_custom'] ?? false;
        $data['is_sensitive'] = $data['is_sensitive'] ?? false;

        if (isset($data['options']) && is_array($data['options'])) {
            $data['options_json'] = $data['options'];
            unset($data['options']);
        }

        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta_json'] = $data['meta'];
            unset($data['meta']);
        }

        return static::create($data);
    }

    public static function updateSettingValue(string $dotKey, mixed $value, ?string $modifier = null): void
    {
        [$category, $key] = static::parseDotKey($dotKey);

        $setting = static::forCategory($category)->where('key', $key)->first();

        if (!$setting) {
            throw new \InvalidArgumentException("Setting not found: {$dotKey}");
        }

        $oldValue = $setting->value;
        $newValue = static::normalizeValue($value, $setting);

        if ($setting->is_sensitive && $newValue !== null) {
            $newValue = encrypt($newValue);
        }

        $modifier ??= filament()->auth()?->user()?->email ?? 'system';

        $setting->update([
            'value' => $newValue,
            'last_modified_by' => $modifier,
            'last_touched_at' => now(),
        ]);

        if (class_exists('Webkernel\CP\System\Models\WebkernelSettingHistory')) {
            \Webkernel\CP\System\Models\WebkernelSettingHistory::create([
                'id' => Str::ulid()->toBase32(),
                'setting_id' => $setting->id,
                'category' => $category,
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $modifier,
            ]);
        }
    }

    private static function validateSettingInput(array $data): void
    {
        $required = ['category', 'key', 'type', 'label'];
        $missing = array_filter($required, fn($field) => !isset($data[$field]) || empty($data[$field]));

        if ($missing) {
            throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missing));
        }

        $validTypes = ['text', 'password', 'boolean', 'integer', 'select', 'textarea', 'json'];
        if (!in_array($data['type'], $validTypes)) {
            throw new \InvalidArgumentException("Invalid type: {$data['type']}. Must be one of: " . implode(', ', $validTypes));
        }

        if ($data['type'] === 'select' && !isset($data['options']) && !isset($data['options_json'])) {
            throw new \InvalidArgumentException('Select type requires options');
        }

        if ($data['type'] === 'enum_class' && !isset($data['enum_class'])) {
            throw new \InvalidArgumentException('Enum type requires enum_class');
        }

        $existing = static::forCategory($data['category'])
            ->where('key', $data['key'])
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException("Setting already exists: {$data['category']}.{$data['key']}");
        }

        if (isset($data['category']) && !WebkernelSettingCategory::where('key', $data['category'])->exists()) {
            throw new \InvalidArgumentException("Category not found: {$data['category']}");
        }
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
            throw new \InvalidArgumentException("Invalid key format: {$dotKey}. Use category.key");
        }

        return $parts;
    }
}

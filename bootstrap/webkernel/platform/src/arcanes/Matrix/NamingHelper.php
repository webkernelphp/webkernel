<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Matrix;

/**
 * Pure naming and identifier helpers. No I/O, no state.
 */
final class NamingHelper
{
    public static function toPascal(string $value): string
    {
        return implode('', array_map('ucfirst', preg_split('/[-_]/', $value) ?: [$value]));
    }

    public static function toKebab(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
    }

    // ── Namespace builders (no trailing backslash — keeps manifest clean) ─────

    public static function moduleNamespace(string $vendor, string $slug): string
    {
        return 'WebModule\\' . self::toPascal($vendor) . self::toPascal($slug);
    }

    public static function normalizeModuleNamespace(string $declared, string $vendor, string $slug): string
    {
        $trimmed = rtrim($declared, '\\');
        if (str_starts_with($trimmed, 'WebModule\\') && substr_count($trimmed, '\\') >= 2) {
            return $trimmed;
        }
        return self::moduleNamespace($vendor, $slug);
    }

    public static function aptitudeNamespace(string $name): string
    {
        return 'Webkernel\\Aptitudes\\' . self::toPascal($name);
    }

    // ── Identifier builders ───────────────────────────────────────────────────

    public static function moduleId(string $registry, string $vendor, string $slug): string
    {
        return str_replace('.', '-', $registry) . '::' . $vendor . '/' . $slug;
    }

    public static function aptitudeId(string $name): string
    {
        return 'webkernel::aptitudes/' . $name;
    }

    // ── Folder helpers ────────────────────────────────────────────────────────

    public static function aptitudeFolderName(string $slug): string
    {
        return self::toPascal($slug);
    }

    public static function aptitudeSlugFromFolder(string $folder): string
    {
        return self::toKebab($folder);
    }
}

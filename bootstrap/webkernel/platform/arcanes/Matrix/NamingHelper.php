<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Matrix;

/**
 * Pure naming and identifier helpers. No I/O, no state.
 *
 * All artifact-kind-specific strings come from constants defined in
 * support/constants/arcanes.php.  Adding a new kind requires zero changes here.
 */
final class NamingHelper
{
    // -------------------------------------------------------------------------
    // String transforms
    // -------------------------------------------------------------------------

    public static function toPascal(string $value): string
    {
        return implode('', array_map('ucfirst', preg_split('/[-_]/', $value) ?: [$value]));
    }

    public static function toKebab(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
    }

    // -------------------------------------------------------------------------
    // Generic identifier builder
    // -------------------------------------------------------------------------

    /**
     * Build an artifact id from a free-form set of segments.
     *
     * The format template comes from WEBKERNEL_ID_FORMATS[$type].
     * Callers pass exactly the segments the template needs via $parts.
     *
     * module   : buildId('module',   $registry, $vendor, $slug)
     * platform : buildId('platform', $scope,    $slug)
     *
     * Dots in the first segment (registry / scope) are normalised to dashes
     * so that ids are safe as array keys and cache keys.
     *
     * @param  string   $type   Artifact kind (_type value)
     * @param  string[] $parts  Ordered segments consumed by the format string
     * @return string
     */
    public static function buildId(string $type, string ...$parts): string
    {
        $formats = defined('WEBKERNEL_ID_FORMATS') ? WEBKERNEL_ID_FORMATS : [];
        $fmt     = $formats[$type] ?? '%s';

        // Normalise dots in the first segment only (registry / scope)
        if (!empty($parts)) {
            $parts[0] = str_replace('.', '-', $parts[0]);
        }

        return sprintf($fmt, ...$parts);
    }

    // -------------------------------------------------------------------------
    // Generic namespace builder
    // -------------------------------------------------------------------------

    /**
     * Derive a default namespace for any artifact kind.
     *
     * Looks up WEBKERNEL_NAMESPACE_DEFAULTS[$type] and appends PascalCase(slug).
     * For module kinds the vendor is also prepended.
     *
     * @param  string $type   Artifact kind
     * @param  string $slug   Slug of the artifact
     * @param  string $vendor Vendor slug (only meaningful for 'module' kind)
     * @return string         Namespace without trailing backslash
     */
    public static function defaultNamespace(string $type, string $slug, string $vendor = ''): string
    {
        $defaults = defined('WEBKERNEL_NAMESPACE_DEFAULTS') ? WEBKERNEL_NAMESPACE_DEFAULTS : [];
        $prefix   = $defaults[$type] ?? 'Webkernel\\';

        if ($type === 'module' && $vendor !== '') {
            return rtrim($prefix, '\\') . '\\' . self::toPascal($vendor) . self::toPascal($slug);
        }

        return rtrim($prefix, '\\') . '\\' . self::toPascal($slug);
    }

    /**
     * Accept a namespace declared in a manifest and return a validated,
     * normalised version, falling back to defaultNamespace() when invalid.
     *
     * @param  string $declared Namespace string from the manifest (may be empty)
     * @param  string $type     Artifact kind
     * @param  string $slug     Artifact slug
     * @param  string $vendor   Vendor slug (for 'module' kind)
     * @return string           Namespace without trailing backslash
     */
    public static function normalizeNamespace(
        string $declared,
        string $type,
        string $slug,
        string $vendor = ''
    ): string {
        $trimmed = rtrim($declared, '\\');
        $rules   = defined('WEBKERNEL_NAMESPACE_RULES') ? WEBKERNEL_NAMESPACE_RULES : [];
        $rule    = $rules[$type] ?? null;

        if (
            $trimmed !== ''
            && ($rule === null || str_starts_with($trimmed, $rule))
            && substr_count($trimmed, '\\') >= 1
        ) {
            return $trimmed;
        }

        return self::defaultNamespace($type, $slug, $vendor);
    }

    // -------------------------------------------------------------------------
    // Folder / slug transforms
    // -------------------------------------------------------------------------

    /** Convert a filesystem folder name to a kebab slug. */
    public static function slugFromFolder(string $folder): string
    {
        return self::toKebab($folder);
    }

    /** Convert a kebab slug to a PascalCase folder name. */
    public static function folderFromSlug(string $slug): string
    {
        return self::toPascal($slug);
    }
}

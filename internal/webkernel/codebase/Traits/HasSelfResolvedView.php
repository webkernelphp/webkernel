<?php declare(strict_types=1);

namespace Webkernel\Traits;

use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;

/**
 * Enables directory-relative view resolution for any class. It registers a
 * unique Blade namespace on-the-fly based on the file's physical location.
 *
 * @property string $view The resolved view path (e.g., "dirname_hash::filename").
 * * @example
 * // In your class:
 * use HasSelfResolvedView;
 * protected static ?string $dynamicView = 'template'; // Points to template.blade.php in same dir
 *
 * // In a Blade template to reference sub-views:
 * @include(MyClass::resolveDynamicView('partials.header'))
 *
 * @package Webkernel\Traits
 */
trait HasSelfResolvedView
{
    /**
     * Internal cache for resolved paths.
     */
    protected static array $resolvedViewCache = [];

    /**
     * Resolves the view path and assigns it to the instance property.
     * This follows the Laravel trait booting convention: boot[TraitName].
     */
    public function bootHasSelfResolvedView(): void
    {
        if (property_exists($this, 'view')) {
            $this->view = static::resolveFullViewPath();
        }
    }

    /**
     * Main entry point for manual resolution.
     * Renamed from dynamicView to avoid potential collisions.
     */
    public static function resolveDynamicView(?string $view = null): string
    {
        if ($view !== null) {
            $class = static::class;
            static::$resolvedViewCache[$class] = static::applyNamespace(static::class, $view);
        }

        return static::resolveFullViewPath();
    }

    /**
     * Core logic to find the file and the "on-the-fly" namespace.
     */
    protected static function resolveFullViewPath(): string
    {
        $class = static::class;

        if (isset(static::$resolvedViewCache[$class])) {
            return static::$resolvedViewCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $shortName = $reflection->getShortName();

        // Access property via reflection or static to avoid trait composition errors
        $viewName = static::$dynamicView ?? Str::kebab($shortName);

        return static::$resolvedViewCache[$class] = static::applyNamespace($class, $viewName);
    }

    /**
     * Registers the directory and validates file existence.
     */
    protected static function applyNamespace(string $class, string $viewName): string
    {
        $reflection = new ReflectionClass($class);
        $dir = dirname($reflection->getFileName());

        $namespace = strtolower(basename($dir)) . '_' . substr(md5($dir), 0, 8);

        app('view')->addNamespace($namespace, $dir);

        $normalized = str_replace('.', '/', strtolower($viewName));
        $fullPath = "{$namespace}::{$normalized}";

        if (!file_exists("{$dir}/{$normalized}.blade.php")) {
            throw new RuntimeException(sprintf(
                "View file [%s.blade.php] not found in [%s].",
                $normalized,
                $dir
            ));
        }

        return $fullPath;
    }
}

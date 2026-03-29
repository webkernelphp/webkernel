<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;

use Filament\Panel;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * PanelSchemaInspector
 *
 * Reflection-based analyzer for Filament\Panel public methods.
 *
 * Returns a typed schema that PanelRuntimeFactory uses to apply DTO fields
 * to a Panel instance without any hardcoded method lists.
 *
 * Result is cached in-process (static property) — Octane safe because
 * the schema never changes within a request and Panel class is immutable.
 *
 * Usage:
 *   $schema = PanelSchemaInspector::analyze();
 *   $schema->isBooleanMethod('spa');           // true
 *   $schema->isStringMethod('brandName');      // true
 *   $schema->methodsOfType(MethodKind::BOOL);  // [...method names...]
 */
final class PanelSchemaInspector
{
    /** In-process cache — rebuilt once per worker lifecycle (Octane safe). */
    private static ?self $instance = null;

    /** @var array<string, MethodDescriptor> */
    private array $methods = [];

    private function __construct()
    {
        $this->analyze();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** Force re-analysis (for testing). */
    public static function flush(): void
    {
        self::$instance = null;
    }

    // -------------------------------------------------------------------------
    // Query API
    // -------------------------------------------------------------------------

    public function isBooleanMethod(string $name): bool
    {
        return isset($this->methods[$name])
            && $this->methods[$name]->kind === MethodKind::BOOL;
    }

    public function isStringMethod(string $name): bool
    {
        return isset($this->methods[$name])
            && $this->methods[$name]->kind === MethodKind::STRING;
    }

    public function isArrayMethod(string $name): bool
    {
        return isset($this->methods[$name])
            && $this->methods[$name]->kind === MethodKind::ARRAY;
    }

    public function exists(string $name): bool
    {
        return isset($this->methods[$name]);
    }

    public function get(string $name): ?MethodDescriptor
    {
        return $this->methods[$name] ?? null;
    }

    /**
     * @return list<string>
     */
    public function methodsOfType(MethodKind $kind): array
    {
        return array_values(array_filter(
            array_keys($this->methods),
            fn (string $n) => $this->methods[$n]->kind === $kind,
        ));
    }

    /**
     * Full schema as array — useful for debugging / schema endpoint.
     *
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->methods as $name => $descriptor) {
            $out[$name] = $descriptor->toArray();
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Reflection engine
    // -------------------------------------------------------------------------

    private function analyze(): void
    {
        $rc = new ReflectionClass(Panel::class);

        foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldSkip($method)) {
                continue;
            }
            $descriptor = $this->describe($method);
            if ($descriptor !== null) {
                $this->methods[$method->getName()] = $descriptor;
            }
        }
    }

    private function shouldSkip(ReflectionMethod $method): bool
    {
        static $skip = ['__construct', '__destruct', '__call', '__callStatic', 'make', 'resolve'];

        return $method->isStatic()
            || $method->isAbstract()
            || in_array($method->getName(), $skip, true);
    }

    private function describe(ReflectionMethod $method): ?MethodDescriptor
    {
        $params     = $method->getParameters();
        $paramCount = count($params);
        $returnType = $this->returnTypeName($method);

        // --- Boolean (toggle/flag) methods ---
        // Heuristic: no params, or single optional bool param, returns Panel or void/self
        if ($this->looksLikeBooleanMethod($method, $params)) {
            $firstParam  = $params[0] ?? null;
            $hasParam    = $firstParam !== null;
            $paramOptional = $hasParam && $firstParam->isOptional();

            return new MethodDescriptor(
                name:          $method->getName(),
                kind:          MethodKind::BOOL,
                paramCount:    $paramCount,
                acceptsParam:  $hasParam,
                paramOptional: $paramOptional,
                defaultValue:  true,
            );
        }

        // --- Array methods ---
        if ($this->looksLikeArrayMethod($method, $params)) {
            return new MethodDescriptor(
                name:       $method->getName(),
                kind:       MethodKind::ARRAY,
                paramCount: $paramCount,
            );
        }

        // --- String methods ---
        if ($this->looksLikeStringMethod($method, $params)) {
            return new MethodDescriptor(
                name:          $method->getName(),
                kind:          MethodKind::STRING,
                paramCount:    $paramCount,
                paramOptional: $params[0]->isOptional(),
            );
        }

        // --- Mixed / skip ---
        return null;
    }

    private function looksLikeBooleanMethod(ReflectionMethod $method, array $params): bool
    {
        $name = $method->getName();

        // Explicit boolean-flag method names in Filament Panel
        static $boolNames = [
            'login', 'registration', 'profile', 'spa', 'darkMode', 'topNavigation',
            'globalSearch', 'sidebarCollapsibleOnDesktop', 'sidebarFullyCollapsibleOnDesktop',
            'hasDarkMode', 'hasSpaMode', 'hasTopNavigation', 'viteTheme',
            'databaseNotifications', 'broadcasting', 'unsavedChangesAlerts',
            'emailVerification', 'requiresEmailVerification',
            'databaseTransactions',
        ];

        if (in_array($name, $boolNames, true)) {
            return true;
        }

        // Prefixes strongly suggesting a toggle
        foreach (['enable', 'disable'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        // Single param that accepts bool
        if (count($params) === 1 && $this->paramAcceptsBool($params[0])) {
            return true;
        }

        // No params, returns Panel (fluent flag-setter)
        if (count($params) === 0 && $this->returnIsPanel($method)) {
            return true;
        }

        return false;
    }

    private function looksLikeArrayMethod(ReflectionMethod $method, array $params): bool
    {
        $name = $method->getName();

        static $arrayKeywords = [
            'resources', 'pages', 'widgets', 'plugins', 'middleware', 'authMiddleware',
            'colors', 'navigationGroups', 'navigationItems', 'userMenuItems',
            'domains', 'renderHooks', 'routes', 'discoverResources', 'discoverPages',
            'discoverWidgets', 'discoverClusters',
        ];

        foreach ($arrayKeywords as $kw) {
            if (str_contains(strtolower($name), strtolower($kw))) {
                return true;
            }
        }

        if (! empty($params)) {
            $type = $params[0]->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
                return true;
            }
        }

        return false;
    }

    private function looksLikeStringMethod(ReflectionMethod $method, array $params): bool
    {
        if (empty($params)) {
            return false;
        }

        $type = $params[0]->getType();

        if ($type instanceof ReflectionNamedType) {
            return in_array($type->getName(), ['string', 'int', 'float'], true);
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                if ($t instanceof ReflectionNamedType && $t->getName() === 'string') {
                    return true;
                }
            }
        }

        return false;
    }

    private function paramAcceptsBool(ReflectionParameter $param): bool
    {
        $type = $param->getType();
        if ($type === null) {
            return true;
        }
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === 'bool';
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                if ($t instanceof ReflectionNamedType && $t->getName() === 'bool') {
                    return true;
                }
            }
        }
        return false;
    }

    private function returnIsPanel(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            return $name === 'static' || $name === 'self' || $name === Panel::class;
        }
        return false;
    }

    private function returnTypeName(ReflectionMethod $method): ?string
    {
        $type = $method->getReturnType();
        return $type instanceof ReflectionNamedType ? $type->getName() : null;
    }
}

// -------------------------------------------------------------------------
// Value types
// -------------------------------------------------------------------------

enum MethodKind: string
{
    case BOOL   = 'boolean';
    case STRING = 'string';
    case ARRAY  = 'array';
    case MIXED  = 'mixed';
}

final class MethodDescriptor
{
    public function __construct(
        public readonly string     $name,
        public readonly MethodKind $kind,
        public readonly int        $paramCount    = 0,
        public readonly bool       $acceptsParam  = false,
        public readonly bool       $paramOptional = false,
        public readonly mixed      $defaultValue  = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'kind'          => $this->kind->value,
            'param_count'   => $this->paramCount,
            'accepts_param' => $this->acceptsParam,
            'optional'      => $this->paramOptional,
            'default'       => $this->defaultValue,
        ];
    }
}

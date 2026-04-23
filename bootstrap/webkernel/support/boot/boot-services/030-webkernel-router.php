<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 3  WebkernelRouter
// ═══════════════════════════════════════════════════════════════════
/**
 * Internal router for /__webkernel-app/* paths.
 *
 * If the current request matches a registered Webkernel route,
 * the handler is invoked and execution terminates.
 * If no route matches, dispatch() returns false so the framework
 * (Laravel, etc.) can take over.
 */
final class WebkernelRouter
{
    private const PREFIX = '/__webkernel-app/';

    /** @var list<array{pattern: string, handler: \Closure}> */
    private static array $routes = [];

    /** @param \Closure(array<string,string> $params): void $handler */
    public static function register(string $pattern, \Closure $handler): void
    {
        self::$routes[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    /**
     * Attempt to dispatch the current request.
     * Returns true if a route matched. Returns false if no match.
     */
    public static function dispatch(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = '/' . ltrim($uri, '/');

        if (!str_starts_with($uri, self::PREFIX) && $uri !== rtrim(self::PREFIX, '/')) {
            return false;
        }

        $relative = substr($uri, strlen(self::PREFIX));

        foreach (self::$routes as $route) {
            $params = self::match($route['pattern'], $relative);
            if ($params !== null) {
                ($route['handler'])($params);
                exit;
            }
        }
        return false;
    }

    /** Returns true if the current HTTP request targets a /__webkernel-app/* path. */
    public static function isWebkernelRequest(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return str_starts_with('/' . ltrim($uri, '/'), self::PREFIX);
    }

    /** Generate a canonical /__webkernel-app/ URL. */
    public static function url(string $pattern, array $params = []): string
    {
        $path = $pattern;
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }
        return self::PREFIX . ltrim($path, '/');
    }

    /** @return array<string,string>|null */
    private static function match(string $pattern, string $uri): ?array
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern,
        );
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

}

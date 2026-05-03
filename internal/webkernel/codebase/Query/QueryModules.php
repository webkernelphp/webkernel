<?php declare(strict_types=1);
namespace Webkernel\Query;

use Webkernel\Query\Abstract\QueryCatalog;

/**
 * @example QueryModules::make()->where('active')->is(true)->get()
 * @example QueryModules::make()->where('_type')->is('aptitude')->get()
 */
final class QueryModules extends QueryCatalog
{
    protected static function loadItems(): array
    {
        $path = WEBKERNEL_MODULES_CACHE;
        if (!is_file($path) || filesize($path) === 0) {
            return [];
        }
        $payload = require $path;
        $entries = $payload['entries'] ?? [];
        return is_array($entries) ? array_values($entries) : [];
    }
}

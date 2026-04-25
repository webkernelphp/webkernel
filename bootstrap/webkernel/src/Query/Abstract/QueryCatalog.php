<?php declare(strict_types=1);
namespace Webkernel\Query\Abstract;

/**
 * Source-agnostic base for catalog queries.
 * Subclasses implement loadItems() only.
 */
abstract class QueryCatalog
{
    /** @var list<array<string, mixed>> */
    private array $items = [];
    private string $pendingKey = '';
    private mixed  $pendingVal = null;

    final protected function __construct() {}

    /** @return list<array<string, mixed>> */
    abstract protected static function loadItems(): array;

    final public static function make(): static
    {
        $instance        = new static();
        $instance->items = static::loadItems();
        return $instance;
    }

    public function where(string $key): static
    {
        $clone             = clone $this;
        $clone->pendingKey = $key;
        $clone->pendingVal = null;
        return $clone;
    }

    public function is(mixed $value): static
    {
        $clone             = clone $this;
        $clone->pendingVal = $value;
        return $clone;
    }

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        if ($this->pendingKey === '') {
            return array_values($this->items);
        }
        $key = $this->pendingKey;
        $val = $this->pendingVal;
        return array_values(array_filter($this->items, static fn (array $item) => ($item[$key] ?? null) === $val));
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        foreach ($this->items as $item) {
            if (($item['id'] ?? null) === $id) {
                return $item;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug, ?string $vendor = null): ?array
    {
        foreach ($this->items as $item) {
            if (($item['slug'] ?? $item['_slug'] ?? null) !== $slug) {
                continue;
            }
            if ($vendor !== null && ($item['vendor'] ?? $item['_vendor'] ?? null) !== $vendor) {
                continue;
            }
            return $item;
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function findByNamespace(string $namespace): ?array
    {
        $needle = rtrim($namespace, '\\') . '\\';
        foreach ($this->items as $item) {
            if (rtrim($item['namespace'] ?? '', '\\') . '\\' === $needle) {
                return $item;
            }
        }
        return null;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array   { return array_values($this->items); }
    public function count(): int   { return count($this->items); }
}

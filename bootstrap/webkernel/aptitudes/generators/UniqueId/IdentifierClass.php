<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId;

use Webkernel\Traits\HasIdentifiers;

/**
 * IdentifierClass — Blade / template bridge.
 *
 * Because traits cannot be instantiated directly in Blade templates, this
 * thin class wraps `HasIdentifiers` so you can do:
 *
 *   @php $id = new \Webkernel\Generators\UniqueId\IdentifierClass @endphp
 *   <div id="{{ $id->makeUniqueIdentifier()->using('nano')->get() }}">…</div>
 *
 * Or even terser — inject it via a view-composer and call:
 *
 *   {{ $ids->makeUniqueIdentifier()->using('uuidv4')->get() }}
 *
 * It is intentionally minimal: all logic lives in the trait and the
 * UniqueIdGenerator chain.
 *
 * Available methods (from HasIdentifiers and UniqueIdGenerator):
 * @method makeUniqueIdentifier(): Webkernel\Generators\UniqueId\UniqueIdGenerator
 * @method make(): self
 * @method using(string $strategy): self
 * @method length(int $length): self
 * @method count(int $count): self
 * @method prefix(string $prefix): self
 * @method cssSafe(bool $safe = true): self
 * @method with(array $options): self
 * @method get(): string
 * @method toArray(): array
 * @method toCss(): string
 * @method toRaw(): string
 * @method toJson(): string
 * @method strategies(): array
 * @method catalog(): array
 * @method __toString(): string
 */
final class IdentifierClass
{
    use HasIdentifiers;

    /**
     * Proxy fluent UniqueIdGenerator methods directly on the helper instance.
     * Enables webkernel_id_generator()->using('nano')->get() as documented.
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->makeUniqueIdentifier()->{$method}(...$args);
    }
}

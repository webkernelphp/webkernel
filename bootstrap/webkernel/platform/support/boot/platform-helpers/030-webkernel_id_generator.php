<?php declare(strict_types=1);

use Webkernel\Generators\UniqueId\IdentifierClass;

if (!function_exists('webkernel_id_generator')) {
    /**
     * Ultra-fast unique identifier generator with multiple strategies.
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
     *
     * Default strategies:
     * random, epoch, nano, sequential, username, uuidv4, uuidv7, ulid, sqids, cuid2, nanoid, shorthash
     */
    function webkernel_id_generator(): IdentifierClass
    {
        return (new IdentifierClass());
    }
}

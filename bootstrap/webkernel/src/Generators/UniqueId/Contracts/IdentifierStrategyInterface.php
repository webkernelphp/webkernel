<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Contracts;

interface IdentifierStrategyInterface
{
    /**
     * Generate a single identifier.
     */
    public function generate(int $length = 12, array $options = []): string;

    /**
     * Generate multiple identifiers at once (bulk-optimized).
     *
     * @return string[]
     */
    public function many(int $count, int $length = 12, array $options = []): array;

    /**
     * Return the canonical name of this strategy.
     */
    public static function name(): string;

    /**
     * Human-readable description of what this strategy does.
     */
    public static function description(): string;
}

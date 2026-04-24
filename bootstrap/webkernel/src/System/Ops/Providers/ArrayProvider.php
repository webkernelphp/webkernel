<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Providers;

use Illuminate\Support\Collection;
use Webkernel\System\Ops\Contracts\Provider;

/**
 * Array/Collection provider for in-memory data.
 *
 * Usage:
 *   webkernel()->do()
 *       ->from(ArrayProvider::data($array))
 *       ->map(fn($item) => [...])
 *       ->run();
 */
final class ArrayProvider implements Provider
{
    public function __construct(
        private readonly array $data,
        private readonly string $name = 'array',
    ) {}

    public static function data(array $data, string $name = 'array'): self
    {
        return new self($data, $name);
    }

    public function fetch(): Collection
    {
        return collect($this->data);
    }

    public function name(): string
    {
        return "Array::{$this->name}";
    }

    public function headers(): array
    {
        // In-memory data has no headers
        return [];
    }
}

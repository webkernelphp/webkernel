<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId;

use InvalidArgumentException;
use Webkernel\Generators\UniqueId\Registry\IdentifierRegistry;

/**
 * Fluent, chainable identifier builder.
 *
 * Entry-point for the entire Webkernel ID generation system.
 * Usable standalone, from a trait, from Blade templates, or via
 * the `webkernel_id_generator()` global helper.
 *
 * @example — all fluent chains:
 *
 *   // Single ID (default strategy: epoch)
 *   $id = UniqueIdGenerator::make()->get();
 *
 *   // Change strategy
 *   $id = UniqueIdGenerator::make()->using('uuidv4')->get();
 *
 *   // Custom length
 *   $id = UniqueIdGenerator::make()->length(16)->get();
 *
 *   // With prefix
 *   $id = UniqueIdGenerator::make()->prefix('wk_')->get();
 *
 *   // CSS-safe (default: true)
 *   $id = UniqueIdGenerator::make()->cssSafe(true)->get();
 *
 *   // Multiple IDs
 *   $ids = UniqueIdGenerator::make()->count(5)->toArray();
 *
 *   // CSS class list
 *   $css = UniqueIdGenerator::make()->count(3)->toCss();
 *
 *   // Raw space-separated
 *   $raw = UniqueIdGenerator::make()->count(3)->toRaw();
 *
 *   // Username strategy
 *   $id = UniqueIdGenerator::make()->using('username')->with(['name' => 'John Doe'])->get();
 *
 *   // Sqids from numbers
 *   $id = UniqueIdGenerator::make()->using('sqids')->with(['numbers' => [1, 2, 3]])->get();
 *
 *   // Full global-helper style
 *   $id = webkernel_id_generator()->using('uuidv4')->get();
 */
final class UniqueIdGenerator
{
    private string  $strategy = 'epoch';
    private int     $length   = 12;
    private int     $count    = 1;
    private string  $prefix   = '';
    private bool    $cssSafe  = true;
    private array   $options  = [];

    // ── Bootstrap ────────────────────────────────────────────────────────────

    private static bool $booted = false;

    private static function boot(): void
    {
        if (!self::$booted) {
            IdentifierRegistry::registerDefaults();
            self::$booted = true;
        }
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    public static function make(): self
    {
        self::boot();
        return new self();
    }

    // ── Fluent setters ───────────────────────────────────────────────────────

    /**
     * Select which strategy to use.
     * Accepts any name registered in the IdentifierRegistry.
     */
    public function using(string $strategy): self
    {
        $clone           = clone $this;
        $clone->strategy = strtolower($strategy);
        return $clone;
    }

    /**
     * Set the desired output length.
     * Ignored by strategies with a fixed length (uuidv4, ulid …).
     */
    public function length(int $length): self
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be at least 1.');
        }
        $clone         = clone $this;
        $clone->length = $length;
        return $clone;
    }

    /**
     * How many IDs to produce.
     */
    public function count(int $count): self
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be at least 1.');
        }
        $clone        = clone $this;
        $clone->count = $count;
        return $clone;
    }

    /**
     * Prepend a fixed string to every generated ID.
     * The prefix is not counted in `length()`.
     */
    public function prefix(string $prefix): self
    {
        $clone         = clone $this;
        $clone->prefix = $prefix;
        return $clone;
    }

    /**
     * When true (default) the first character is guaranteed to be [a-zA-Z],
     * making the ID safe as a CSS class or HTML id attribute.
     */
    public function cssSafe(bool $safe = true): self
    {
        $clone          = clone $this;
        $clone->cssSafe = $safe;
        return $clone;
    }

    /**
     * Pass strategy-specific options (e.g. name, numbers, alphabet …).
     */
    public function with(array $options): self
    {
        $clone          = clone $this;
        $clone->options = array_merge($clone->options, $options);
        return $clone;
    }

    // ── Terminal methods ─────────────────────────────────────────────────────

    /**
     * Generate and return a single identifier string.
     */
    public function get(): string
    {
        return $this->toArray()[0];
    }

    /**
     * Generate and return all identifiers as an array.
     *
     * @return string[]
     */
    public function toArray(): array
    {
        $opts = array_merge($this->options, [
            'prefix'  => $this->prefix,
            'cssSafe' => $this->cssSafe,
        ]);

        $strategy = IdentifierRegistry::make($this->strategy);

        return $strategy->many($this->count, $this->length, $opts);
    }

    /**
     * Return IDs as CSS selectors: ".id1, .id2, .id3"
     */
    public function toCss(): string
    {
        return implode(', ', array_map(
            static fn($id) => '.' . $id,
            $this->toArray()
        ));
    }

    /**
     * Return IDs as a space-separated string.
     */
    public function toRaw(): string
    {
        return implode(' ', $this->toArray());
    }

    /**
     * Return IDs as JSON.
     */
    public function toJson(): string
    {
        $encoded = json_encode($this->toArray());
        if ($encoded === false) {
            throw new \RuntimeException('Failed to JSON-encode identifiers.');
        }
        return $encoded;
    }

    // ── Introspection ────────────────────────────────────────────────────────

    /**
     * List all registered strategy names.
     *
     * @return string[]
     */
    public static function strategies(): array
    {
        self::boot();
        return IdentifierRegistry::all();
    }

    /**
     * Return a catalog with name + description for all strategies.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public static function catalog(): array
    {
        self::boot();
        return IdentifierRegistry::catalog();
    }

    /**
     * String-cast returns a single ID using current config.
     */
    public function __toString(): string
    {
        return $this->get();
    }
}

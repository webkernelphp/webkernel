<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Registry;

use InvalidArgumentException;
use Webkernel\Generators\UniqueId\Contracts\IdentifierStrategyInterface;

/**
 * Central registry for all identifier-generation strategies.
 *
 * Strategies are registered as factory callables so instances are created
 * lazily and never shared across calls (no static state leakage).
 *
 * @example — registration (in your bootstrap / service-provider):
 *
 *   IdentifierRegistry::register('random',    fn() => new RandomStrategy());
 *   IdentifierRegistry::register('epoch',     fn() => new EpochStrategy());
 *   IdentifierRegistry::register('nano',      fn() => new NanoStrategy());
 *
 * @example — usage anywhere:
 *
 *   $id  = IdentifierRegistry::make('uuidv4')->generate();
 *   $ids = IdentifierRegistry::make('random')->many(10, 12);
 *   $all = IdentifierRegistry::all();
 */
final class IdentifierRegistry
{
    /** @var array<string, callable(): IdentifierStrategyInterface> */
    private static array $strategies = [];

    /**
     * Register a strategy by name.
     *
     * @param string                               $name    Unique strategy name (e.g. 'uuidv4')
     * @param callable(): IdentifierStrategyInterface $factory Returns a fresh strategy instance
     */
    public static function register(string $name, callable $factory): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Strategy name must not be empty.');
        }

        self::$strategies[strtolower($name)] = $factory;
    }

    /**
     * Resolve a strategy instance by name.
     *
     * @throws InvalidArgumentException If the strategy is not registered.
     */
    public static function make(string $name): IdentifierStrategyInterface
    {
        $key = strtolower($name);

        if (!isset(self::$strategies[$key])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Identifier strategy [%s] is not registered. Available: %s',
                    $name,
                    implode(', ', self::all()) ?: '(none)'
                )
            );
        }

        $strategy = (self::$strategies[$key])();

        if (!$strategy instanceof IdentifierStrategyInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Strategy factory for [%s] must return an instance of %s.',
                    $name,
                    IdentifierStrategyInterface::class
                )
            );
        }

        return $strategy;
    }

    /**
     * Check whether a strategy is registered.
     */
    public static function has(string $name): bool
    {
        return isset(self::$strategies[strtolower($name)]);
    }

    /**
     * Unregister a strategy (useful in tests).
     */
    public static function forget(string $name): void
    {
        unset(self::$strategies[strtolower($name)]);
    }

    /**
     * Return an array of all registered strategy names.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return array_keys(self::$strategies);
    }

    /**
     * Return metadata about all registered strategies.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public static function catalog(): array
    {
        $catalog = [];
        foreach (self::$strategies as $key => $factory) {
            try {
                $strategy = $factory();
                $catalog[$key] = [
                    'name'        => $strategy::name(),
                    'description' => $strategy::description(),
                ];
            } catch (\Throwable $e) {
                $catalog[$key] = ['name' => $key, 'description' => '(factory error: ' . $e->getMessage() . ')'];
            }
        }
        return $catalog;
    }

    /**
     * Flush ALL registered strategies (useful in tests / fresh boot).
     */
    public static function flush(): void
    {
        self::$strategies = [];
    }

    /**
     * Register all built-in Webkernel strategies at once.
     * Call this once during application bootstrap.
     */
    public static function registerDefaults(): void
    {
        $defaults = [
            'random'     => \Webkernel\Generators\UniqueId\Strategy\RandomStrategy::class,
            'epoch'      => \Webkernel\Generators\UniqueId\Strategy\EpochStrategy::class,
            'nano'       => \Webkernel\Generators\UniqueId\Strategy\NanoStrategy::class,
            'sequential' => \Webkernel\Generators\UniqueId\Strategy\SequentialStrategy::class,
            'username'   => \Webkernel\Generators\UniqueId\Strategy\UsernameStrategy::class,
            'uuidv4'     => \Webkernel\Generators\UniqueId\Strategy\UuidV4Strategy::class,
            'uuidv7'     => \Webkernel\Generators\UniqueId\Strategy\UuidV7Strategy::class,
            'ulid'       => \Webkernel\Generators\UniqueId\Strategy\UlidStrategy::class,
            'sqids'      => \Webkernel\Generators\UniqueId\Strategy\SqidsStrategy::class,
            'cuid2'      => \Webkernel\Generators\UniqueId\Strategy\Cuid2Strategy::class,
            'nanoid'     => \Webkernel\Generators\UniqueId\Strategy\NanoIdStrategy::class,
            'shorthash'  => \Webkernel\Generators\UniqueId\Strategy\ShortHashStrategy::class,
        ];

        foreach ($defaults as $name => $class) {
            self::register($name, fn() => new $class());
        }
    }
}

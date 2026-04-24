<?php

declare(strict_types=1);

namespace Webkernel\Commands;

use Illuminate\Console\Application as Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Replaces registered Artisan commands with Webkernel equivalents.
 *
 * Uses {@see Artisan::starting()} — the official Laravel hook.
 *
 * Supports keys as strings (single command name) or arrays (multiple names/aliases).
 *
 * @internal
 */
final class CommandReplacer
{
    /**
     * Register command overrides with Artisan.
     *
     * @param array<string|list<string>, class-string<\Illuminate\Console\Command>> $overrides
     *        Map of [ 'original:name' => ReplacementClass::class ] or
     *        [ ['name1', 'name2'] => ReplacementClass::class ]
     */
     public static function register(array $overrides): void
     {
         Artisan::starting(static function (Artisan $artisan) use ($overrides): void {
             foreach ($overrides as $replacementClass => $names) {
                 /** @var SymfonyCommand $replacement */
                 $replacement = new $replacementClass();

                 // On force $names à être un tableau, qu'il soit string ou array
                 $names = (array) $names;

                 $knownNames = [$replacement->getName(), ...$replacement->getAliases()];

                 foreach ($names as $name) {
                     if (!in_array($name, $knownNames, true)) {
                         $replacement->setAliases(array_unique([...$replacement->getAliases(), $name]));
                     }
                 }

                 $artisan->add($replacement);
             }
         });
     }

    public static function registerByPrefix(array $prefixOverrides): void
    {
        Artisan::starting(static function (Artisan $artisan) use ($prefixOverrides): void {
            foreach ($prefixOverrides as $prefix => $replacementClass) {
                /** @var SymfonyCommand $replacement */
                $replacement = new $replacementClass();

                // Use prefix as alias to override all old commands with that prefix
                $replacement->setAliases([$prefix]);

                $artisan->add($replacement);
            }
        });
    }
}

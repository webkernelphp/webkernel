<?php

declare(strict_types=1);

namespace Webkernel\System\Security;

use EmergencyPageBuilder;

/**
 * Webkernel — Kernel Boundary Enforcement
 *
 * Provides structural inheritance control for core framework classes.
 * Unauthorized subclassing is detected and terminated before any
 * application logic executes.
 *
 * Protection scope:
 *   - Accidental or deliberate extension by application code
 *   - Unauthorized extension by third-party packages and plugins
 *   - Runtime class injection via autoloaders
 *
 * Out of scope (infrastructure-level responsibility):
 *   - Direct modification of source files on disk
 *   - PHP execution under a tampered opcache
 *
 * Components:
 *   Lockable      — contract for a class that seals its inheritance tree
 *   Admitted      — contract for a class authorized by a Lockable parent
 *   LockGuard     — enforcement trait for Lockable classes
 *   AdmitGuard    — enforcement trait for Admitted classes
 *   SealRegistry  — Octane-safe O(1) process-level verification store
 *   SealEnforcer  — boot-time scanner + inline real-time enforcement
 *   SealException — thrown on any boundary violation
 *
 * @package Webkernel\System\Security
 */



 // ---------------------------------------------------------------------------
 // Enforcer
 // ---------------------------------------------------------------------------

 /**
  * Boot-time scanner and real-time inline enforcer.
  *
  * Call once at kernel init (see fast-boot.php):
  *
  *   SealEnforcer::boot(paranoid: true, trustedBasePath: __DIR__ . '/source');
  *
  * After boot(), every class loaded via autoload is inspected immediately
  * by the hook registered in fast-boot.php. Both paths call inspect() — one
  * code path, no duplication.
  */
final class SealEnforcer
{
    private static bool  $booted          = false;
    private static bool  $paranoid        = false;
    private static ?string $trustedBase   = null;

    public static function boot(bool $paranoid = false, ?string $trustedBasePath = null): void
    {
        if (self::$booted) {
            return;
        }

         if ($paranoid && $trustedBasePath === null) {
             throw new \InvalidArgumentException(
                 'SealEnforcer::boot() requires $trustedBasePath when paranoid mode is enabled.',
             );
         }

         self::$paranoid    = $paranoid;
         self::$trustedBase = $trustedBasePath;

         foreach (get_declared_classes() as $class) {
             self::inspect($class);
         }

        self::$booted = true;
    }

    /**
     * Runtime reload for long-lived workers (Octane).
     * Clears registry and re-scans declared classes.
     */
    public static function reload(bool $paranoid = true, ?string $trustedBasePath = null): void
    {
        self::$booted      = false;
        self::$paranoid    = $paranoid;
        self::$trustedBase = $trustedBasePath;
        SealRegistry::clear();
        self::boot($paranoid, $trustedBasePath);
    }

     /**
      * Inspects a single class. Called by boot(), by the autoload hook in fast-boot.php,
      * and by assertLock() / assertAdmission() as a last-resort inline fallback.
      *
      * @param class-string $class
      */
     public static function inspect(string $class): void
     {
         if (SealRegistry::isVerified($class) || SealRegistry::isViolator($class)) {
             return;
         }

         $root = self::findLockableRoot($class);

         if ($root === null) {
             return;
         }

         if ($class === $root) {
             SealRegistry::markVerified($class);
             return;
         }

         // Whitelist is always read from the root class — never from $class —
         // so a subclass cannot inject itself via a late-static override.
         if (!in_array($class, $root::admittedClasses(), true)) {
             SealViolation::raise($class, 'not listed in ' . $root . '::admittedClasses()');
         }

         if (!is_a($class, Admitted::class, true)) {
             SealViolation::raise($class, 'must implement Admitted and use AdmitGuard');
         }

         /** @var class-string<Admitted> $class */
         $declared = $class::lockedParent();
         $actual   = get_parent_class($class);

         if ($declared === '') {
             SealViolation::raise($class, 'lockedParent() is empty — declare $lockedParent or override lockedParent()');
         }

         if ($actual !== $declared) {
             SealViolation::raise(
                 $class,
                 sprintf('lockedParent declared as %s but actual parent is %s', $declared, $actual ?: 'none'),
             );
         }

         if (self::$paranoid && self::$trustedBase !== null) {
             try {
                 $file = (new \ReflectionClass($class))->getFileName();
                 $real = $file !== false ? realpath($file) : false;
                 if ($real === false || !str_starts_with($real, realpath(self::$trustedBase) ?: '')) {
                     SealViolation::raise(
                         $class,
                         sprintf('file %s is outside trusted path %s', $file ?: '(unknown)', self::$trustedBase),
                     );
                 }
             } catch (\ReflectionException $e) {
                 SealViolation::raise($class, 'reflection failed: ' . $e->getMessage());
             }
         }

         SealRegistry::markVerified($class);
     }

     /** Testing only — throws in production. */
     public static function reset(): void
     {
         if (!defined('WEBKERNEL_TESTING')) {
             throw new \LogicException('SealEnforcer::reset() is not available outside of test environments.');
         }
         self::$booted      = false;
         self::$paranoid    = false;
         self::$trustedBase = null;
         SealRegistry::flush();
     }

     /** @return class-string<Lockable>|null */
     private static function findLockableRoot(string $class): ?string
     {
         if (!is_a($class, Lockable::class, true)) {
             return null;
         }

         $cursor = $class;
         while (($parent = get_parent_class($cursor)) !== false && is_a($parent, Lockable::class, true)) {
             $cursor = $parent;
         }

         /** @var class-string<Lockable> $cursor */
         return $cursor;
     }
 }


// ---------------------------------------------------------------------------
// Interfaces
// ---------------------------------------------------------------------------

/**
 * Marks a class as the root of a sealed inheritance tree.
 *
 * Implement via LockGuard. Whitelist declaration (pick one):
 *
 *   protected static array $admittedClasses = [ChildA::class, ChildB::class];
 *   final public static function admittedClasses(): array { return [...]; }
 */
interface Lockable
{
    /** @return class-string[] */
    public static function admittedClasses(): array;

    /** Final in LockGuard. Do not override. */
    public static function assertLock(): void;
}


/**
 * Marks a class as explicitly authorized by a Lockable parent.
 *
 * Implement via AdmitGuard. Parent declaration (pick one):
 *
 *   protected static string $lockedParent = CoreServiceProvider::class;
 *   final public static function lockedParent(): string { return CoreServiceProvider::class; }
 */
interface Admitted
{
    /** @return class-string<Lockable> */
    public static function lockedParent(): string;

    /** Final in AdmitGuard. Do not override. */
    public static function assertAdmission(): void;
}


// ---------------------------------------------------------------------------
// Registry
// ---------------------------------------------------------------------------

/**
 * Process-level verification store. Octane / Swoole / RoadRunner / FrankenPHP safe.
 * Holds only string-keyed boolean flags — no closures, no object refs, no request state.
 *
 * @internal
 */
final class SealRegistry
{
    /** @var array<string, true> */
    private static array $verified = [];

    /** @var array<string, true> */
    private static array $violators = [];

    public static function isVerified(string $class): bool   { return isset(self::$verified[$class]); }
    public static function markVerified(string $class): void  { self::$verified[$class] = true; }
    public static function isViolator(string $class): bool   { return isset(self::$violators[$class]); }
    public static function markViolator(string $class): void  { self::$violators[$class] = true; }

    /** Runtime reset (Octane safe). */
    public static function clear(): void
    {
        self::$verified  = [];
        self::$violators = [];
    }

    /** Testing only — throws in production. */
    public static function flush(): void
    {
        if (!defined('WEBKERNEL_TESTING')) {
            throw new \LogicException('SealRegistry::flush() is not available outside of test environments.');
        }
        self::$verified  = [];
        self::$violators = [];
    }
}


// ---------------------------------------------------------------------------
// Violation
// ---------------------------------------------------------------------------

/** @internal */
final class SealViolation
{
    public static function raise(string $violator, string $reason): never
    {
        SealRegistry::markViolator($violator);

        $message = \sprintf('Kernel boundary violation — class: %s — %s', $violator, $reason);

        if (class_exists(EmergencyPageBuilder::class)) {
            EmergencyPageBuilder::create()
                ->title('CORE INTEGRITY VIOLATION')
                ->message($message)
                ->code(500)
                ->severity('CRITICAL')
                ->systemState('KERNEL BOUNDARY VIOLATED')
                ->footer('WEBKERNEL — SEAL ENFORCER')
                ->render();
        }

        throw new SealException($message);
    }
}


// ---------------------------------------------------------------------------
// Exception
// ---------------------------------------------------------------------------

final class SealException extends \RuntimeException {}

// ---------------------------------------------------------------------------
// LockGuard
// ---------------------------------------------------------------------------

/**
 * Provides Lockable enforcement for a sealed parent class.
 *
 *   class CoreServiceProvider implements Lockable
 *   {
 *       use LockGuard;
 *       protected static array $admittedClasses = [FastApplication::class];
 *   }
 */
trait LockGuard
{
    /**
     * Declare in your class as:
     *   protected static array $admittedClasses = [...];
     *
     * Or override this method for computed lists:
     *   final public static function admittedClasses(): array { return [...]; }
     *
     * @var class-string[]
     */
    protected static array $admittedClasses = [];

    /** @return class-string[] */
    public static function admittedClasses(): array
    {
        return static::$admittedClasses;
    }

    final public static function assertLock(): void
    {
        $current = static::class;
        if ($current !== self::class && !SealRegistry::isVerified($current)) {
            SealEnforcer::inspect($current);
        }
    }

    /** Laravel / framework static boot hook. */
    protected static function bootLockGuard(): void { static::assertLock(); }

    /** Call as first line of __construct() when you own it. */
    final protected function initLock(): void { static::assertLock(); }
}


// ---------------------------------------------------------------------------
// AdmitGuard
// ---------------------------------------------------------------------------

/**
 * Provides Admitted enforcement for an authorized child class.
 *
 *   class FastApplication extends CoreServiceProvider implements Admitted
 *   {
 *       use AdmitGuard;
 *       protected static string $lockedParent = CoreServiceProvider::class;
 *   }
 */
trait AdmitGuard
{
    /**
     * Declare in your class as:
     *   protected static string $lockedParent = SomeParent::class;
     *
     * Or override this method:
     *   final public static function lockedParent(): string { return SomeParent::class; }
     *
     * @var class-string<Lockable>
     */
    protected static string $lockedParent = '';

    /** @return class-string<Lockable> */
    public static function lockedParent(): string
    {
        return static::$lockedParent;
    }

    final public static function assertAdmission(): void
    {
        $current = static::class;
        if (!SealRegistry::isVerified($current)) {
            SealEnforcer::inspect($current);
        }
    }

    /** Laravel / framework static boot hook. */
    protected static function bootAdmitGuard(): void { static::assertAdmission(); }

    /** Call as first line of __construct() when you own it. */
    final protected function initAdmission(): void { static::assertAdmission(); }
}

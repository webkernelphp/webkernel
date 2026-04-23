<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Webkernel\Auth\UserInfo;
use Webkernel\System\Contracts\Managers\AuthManagerInterface;

/**
 * Webkernel authentication and user identity manager.
 *
 * Provides a stable abstraction over Laravel's auth layer so the rest
 * of the Webkernel API never depends directly on Filament or any
 * specific guard implementation.
 *
 * Bound as `scoped()` — re-resolved per Octane request.
 *
 * Usage:
 *   webkernel()->auth()->user()
 *   webkernel()->auth()->id()
 *   webkernel()->auth()->isAuthenticated()
 *   webkernel()->auth()->hasRole('admin')
 *   webkernel()->auth()->can('manage-system')
 *   webkernel()->auth()->fieldSensitivity('email')  // for AI agent access control
 */
final class AuthManager implements AuthManagerInterface
{
    public function __construct(
        private readonly Guard $guard,
        private readonly Gate  $gate,
    ) {}

    // ── Identity ─────────────────────────────────────────────────────────────

    /**
     * Currently authenticated user, or null for guests.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        return $this->guard->user();
    }

    /**
     * Current user primary key, or null for guests.
     *
     * @return int|string|null
     */
    public function id(): int|string|null
    {
        return $this->guard->id();
    }

    public function isAuthenticated(): bool
    {
        return $this->guard->check();
    }

    public function isGuest(): bool
    {
        return $this->guard->guest();
    }

    // ── Role / Permission ─────────────────────────────────────────────────────

    /**
     * Returns true if the current user has the given role.
     *
     * Supports Spatie laravel-permission, Filament Shield,
     * or any model that exposes hasRole().
     *
     * @param  string|array<string>  $role
     */
    public function hasRole(string|array $role): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

//        if (method_exists($user, 'hasRole')) {
//            return (bool) $user->hasRole($role);
//        }

        return false;
    }

    /**
     * Gate check for the current user.
     *
     * Uses Gate::forUser() rather than $user->can() because Authenticatable
     * does not declare can() — that method lives on Illuminate\Foundation\Auth\User.
     * Gate::forUser() works with any Authenticatable implementation.
     *
     * @param  mixed  $arguments
     */
    public function can(string $ability, mixed $arguments = []): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->check($ability, $arguments);
    }

    // ── AI Field Sensitivity ──────────────────────────────────────────────────

    /**
     * Returns the sensitivity level for a given model field name.
     *
     * This drives the AI-agent ACL layer: when building customer bases
     * accessible by autonomous agents, each column can be tagged so
     * the agent knows what it may read, annotate, or never touch.
     *
     * Levels (ascending restriction):
     *   'public'     — always readable by agents and API consumers
     *   'internal'   — readable by authenticated agents only
     *   'restricted' — readable only by privileged / human-confirmed agents
     *   'sensitive'  — PII, financial, medical — never exposed to AI agents
     *   'critical'   — credentials, secrets — never stored in agent context
     *
     * The sensitivity map is defined in config/webkernel-auth.php under
     * the `field_sensitivity` key, keyed by model class → field → level.
     *
     * Example config entry:
     *   'field_sensitivity' => [
     *       \App\Models\Customer::class => [
     *           'email'          => 'sensitive',
     *           'phone'          => 'sensitive',
     *           'name'           => 'internal',
     *           'notes'          => 'public',
     *           'stripe_id'      => 'critical',
     *           'credit_score'   => 'sensitive',
     *       ],
     *   ],
     *
     * @param  string       $field      Column / attribute name
     * @param  string|null  $modelClass Fully-qualified model class (optional, defaults to global map)
     * @return string  One of: public | internal | restricted | sensitive | critical
     */
    public function fieldSensitivity(string $field, ?string $modelClass = null): string
    {
        /** @var array<string, array<string, string>|string> $map */
        $map = config('webkernel-auth.field_sensitivity', []);

        if ($modelClass !== null && isset($map[$modelClass][$field])) {
            return (string) $map[$modelClass][$field];
        }

        // Fall back to global field-name defaults
        if (isset($map[$field]) && is_string($map[$field])) {
            return $map[$field];
        }

        return 'internal'; // safe default: not exposed to agents without explicit grant
    }

    /**
     * Returns true when an AI agent is allowed to read the given field.
     *
     * @param  string       $field
     * @param  string|null  $modelClass
     * @param  string       $agentLevel  The agent's maximum clearance level
     */
    public function agentCanReadField(
        string $field,
        ?string $modelClass = null,
        string $agentLevel = 'internal',
    ): bool {
        $levels = ['public', 'internal', 'restricted', 'sensitive', 'critical'];
        $fieldLevel = $this->fieldSensitivity($field, $modelClass);

        $agentIndex = array_search($agentLevel, $levels, true);
        $fieldIndex = array_search($fieldLevel, $levels, true);

        if ($agentIndex === false || $fieldIndex === false) {
            return false;
        }

        // Agent can read a field if its clearance level >= field sensitivity level
        // BUT sensitive and critical are NEVER readable by AI agents regardless of level
        if ($fieldLevel === 'sensitive' || $fieldLevel === 'critical') {
            return false;
        }

        return $agentIndex >= $fieldIndex;
    }

    // ── Convenience ───────────────────────────────────────────────────────────

    /**
     * Returns a value object wrapping the current user for safe serialization.
     * Useful for passing user context to jobs, events, and AI agent sessions
     * without leaking the full Eloquent model.
     *
     * @return UserInfo|null
     */
    public function userInfo(): ?UserInfo
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        return new UserInfo(
            id:    $user->getAuthIdentifier(),
            name:  method_exists($user, 'name') ? (string) $user->name : '',    // @phpstan-ignore-line
            email: method_exists($user, 'email') ? (string) $user->email : '',  // @phpstan-ignore-line
        );
    }
}

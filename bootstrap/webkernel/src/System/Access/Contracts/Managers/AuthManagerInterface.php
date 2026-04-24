<?php declare(strict_types=1);

namespace Webkernel\System\Access\Contracts\Managers;

use Illuminate\Contracts\Auth\Authenticatable;
use Webkernel\Auth\UserInfo;

/**
 * Webkernel authentication and identity contract.
 *
 * @api
 */
interface AuthManagerInterface
{
    /** Currently authenticated user, or null for guests. */
    public function user(): ?Authenticatable;

    /** Current user primary key, or null for guests. */
    public function id(): int|string|null;

    public function isAuthenticated(): bool;

    public function isGuest(): bool;

    /**
     * Role check. Supports Spatie laravel-permission and Filament Shield.
     *
     * @param  string|array<string>  $role
     */
    public function hasRole(string|array $role): bool;

    /**
     * Laravel Gate check against the current user.
     *
     * @param  mixed  $arguments
     */
    public function can(string $ability, mixed $arguments = []): bool;

    /**
     * Sensitivity level for a model field.
     *
     * Drives the AI-agent ACL layer.
     * One of: public | internal | restricted | sensitive | critical
     *
     * @param  string       $field
     * @param  string|null  $modelClass
     */
    public function fieldSensitivity(string $field, ?string $modelClass = null): string;

    /**
     * Returns true when an AI agent with the given clearance may read the field.
     *
     * `sensitive` and `critical` fields are NEVER readable by AI agents.
     *
     * @param  string       $field
     * @param  string|null  $modelClass
     * @param  string       $agentLevel  public | internal | restricted
     */
    public function agentCanReadField(
        string $field,
        ?string $modelClass = null,
        string $agentLevel = 'internal',
    ): bool;

    /**
     * Serializable value object for the current user.
     * Safe to pass into jobs, events, and AI agent sessions.
     */
    public function userInfo(): ?UserInfo;
}

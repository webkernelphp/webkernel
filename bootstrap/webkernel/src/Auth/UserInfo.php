<?php declare(strict_types=1);

namespace Webkernel\Auth;

/**
 * Immutable, serializable representation of an authenticated user.
 *
 * Intentionally minimal: only the identity fields that are safe
 * to pass into jobs, events, AI agent sessions, and audit logs
 * without leaking the full Eloquent model or any sensitive payload.
 *
 * Fields with unknown sensitivity are NOT included here.
 * Use webkernel()->auth()->fieldSensitivity() to gate access
 * to additional attributes before passing them to agents.
 */
final readonly class UserInfo
{
    public function __construct(
        /** Primary key — int for standard users, string for UUID models. */
        public readonly int|string $id,

        /** Display name — empty string when not available. */
        public readonly string $name,

        /**
         * Email address.
         *
         * NOTE: email is `internal` sensitivity by default.
         * Do NOT include in AI agent context without explicit grant.
         */
        public readonly string $email,
    ) {}

    /**
     * Returns only the fields safe for an AI agent at the given clearance level.
     *
     * @param  string  $agentLevel  public | internal | restricted
     * @return array<string, mixed>
     */
    public function forAgent(string $agentLevel = 'internal'): array
    {
        // id and name are 'internal' — readable by default agents
        $payload = [
            'id'   => $this->id,
            'name' => $this->name,
        ];

        // email is 'internal' but excluded from 'public' agents
        if (in_array($agentLevel, ['internal', 'restricted'], true)) {
            $payload['email'] = $this->email;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }
}

<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an administrative system action is performed.
 *
 * Channel: WEBKERNEL_WS_CHANNEL_SYSTEM (webkernel.system) — private.
 * Consumed by the maintenance page Reverb listener to notify
 * other open admin sessions.
 */
class SystemActionEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        /** Human-readable action label, e.g. "cache:clear". */
        public readonly string  $action,
        /** Display name of the user who performed the action. */
        public readonly ?string $performedBy = null,
        /** Optional additional context payload. */
        public readonly array   $context = [],
    ) {}

    /**
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(WEBKERNEL_WS_CHANNEL_SYSTEM)];
    }

    public function broadcastAs(): string
    {
        return 'SystemAction';
    }
}

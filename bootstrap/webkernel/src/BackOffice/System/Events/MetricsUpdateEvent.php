<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when real-time system metrics are pushed over WebSocket.
 *
 * Channel: WEBKERNEL_WS_CHANNEL_METRICS (webkernel.metrics)
 */
class MetricsUpdateEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        /** Serialised metrics payload. */
        public readonly array $metrics,
        /** Unix timestamp of collection. */
        public readonly float $collectedAt,
    ) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [new Channel(WEBKERNEL_WS_CHANNEL_METRICS)];
    }

    public function broadcastAs(): string
    {
        return 'MetricsUpdate';
    }
}

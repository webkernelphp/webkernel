<?php declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Webkernel Broadcast Channel Definitions
|--------------------------------------------------------------------------
|
| WEBKERNEL_WS_CHANNEL_SYSTEM  (private)  — admin system action events
| WEBKERNEL_WS_CHANNEL_AUDIT   (private)  — security audit stream
| WEBKERNEL_WS_CHANNEL_METRICS (public)   — real-time metrics push
|
*/

Broadcast::channel(WEBKERNEL_WS_CHANNEL_SYSTEM, static function ($user): bool {
    return $user !== null;
});

Broadcast::channel(WEBKERNEL_WS_CHANNEL_AUDIT, static function ($user): bool {
    return $user !== null;
});

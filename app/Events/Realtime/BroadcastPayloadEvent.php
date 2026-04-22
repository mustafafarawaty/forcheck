<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast a generic payload to one or more websocket channels.
 */
class BroadcastPayloadEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $channels
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $channels,
        private readonly string $eventName,
        private readonly array $payload,
    ) {
    }

    /**
     * Channels receiving the payload.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $channel): Channel => new Channel($channel),
            $this->channels
        );
    }

    /**
     * Public event alias listened to by Echo.
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    /**
     * Broadcast payload.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

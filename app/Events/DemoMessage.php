<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemoMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message,
        public string $sentAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('demo')];
    }

    public function broadcastAs(): string
    {
        return 'demo.message';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'sent_at' => $this->sentAt,
        ];
    }
}

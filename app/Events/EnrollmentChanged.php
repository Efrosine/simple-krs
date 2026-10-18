<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnrollmentChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $courseId,
        public int $enrolledCount,
        public int $capacity,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('course-capacity')];
    }

    public function broadcastAs(): string
    {
        return 'enrollment.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'course_id' => $this->courseId,
            'enrolled_count' => $this->enrolledCount,
            'capacity' => $this->capacity,
            'remaining_seats' => max(0, $this->capacity - $this->enrolledCount),
        ];
    }
}

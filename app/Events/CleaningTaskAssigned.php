<?php

namespace App\Events;

use App\Models\CleaningTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CleaningTaskAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CleaningTask $task)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ob-tasks.' . $this->task->assigned_to),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'room_number' => $this->task->room->room_number ?? null,
            'floor' => $this->task->room->floor ?? null,
            'room_type' => $this->task->room->roomType->name ?? null,
            'status' => $this->task->status,
            'assigned_to' => $this->task->assigned_to,
        ];
    }

    public function broadcastAs(): string
    {
        return 'CleaningTaskAssigned';
    }
}

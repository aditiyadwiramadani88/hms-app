<?php

namespace App\Services;

use App\Events\CleaningTaskAssigned;
use App\Models\CleaningChecklistItem;
use App\Models\CleaningChecklistTemplate;
use App\Models\CleaningTask;
use App\Models\CleaningTaskPhoto;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleaningTaskService
{
    /**
     * Create a new cleaning task for a room and assign to a user.
     * Generates checklist items from the hotel's template (or defaults).
     */
    public function createTask(Room $room, int $assignedTo, int $assignedBy): CleaningTask
    {
        return DB::transaction(function () use ($room, $assignedTo, $assignedBy) {
            $task = CleaningTask::create([
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'assigned_to' => $assignedTo,
                'assigned_by' => $assignedBy,
                'status' => CleaningTask::STATUS_BELUM_MULAI,
            ]);

            // 1. Check if room has custom checklist
            $roomChecklist = $room->checklistTemplates()->active()->orderBy('sort_order')->get();

            if ($roomChecklist->isNotEmpty()) {
                // Use specific checklist for this room
                $templateItems = $roomChecklist;
            } else {
                // Fallback: use all active hotel templates
                $templateItems = CleaningChecklistTemplate::where('hotel_id', $room->hotel_id)
                    ->active()
                    ->orderBy('sort_order')
                    ->get();
            }

            foreach ($templateItems as $item) {
                CleaningChecklistItem::create([
                    'cleaning_task_id' => $task->id,
                    'name' => $item->name,
                    'sort_order' => $item->sort_order,
                    'is_done' => false,
                ]);
            }

            // Load relationships for broadcast
            $task->load(['room', 'assignedUser']);

            // Update room status to "Room Refresh" (Being Cleaned)
            if ($room->status !== 'Room Refresh' && $room->status !== 'In-House') {
                $room->update(['status' => 'Room Refresh']);
            }

            // Dispatch broadcast event
            event(new CleaningTaskAssigned($task));

            return $task->fresh(['room', 'room.roomType', 'assignedUser']);
        });
    }

    /**
     * Get all tasks assigned to a user, ordered by status priority.
     * Priority: belum_mulai > sedang_dikerjakan > selesai
     */
    public function getTasksForUser(int $userId): Collection
    {
        $today = now()->toDateString();

        // Only show today's tasks, exclude completed (selesai) — they auto-hide after approval
        $tasks = CleaningTask::forUser($userId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', CleaningTask::STATUS_SELESAI)
            ->with(['room.roomType', 'room.bookings' => fn($q) => $q->whereIn('status', ['checked_in', 'pending', 'confirmed', 'checked_out'])->with('bookingSource')->orderByDesc('id'), 'checklistItems'])
            ->get()
            ->sortBy(function ($task) {
                $priority = [
                    CleaningTask::STATUS_REVISI => 1,
                    CleaningTask::STATUS_BELUM_MULAI => 2,
                    CleaningTask::STATUS_SEDANG_DIKERJAKAN => 3,
                    CleaningTask::STATUS_MENUNGGU_VERIFIKASI => 4,
                    CleaningTask::STATUS_SELESAI => 5,
                ];
                return $priority[$task->status] ?? 6;
            })
            ->values();

        // Also include dirty/checkout rooms with no task assigned
        $roomsWithoutTask = Room::where('hotel_id', active_hotel_id())
            ->whereIn('status', ['dirty', 'Checkout', 'Room Refresh'])
            ->whereDoesntHave('cleaningTasks', fn($q) => $q->where('assigned_to', $userId))
            ->with(['roomType', 'bookings' => fn($q) => $q->whereIn('status', ['checked_in', 'pending', 'confirmed', 'checked_out'])->with('bookingSource')->orderByDesc('id')])
            ->get()
            ->map(fn($room) => (object) [
                'id' => null,
                'status' => null,
                'room' => $room,
                'checklistItems' => collect(),
                'dirty_room_unassigned' => true,
                'room_id' => $room->id,
            ]);

        return $tasks->concat($roomsWithoutTask);
    }

    /**
     * Get count of pending (belum_mulai) tasks for a user.
     */
    public function getPendingTaskCount(int $userId): int
    {
        return CleaningTask::forUser($userId)
            ->whereDate('created_at', now()->toDateString())
            ->belumMulai()
            ->count();
    }

    /**
     * Get today's completed tasks for a user.
     */
    public function getTodayCompletedTasks(int $userId): Collection
    {
        return CleaningTask::forUser($userId)
            ->whereDate('completed_at', now()->toDateString())
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->with([
                'room.roomType',
                'room.bookings' => fn($q) => $q->whereIn('status', ['checked_in', 'pending', 'confirmed', 'checked_out'])->with('bookingSource')->orderByDesc('id'),
                'checklistItems',
            ])
            ->orderByDesc('completed_at')
            ->get();
    }

    /**
     * Start a task (change status to sedang_dikerjakan).
     * Also updates room status to 'Room Refresh' to reflect cleaning in progress.
     */
    public function startTask(CleaningTask $task): CleaningTask
    {
        if ($task->status !== CleaningTask::STATUS_BELUM_MULAI) {
            return $task;
        }

        $task->update([
            'status' => CleaningTask::STATUS_SEDANG_DIKERJAKAN,
            'started_at' => now(),
        ]);

        // Update room status to "Room Refresh" (cleaning in progress)
        if ($task->room && $task->room->status !== 'Room Refresh') {
            $task->room->update(['status' => 'Room Refresh']);
        }

        return $task->fresh();
    }

    /**
     * Complete a task (change status to selesai).
     */
    public function completeTask(CleaningTask $task, ?string $notes = null): CleaningTask
    {
        $update = [
            'status' => CleaningTask::STATUS_SELESAI,
            'completed_at' => now(),
        ];

        if ($notes !== null) {
            $update['notes'] = $notes;
        }

        $task->update($update);

        return $task->fresh();
    }

    /**
     * Toggle a checklist item's is_done status.
     */
    public function toggleChecklistItem(CleaningChecklistItem $item): CleaningChecklistItem
    {
        $item->update([
            'is_done' => !$item->is_done,
        ]);

        return $item->fresh();
    }

    /**
     * Add a photo to a cleaning task.
     * Validates: max 5 photos total, format jpg/jpeg/png, max 5MB.
     */
    public function addPhoto(CleaningTask $task, UploadedFile $file, string $type = 'general'): CleaningTaskPhoto
    {
        $currentCount = $task->photos()->count();
        if ($currentCount >= 10) {
            throw new \Exception('Maksimal 10 foto per task.');
        }

        // Validate file type and size
        $allowedMimes = ['image/jpeg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Format file harus JPG atau PNG.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Ukuran file maksimal 5MB.');
        }

        $path = $file->store('cleaning-photos', 'public');

        return CleaningTaskPhoto::create([
            'cleaning_task_id' => $task->id,
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Calculate progress percentage for a task.
     */
    public function calculateProgress(CleaningTask $task): float
    {
        return $task->calculateProgress();
    }

    /**
     * Get active checklist template items for a hotel.
     */
    public function getChecklistForHotel(int $hotelId): Collection
    {
        return CleaningChecklistTemplate::where('hotel_id', $hotelId)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Bulk create cleaning tasks using round-robin assignment.
     * Rooms are distributed evenly across staff members.
     * Skips rooms that already have active cleaning tasks.
     */
    public function bulkCreateTasks(array $roomIds, array $staffIds, int $assignedBy): array
    {
        $created = [];
        $skipped = [];

        // Get rooms that already have active tasks
        $activeRoomIds = CleaningTask::whereIn('room_id', $roomIds)
            ->whereIn('status', [
                CleaningTask::STATUS_BELUM_MULAI,
                CleaningTask::STATUS_SEDANG_DIKERJAKAN,
            ])
            ->pluck('room_id')
            ->toArray();

        $staffCount = count($staffIds);
        if ($staffCount === 0) {
            throw new \Exception('No staff selected.');
        }

        $staffIndex = 0;
        foreach ($roomIds as $roomId) {
            if (in_array($roomId, $activeRoomIds)) {
                $room = Room::find($roomId);
                $skipped[] = $room->room_number ?? "#{$roomId}";
                continue;
            }

            $room = Room::findOrFail($roomId);
            $assignedTo = $staffIds[$staffIndex % $staffCount];
            $task = $this->createTask($room, $assignedTo, $assignedBy);
            $created[] = $task;
            $staffIndex++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}

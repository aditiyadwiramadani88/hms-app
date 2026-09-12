<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Services\CleaningTaskService;
use App\Services\RoomService;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    protected RoomService $roomService;

    public function __construct(RoomService $roomService, private CleaningTaskService $cleaningTaskService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Display the housekeeping dashboard with room statuses.
     */
    public function index(Request $request)
    {
        $roomStatuses = $this->roomService->getRoomStatuses();

        $rooms = $roomStatuses['rooms'];

        if ($request->filled('floor')) {
            $rooms = $rooms->where('floor', $request->floor);
        }

        $rooms = $rooms->groupBy('status');
        $floors = Room::distinct('orderBy', 'floor')->pluck('floor');

        $cleanRooms = $roomStatuses['cleanRooms'] ?? collect();
        $dirtyRooms = $roomStatuses['dirtyRooms'] ?? collect();
        $cleaningRooms = $roomStatuses['cleaningRooms'] ?? collect();
        $occupiedRooms = $roomStatuses['occupiedRooms'] ?? collect();
        $maintenanceRooms = $roomStatuses['maintenanceRooms'] ?? collect();
        $outOfOrderRooms = $roomStatuses['outOfOrderRooms'] ?? collect();
        $otherRooms = $roomStatuses['otherRooms'] ?? collect();
        $allRooms = $roomStatuses['allRooms'] ?? collect();

        // Get only users with Housekeeping or OB role
        $hotelId = active_hotel_id();
        $housekeepingRoleIds = \Spatie\Permission\Models\Role::where('is_housekeeping_staff', true)
            ->orWhere('name', 'like', '%Housekeeping%')
            ->orWhere('name', 'like', '%OB%')
            ->pluck('id');

        $teamColumn = config('permission.column_names.team_foreign_key', 'hotel_id');
        $userIds = \DB::table('model_has_roles')
            ->whereIn('role_id', $housekeepingRoleIds)
            ->where($teamColumn, $hotelId)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        if ($userIds->isNotEmpty()) {
            $staffs = User::whereIn('id', $userIds)
                ->orderBy('name')
                ->get();
        } else {
            // Fallback: Get all users in this hotel if no specific roles found
            $staffs = User::whereHas('hotels', function($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->orderBy('name')
            ->get();
        }

        return view('housekeeping.index', compact('roomStatuses', 'rooms', 'floors', 'cleanRooms', 'dirtyRooms', 'cleaningRooms', 'occupiedRooms', 'maintenanceRooms', 'outOfOrderRooms', 'otherRooms', 'allRooms', 'staffs'));
    }

    /**
     * Mark a room as clean (transitions from dirty/cleaning to available).
     */
    public function markClean(Room $room)
    {
        try {
            // Auto-close any active tasks for this room
            \App\Models\CleaningTask::where('room_id', $room->id)
                ->whereIn('status', [
                    \App\Models\CleaningTask::STATUS_BELUM_MULAI,
                    \App\Models\CleaningTask::STATUS_SEDANG_DIKERJAKAN,
                ])
                ->update([
                    'status' => \App\Models\CleaningTask::STATUS_SELESAI,
                    'completed_at' => now(),
                ]);

            // Clear assigned staff
            $room->update(['assigned_staff_id' => null]);

            $this->roomService->markRoomClean($room);

            return redirect()->back()
                ->with('success', "Room {$room->room_number} marked as clean.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a room as dirty (typically after inspection finds it needs cleaning).
     */
    public function markDirty(Room $room)
    {
        try {
            $this->roomService->updateRoomStatus($room, 'dirty', 'Marked dirty by housekeeping');

            return redirect()->back()
                ->with('success', "Room {$room->room_number} marked as dirty.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update room status (general purpose).
     */
    public function updateStatus(Request $request, Room $room)
    {
        $validated = $request->validate([
            'status' => 'required|in:Available,In-House,Checkin,Checkout,Room Refresh,dirty,Out of Order,maintenance',
            'assigned_staff_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            // Close any active tasks for this room before re-assigning or changing status
            $activeTasks = \App\Models\CleaningTask::where('room_id', $room->id)
                ->whereIn('status', [
                    \App\Models\CleaningTask::STATUS_BELUM_MULAI,
                    \App\Models\CleaningTask::STATUS_SEDANG_DIKERJAKAN,
                ])
                ->get();

            foreach ($activeTasks as $activeTask) {
                $activeTask->update([
                    'status' => \App\Models\CleaningTask::STATUS_SELESAI,
                    'completed_at' => now(),
                    'notes' => ($activeTask->notes ? $activeTask->notes . ' | ' : '') . 'Auto-closed by admin (status changed to ' . $validated['status'] . ')',
                ]);
            }

            // If status is Clean/Available, clear assigned staff
            if ($validated['status'] === 'Available') {
                $validated['assigned_staff_id'] = null;
                $room->update(['assigned_staff_id' => null]);
            }

            $this->roomService->updateRoomStatus(
                $room,
                $validated['status'],
                $validated['reason'] ?? null,
                $validated['assigned_staff_id'] ?? null
            );

            // Create cleaning task if staff is assigned
            if (!empty($validated['assigned_staff_id'])) {
                $task = $this->cleaningTaskService->createTask(
                    $room,
                    (int) $validated['assigned_staff_id'],
                    auth()->id()
                );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Room status updated successfully.',
                    'room' => $room->refresh()->only(['id', 'room_number', 'status']),
                    'task_id' => $task->id ?? null,
                ]);
            }

            return redirect()->back()
                ->with('success', "Room {$room->room_number} status updated to {$validated['status']}.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Search staff for Select2 dropdown.
     * Only returns users with Housekeeping or OB role.
     */
    public function searchStaff(Request $request)
    {
        $term = $request->get('term', '');
        $hotelId = active_hotel_id();

        // Get roles marked as housekeeping staff
        $housekeepingRoleIds = \Spatie\Permission\Models\Role::where('is_housekeeping_staff', true)->pluck('id');

        // With Spatie teams enabled, roles are assigned per hotel
        // Query model_has_roles directly with team context
        $userIds = collect();

        if ($housekeepingRoleIds->isNotEmpty()) {
            $teamColumn = config('permission.column_names.team_foreign_key', 'hotel_id');
            $userIds = \DB::table('model_has_roles')
                ->whereIn('role_id', $housekeepingRoleIds)
                ->where($teamColumn, $hotelId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');
        }

        // Fallback: if no users found via flag, try name matching
        if ($userIds->isEmpty()) {
            $fallbackRoleIds = \Spatie\Permission\Models\Role::where('name', 'like', '%Housekeeping%')
                ->orWhere('name', 'like', '%OB%')
                ->pluck('id');

            $teamColumn = config('permission.column_names.team_foreign_key', 'hotel_id');
            $userIds = \DB::table('model_has_roles')
                ->whereIn('role_id', $fallbackRoleIds)
                ->where($teamColumn, $hotelId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');
        }

        // If still empty, return all users in this hotel
        if ($userIds->isEmpty()) {
            $staffs = User::whereHas('hotels', function($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            })
            ->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            })
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->limit(20)
            ->get();
        } else {
            $staffs = User::whereIn('id', $userIds)
                ->where(function($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                })
                ->select(['id', 'name', 'email'])
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $results = $staffs->map(fn($user) => [
            'id' => $user->id,
            'text' => $user->name . ' (' . $user->email . ')',
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * Get cleaning task details for a room (for admin monitoring).
     */
    public function getTaskDetails(Room $room)
    {
        $task = \App\Models\CleaningTask::where('room_id', $room->id)
            ->whereIn('status', ['belum_mulai', 'sedang_dikerjakan', 'selesai'])
            ->with(['assignedUser', 'checklistItems', 'photos'])
            ->latest()
            ->first();

        if (!$task) {
            return response()->json(['task' => null]);
        }

        $progress = $task->checklistItems->count() > 0
            ? round(($task->checklistItems->where('is_done', true)->count() / $task->checklistItems->count()) * 100)
            : 0;

        return response()->json([
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'assigned_to' => $task->assigned_to,
                'assigned_name' => $task->assignedUser->name ?? '-',
                'started_at' => $task->started_at?->format('d/m/Y H:i'),
                'completed_at' => $task->completed_at?->format('d/m/Y H:i'),
                'notes' => $task->notes,
                'progress' => $progress,
                'checklist' => $task->checklistItems->map(fn($item) => [
                    'name' => $item->name,
                    'is_done' => $item->is_done,
                ]),
                'photos' => $task->photos->map(fn($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'original_name' => $photo->original_name,
                ]),
            ]
        ]);
    }

    /**
     * Quick assign cleaning staff to multiple dirty rooms.
     */
    public function quickAssign(Request $request)
    {
        $request->validate([
            'room_ids' => 'required|array',
            'room_ids.*' => 'exists:rooms,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $hotelId = active_hotel_id();
        $assignedTo = $request->assigned_to;
        $roomIds = $request->room_ids;

        $created = 0;
        foreach ($roomIds as $roomId) {
            $room = Room::find($roomId);
            if (!$room) {
                continue;
            }

            $this->cleaningTaskService->createTask($room, $assignedTo, auth()->id());
            $room->update(['assigned_staff_id' => $assignedTo]);

            $created++;
        }

        return redirect()->back()
            ->with('success', "{$created} kamar berhasil di-assign ke cleaning staff.");
    }

    /**
     * Auto-assign cleaning staff to rooms using round-robin.
     */
    public function autoAssign(Request $request)
    {
        $request->validate([
            'room_ids' => 'required|array',
            'room_ids.*' => 'exists:rooms,id',
            'staff_ids' => 'required|array',
            'staff_ids.*' => 'exists:users,id',
        ]);

        try {
            $result = $this->cleaningTaskService->bulkCreateTasks(
                $request->room_ids,
                $request->staff_ids,
                auth()->id()
            );

            $message = count($result['created']) . ' task berhasil dibuat.';
            if (count($result['skipped']) > 0) {
                $message .= ' Kamar dilewati (sudah punya task aktif): ' . implode(', ', $result['skipped']);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

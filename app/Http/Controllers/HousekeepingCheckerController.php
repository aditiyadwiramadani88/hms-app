<?php

namespace App\Http\Controllers;

use App\Models\CleaningChecklistItem;
use App\Models\CleaningTask;
use App\Models\CleaningTaskLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HousekeepingCheckerController extends Controller
{
    public function index()
    {
        $tasks = CleaningTask::where('status', CleaningTask::STATUS_MENUNGGU_VERIFIKASI)
            ->with(['room.roomType', 'assignedUser', 'checklistItems', 'photos'])
            ->latest()
            ->get();

        // History: tasks that have been verified (approved/rejected/revised)
        $history = CleaningTask::whereIn('status', [
                CleaningTask::STATUS_SELESAI,
                CleaningTask::STATUS_REVISI,
            ])
            ->with(['room.roomType', 'assignedUser', 'logs' => fn($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        return view('housekeeping.checker.dashboard', compact('tasks', 'history'));
    }

    public function show(CleaningTask $task)
    {
        $task->load([
            'room.roomType',
            'assignedUser',
            'checklistItems' => fn($q) => $q->orderBy('sort_order'),
            'logs' => fn($q) => $q->with('user')->latest(),
        ]);

        return view('housekeeping.checker.task-detail', compact('task'));
    }

    /**
     * Approve entire task - mark as selesai.
     */
    public function approve(Request $request, CleaningTask $task)
    {
        // Mark all checklist items as done
        $task->checklistItems()->update([
            'is_done' => true,
            'verification_status' => 'approved',
        ]);

        $task->update(['status' => CleaningTask::STATUS_SELESAI]);

        // Update room to Available
        if ($task->room) {
            $task->room->update(['status' => 'Available']);
        }

        // Log
        CleaningTaskLog::create([
            'cleaning_task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'approved',
            'role' => 'checker',
            'note' => $request->input('note', 'Task approved by checker'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task approved! Kamar ditandai Available.',
        ]);
    }

    /**
     * Reject task - send back to OB for revision.
     */
    public function reject(Request $request, CleaningTask $task)
    {
        $request->validate([
            'note' => 'required|string|max:500',
        ]);

        $task->update(['status' => CleaningTask::STATUS_REVISI]);

        // Log
        CleaningTaskLog::create([
            'cleaning_task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'role' => 'checker',
            'note' => $request->input('note'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task dikembalikan ke OB untuk revisi.',
        ]);
    }

    /**
     * Verify individual checklist item (approve/reject).
     */
    public function verifyItem(Request $request, CleaningChecklistItem $item)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'note' => 'required_if:status,rejected|string|nullable|max:500',
        ]);

        $updateData = [
            'verification_status' => $request->status,
            'checker_note' => $request->note,
        ];

        // If rejected, reset is_done so OB must redo with new photo
        if ($request->status === 'rejected') {
            $updateData['is_done'] = false;
            // Don't delete old photo — keep for history reference
            // photo_path and photo_url stay so timeline can show old photo
        }

        $item->update($updateData);

        // If rejected, also set task status to revisi so OB can re-edit
        if ($request->status === 'rejected') {
            $item->cleaningTask->update(['status' => CleaningTask::STATUS_REVISI]);
        }

        // Log
        CleaningTaskLog::create([
            'cleaning_task_id' => $item->cleaning_task_id,
            'cleaning_checklist_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => $request->status === 'approved' ? 'item_approved' : 'item_rejected',
            'role' => 'checker',
            'note' => $request->note ?? "Item '{$item->name}' {$request->status}",
        ]);

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    /**
     * Upload checker evidence photo for a checklist item (for comparison).
     */
    public function uploadCheckerEvidence(Request $request, CleaningChecklistItem $item)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:5120',
            'note' => 'nullable|string|max:500',
        ]);

        $path = $request->file('photo')->store('checker-evidence', 'public');

        // Log with photo
        CleaningTaskLog::create([
            'cleaning_task_id' => $item->cleaning_task_id,
            'cleaning_checklist_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'checker_photo',
            'role' => 'checker',
            'note' => $request->input('note', 'Foto pembanding dari checker'),
            'photo_path' => $path,
            'photo_url' => asset('storage/' . $path),
        ]);

        return response()->json([
            'success' => true,
            'photo_url' => asset('storage/' . $path),
        ]);
    }
}

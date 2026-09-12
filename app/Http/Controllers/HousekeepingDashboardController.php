<?php

namespace App\Http\Controllers;

use App\Models\CleaningChecklistItem;
use App\Models\CleaningTask;
use App\Models\CleaningTaskPhoto;
use App\Services\CleaningTaskService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class HousekeepingDashboardController extends Controller
{
    public function __construct(
        private CleaningTaskService $taskService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $tasks = $this->taskService->getTasksForUser($user->id);
        $pendingCount = $this->taskService->getPendingTaskCount($user->id);
        $completedTasks = $this->taskService->getTodayCompletedTasks($user->id);

        return view('housekeeping.my-tasks', compact('tasks', 'pendingCount', 'completedTasks'));
    }

    public function show(CleaningTask $task)
    {
        Gate::authorize('view', $task);

        // Auto-start task if belum_mulai
        if ($task->status === CleaningTask::STATUS_BELUM_MULAI) {
            $task->load('room'); // Ensure room is loaded for status update
            $this->taskService->startTask($task);
            $task->refresh();
        }

        // Auto-populate checklist items if empty (e.g. legacy/manually created tasks)
        if ($task->checklistItems()->count() === 0) {
            $this->taskService->populateChecklistForTask($task);
        }

        $task->load([
            'room.roomType',
            'checklistItems' => fn($q) => $q->orderBy('sort_order'),
            'photos',
            'logs' => fn($q) => $q->with('user')->latest(),
        ]);
        $progress = $this->taskService->calculateProgress($task);

        // Group logs by checklist item for timeline
        $logsByItem = $task->logs->groupBy('cleaning_checklist_item_id');

        return view('housekeeping.task-detail', compact('task', 'progress', 'logsByItem'));
    }

    public function updateChecklist(Request $request, CleaningChecklistItem $item)
    {
        Gate::authorize('update', $item->cleaningTask);

        // If mark_done is provided, mark as done/undone with optional comment (no photo required)
        if ($request->has('mark_done')) {
            $isDone = $request->boolean('mark_done');
            $item->update([
                'is_done' => $isDone,
                'comment' => $request->input('comment', $item->comment),
                'verification_status' => 'pending',
                'checker_note' => $isDone ? null : $item->checker_note,
            ]);
            return response()->json([
                'success' => true,
                'is_done' => $isDone,
            ]);
        }

        // If comment is provided, just update comment without toggling
        if ($request->has('comment')) {
            $item->update(['comment' => $request->input('comment')]);
            return response()->json([
                'success' => true,
                'is_done' => $item->is_done,
            ]);
        }

        $item = $this->taskService->toggleChecklistItem($item);

        return response()->json([
            'success' => true,
            'is_done' => $item->is_done,
        ]);
    }

    /**
     * Upload photo evidence and comment for a specific checklist item.
     * Keeps old photo in history log (never overwrites).
     */
    public function uploadChecklistEvidence(Request $request, CleaningChecklistItem $item)
    {
        Gate::authorize('update', $item->cleaningTask);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:5120',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            // Save old photo to history log before replacing
            if ($item->photo_path) {
                \App\Models\CleaningTaskLog::create([
                    'cleaning_task_id' => $item->cleaning_task_id,
                    'cleaning_checklist_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'action' => 'photo_replaced',
                    'role' => 'ob',
                    'note' => $item->comment ?? 'Foto sebelumnya',
                    'photo_path' => $item->photo_path,
                    'photo_url' => $item->photo_url,
                ]);
            }

            $path = $request->file('photo')->store('checklist-evidence', 'public');

            $item->update([
                'is_done' => true,
                'photo_path' => $path,
                'photo_url' => asset('storage/' . $path),
                'comment' => $request->input('comment'),
                'verification_status' => 'pending', // Reset verification for re-check
                'checker_note' => null, // Clear old checker note
            ]);

            // Log new submission
            \App\Models\CleaningTaskLog::create([
                'cleaning_task_id' => $item->cleaning_task_id,
                'cleaning_checklist_item_id' => $item->id,
                'user_id' => auth()->id(),
                'action' => 'evidence_submitted',
                'role' => 'ob',
                'note' => $request->input('comment') ?? 'Foto baru diupload',
                'photo_path' => $path,
                'photo_url' => asset('storage/' . $path),
            ]);

            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $item->id,
                    'is_done' => true,
                    'photo_url' => asset('storage/' . $path),
                    'comment' => $item->comment,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload foto: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function uploadPhoto(Request $request, CleaningTask $task)
    {
        Gate::authorize('update', $task);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:5120',
            'type' => 'nullable|in:before,after,general',
        ]);

        try {
            $photo = $this->taskService->addPhoto($task, $request->file('photo'), $request->input('type', 'general'));

            return response()->json([
                'success' => true,
                'photo' => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'type' => $photo->type,
                    'original_name' => $photo->original_name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function complete(Request $request, CleaningTask $task)
    {
        Gate::authorize('update', $task);

        $doneCount = $task->checklistItems()->where('is_done', true)->count();

        if ($doneCount === 0 && !$request->has('force')) {
            return response()->json([
                'success' => false,
                'confirm_required' => true,
                'message' => 'Belum ada item yang dicentang. Yakin selesai?',
            ], 422);
        }

        // Set status to menunggu_verifikasi so checker can review
        $task->update([
            'status' => CleaningTask::STATUS_MENUNGGU_VERIFIKASI,
            'completed_at' => now(),
            'notes' => $request->input('notes') ?? $task->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task dikirim untuk verifikasi checker.',
        ]);
    }

    public function badgeCount()
    {
        $count = $this->taskService->getPendingTaskCount(auth()->id());

        return response()->json(['count' => $count]);
    }

    /**
     * Delete a photo from a cleaning task.
     */
    public function deletePhoto(CleaningTaskPhoto $photo)
    {
        Gate::authorize('update', $photo->cleaningTask);

        try {
            // Delete file from storage
            if (Storage::disk('public')->exists($photo->file_path)) {
                Storage::disk('public')->delete($photo->file_path);
            }

            // Delete record from database
            $photo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus foto: ' . $e->getMessage(),
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\CleaningChecklistItem;
use App\Models\CleaningTask;
use App\Models\CleaningTaskPhoto;
use App\Services\CleaningTaskService;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OBDashboardController extends Controller
{
    public function __construct(
        private CleaningTaskService $taskService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $tasks = $this->taskService->getTasksForUser($user->id);
        $pendingCount = $this->taskService->getPendingTaskCount($user->id);

        return view('ob.dashboard', compact('tasks', 'pendingCount'));
    }

    public function show(CleaningTask $task)
    {
        Gate::authorize('view', $task);

        // Auto-start task if belum_mulai
        if ($task->status === CleaningTask::STATUS_BELUM_MULAI) {
            $task->load('room');
            $this->taskService->startTask($task);
            $task->refresh();
        }

        $task->load(['room.roomType', 'checklistItems' => fn($q) => $q->orderBy('sort_order'), 'photos']);
        $progress = $this->taskService->calculateProgress($task);

        return view('ob.task-detail', compact('task', 'progress'));
    }

    public function updateChecklist(Request $request, CleaningChecklistItem $item)
    {
        Gate::authorize('update', $item->cleaningTask);

        $item = $this->taskService->toggleChecklistItem($item);

        return response()->json([
            'success' => true,
            'is_done' => $item->is_done,
        ]);
    }

    public function uploadPhoto(Request $request, CleaningTask $task)
    {
        Gate::authorize('update', $task);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:5120',
        ]);

        try {
            $photo = $this->taskService->addPhoto($task, $request->file('photo'));

            return response()->json([
                'success' => true,
                'photo' => [
                    'id' => $photo->id,
                    'url' => $photo->url,
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

        $this->taskService->completeTask($task, $request->input('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Task completed successfully.',
        ]);
    }

    public function badgeCount()
    {
        $count = $this->taskService->getPendingTaskCount(auth()->id());

        return response()->json(['count' => $count]);
    }
}

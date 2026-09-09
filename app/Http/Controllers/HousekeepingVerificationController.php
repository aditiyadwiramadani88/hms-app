<?php

namespace App\Http\Controllers;

use App\Models\CleaningChecklistItem;
use App\Models\CleaningTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HousekeepingVerificationController extends Controller
{
    /**
     * Verify a specific checklist item.
     */
    public function verifyItem(Request $request, CleaningChecklistItem $item)
    {
        $request->validate([
            "status" => "required|in:approved,rejected",
            "note" => "required_if:status,rejected|string|nullable",
        ]);

        $item->update([
            "verification_status" => $request->status,
            "checker_note" => $request->note,
        ]);

        // Auto-promote task to selesai if all items are now approved
        $task = $item->cleaningTask;
        if ($task->status === CleaningTask::STATUS_REVISI) {
            $unapproved = $task
                ->checklistItems()
                ->where("verification_status", "!=", "approved")
                ->count();
            if ($unapproved === 0) {
                $task->update(["status" => CleaningTask::STATUS_SELESAI]);
            }
        }

        return response()->json(["success" => true]);
    }

    /**
     * Approve or Reject the entire task.
     */
    public function updateTaskStatus(Request $request, CleaningTask $task)
    {
        $request->validate([
            "status" => "required|in:selesai,revisi",
        ]);

        if ($request->status === "selesai") {
            // Check if all items are approved
            $unverified = $task
                ->checklistItems()
                ->where("verification_status", "!=", "approved")
                ->count();
            if ($unverified > 0) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Masih ada item yang belum di-approve!",
                    ],
                    422,
                );
            }
            $task->update(["status" => CleaningTask::STATUS_SELESAI]);
        } else {
            $task->update(["status" => CleaningTask::STATUS_REVISI]);
        }

        return response()->json(["success" => true]);
    }
}

<?php

namespace App\Policies;

use App\Models\CleaningTask;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CleaningTaskPolicy
{
    use HandlesAuthorization;

    public function view(User $user, CleaningTask $task): bool
    {
        return $user->id === $task->assigned_to
            || $user->hasPermissionTo('manage housekeeping');
    }

    public function update(User $user, CleaningTask $task): bool
    {
        return $user->id === $task->assigned_to
            && in_array($task->status, [
                CleaningTask::STATUS_BELUM_MULAI,
                CleaningTask::STATUS_SEDANG_DIKERJAKAN,
                CleaningTask::STATUS_REVISI,
            ]);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['OB', 'Housekeeping'])
            || $user->hasPermissionTo('manage housekeeping');
    }
}

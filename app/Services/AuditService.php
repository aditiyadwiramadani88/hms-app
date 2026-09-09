<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Create an audit log entry.
     *
     * @param string $action
     * @param string $description
     * @param mixed|null $model
     * @return AuditLog
     */
    public function log(string $action, string $description, $model = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Get a user's recent activity.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserActivity(int $userId, int $limit = 50): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity for a specific model.
     *
     * @param string $modelType Fully qualified class name (e.g., App\Models\Booking)
     * @param mixed $modelId
     * @return Collection
     */
    public function getModelActivity(string $modelType, $modelId): Collection
    {
        return AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user')
            ->latest('created_at')
            ->get();
    }

    /**
     * Get recent system-wide activity.
     *
     * @param int $limit
     * @return Collection
     */
    public function getSystemActivity(int $limit = 100): Collection
    {
        return AuditLog::with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity summary grouped by action type.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getActivitySummary(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = AuditLog::select(
            'action',
            \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
        )
            ->groupBy('action')
            ->orderByDesc('count');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get activity by model type with counts.
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostActiveModels(int $limit = 10): Collection
    {
        return AuditLog::select(
                'model_type',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as activity_count'),
                \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT model_id) as unique_entities'),
                \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->whereNotNull('model_type')
            ->groupBy('model_type')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity for a specific action type.
     *
     * @param string $action
     * @param int $limit
     * @return Collection
     */
    public function getActionActivity(string $action, int $limit = 50): Collection
    {
        return AuditLog::where('action', $action)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get most active users by audit log count.
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostActiveUsers(int $limit = 10): Collection
    {
        return AuditLog::select(
                'user_id',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as activity_count'),
                \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT action) as unique_actions'),
                \Illuminate\Support\Facades\DB::raw('MAX(created_at) as last_activity')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->get()
            ->load('user');
    }

    /**
     * Search audit logs by description.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchLogs(string $query, int $limit = 50): Collection
    {
        return AuditLog::where('description', 'like', "%{$query}%")
            ->orWhere('action', 'like', "%{$query}%")
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get IP address activity.
     *
     * @param string $ipAddress
     * @param int $limit
     * @return Collection
     */
    public function getIpActivity(string $ipAddress, int $limit = 50): Collection
    {
        return AuditLog::where('ip_address', $ipAddress)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics for a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getActivityStats(string $startDate, string $endDate): array
    {
        $totalActivities = AuditLog::whereBetween('created_at', [$startDate, $endDate])->count();

        $uniqueUsers = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        $actionsBreakdown = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->select('action', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        $dailyBreakdown = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_activities' => $totalActivities,
            'unique_users' => $uniqueUsers,
            'actions_breakdown' => $actionsBreakdown,
            'daily_breakdown' => $dailyBreakdown,
            'avg_per_day' => $dailyBreakdown->count() > 0
                ? round($totalActivities / $dailyBreakdown->count(), 2)
                : 0,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkOrderService
{
    /**
     * Create a new work order.
     */
    public function createWorkOrder(array $data): WorkOrder
    {
        return DB::transaction(function () use ($data) {
            $workOrder = WorkOrder::create([
                'hotel_id' => active_hotel_id(),
                'room_id' => $data['room_id'],
                'assigned_to' => $data['assigned_to'],
                'type' => $data['type'] ?? 'cleaning',
                'status' => 'pending',
                'bonus_amount' => $data['bonus_amount'] ?? 750,
                'notes' => $data['notes'] ?? null,
            ]);

            // Optionally update room status to 'Dirty'
            if (isset($data['room_id'])) {
                $room = Room::find($data['room_id']);
                if ($room) {
                    $room->update(['status' => 'dirty']);
                }
            }

            AuditService::log(
                'work_order.created',
                'Work order created',
                WorkOrder::class,
                $workOrder->id
            );

            return $workOrder;
        });
    }

    /**
     * Mark work order as in progress.
     */
    public function startWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {
            $workOrder->markAsInProgress();

            AuditService::log(
                'work_order.started',
                'Work order started',
                WorkOrder::class,
                $workOrder->id
            );

            return $workOrder;
        });
    }

    /**
     * Mark work order as completed.
     */
    public function completeWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {
            $workOrder->markAsCompleted();

            // Update room status to 'Clean'
            $room = $workOrder->room;
            if ($room) {
                $room->update(['status' => 'clean']);
            }

            AuditService::log(
                'work_order.completed',
                'Work order completed',
                WorkOrder::class,
                $workOrder->id
            );

            return $workOrder;
        });
    }

    /**
     * Get work orders with filters.
     */
    public function getWorkOrders(array $filters = [])
    {
        $query = WorkOrder::with(['room.roomType', 'assignee']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Get work orders assigned to a specific OB.
     */
    public function getWorkOrdersForOB(User $ob, ?Carbon $date = null)
    {
        $query = WorkOrder::with(['room.roomType'])
            ->where('assigned_to', $ob->id);

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->latest()->get();
    }

    /**
     * Get completed work orders for a specific OB in a date range.
     */
    public function getCompletedWorkOrdersForOB(User $ob, Carbon $from, Carbon $to)
    {
        return WorkOrder::where('assigned_to', $ob->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->get();
    }
}

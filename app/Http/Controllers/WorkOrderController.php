<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Room;
use App\Models\User;
use App\Services\WorkOrderService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class WorkOrderController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected WorkOrderService $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display a listing of work orders.
     */
    public function index(Request $request)
    {
        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }

        if ($request->filled('assigned_to')) {
            $filters['assigned_to'] = $request->assigned_to;
        }

        if ($request->filled('type')) {
            $filters['type'] = $request->type;
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }

        $workOrders = $this->workOrderService->getWorkOrders($filters);
        $obs = $this->getOBList();
        $rooms = Room::orderBy('room_number')->get();

        return view('work_orders.index', compact('workOrders', 'obs', 'rooms'));
    }

    /**
     * Show the form for creating a new work order.
     */
    public function create()
    {
        $obs = $this->getOBList();
        $rooms = Room::where('status', 'dirty')
            ->orWhere('status', 'maintenance')
            ->orderBy('room_number')
            ->get();

        return view('work_orders.create', compact('obs', 'rooms'));
    }

    /**
     * Store a newly created work order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'assigned_to' => 'required|exists:users,id',
            'type' => 'required|in:cleaning,maintenance,other',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $workOrder = $this->workOrderService->createWorkOrder($validated);

            return $this->ajaxOrRedirect('Work order created successfully.', route('work-orders.show', $workOrder), $workOrder, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified work order.
     */
    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['room.roomType', 'assignee']);

        return view('work_orders.show', compact('workOrder'));
    }

    /**
     * Mark work order as in progress.
     */
    public function start(WorkOrder $workOrder)
    {
        try {
            $this->workOrderService->startWorkOrder($workOrder);

            return $this->ajaxOrRedirect('Work order started.', route('work-orders.show', $workOrder), $workOrder);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark work order as completed.
     */
    public function complete(WorkOrder $workOrder)
    {
        try {
            $this->workOrderService->completeWorkOrder($workOrder);

            return $this->ajaxOrRedirect('Work order completed.', route('work-orders.show', $workOrder), $workOrder);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get list of OBs for assignment.
     */
    protected function getOBList()
    {
        $obRole = Role::where('name', 'like', '%ob%')
            ->orWhere('name', 'like', '%cleaner%')
            ->orWhere('name', 'like', '%housekeeping%')
            ->first();

        if ($obRole) {
            return User::role($obRole->name)->orderBy('name')->get();
        }

        return User::whereHas('roles', function ($query) {
            $query->where('name', 'like', '%ob%')
                ->orWhere('name', 'like', '%cleaner%')
                ->orWhere('name', 'like', '%housekeeping%');
        })->orderBy('name')->get();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\User;
use App\Services\BonusReportService;
use App\Services\WorkOrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    protected WorkOrderService $workOrderService;
    protected BonusReportService $bonusReportService;

    public function __construct(WorkOrderService $workOrderService, BonusReportService $bonusReportService)
    {
        $this->workOrderService = $workOrderService;
        $this->bonusReportService = $bonusReportService;
    }

    /**
     * Get work orders for the authenticated OB.
     */
    public function myWorkOrders(Request $request)
    {
        $ob = $request->user();
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $workOrders = $this->workOrderService->getWorkOrdersForOB($ob, $date);

        return response()->json([
            'success' => true,
            'data' => $workOrders,
        ]);
    }

    /**
     * Start a work order.
     */
    public function startWorkOrder(WorkOrder $workOrder)
    {
        try {
            $workOrder = $this->workOrderService->startWorkOrder($workOrder);

            return response()->json([
                'success' => true,
                'data' => $workOrder,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete a work order.
     */
    public function completeWorkOrder(WorkOrder $workOrder)
    {
        try {
            $workOrder = $this->workOrderService->completeWorkOrder($workOrder);

            return response()->json([
                'success' => true,
                'data' => $workOrder,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get monthly bonus report.
     */
    public function monthlyBonusReport(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $report = $this->bonusReportService->calculateMonthlyBonus($month);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get bonus detail for authenticated OB.
     */
    public function myBonusDetail(Request $request)
    {
        $ob = $request->user();
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $detail = $this->bonusReportService->getOBBonusDetail($ob, $month);

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }
}

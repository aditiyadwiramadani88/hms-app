<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BonusReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BonusReportController extends Controller
{
    protected BonusReportService $bonusReportService;

    public function __construct(BonusReportService $bonusReportService)
    {
        $this->bonusReportService = $bonusReportService;
    }

    /**
     * Display the monthly bonus report.
     */
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $report = $this->bonusReportService->calculateMonthlyBonus($month);

        return view('bonus_reports.index', compact('report', 'month'));
    }

    /**
     * Display bonus detail for a specific OB.
     */
    public function show(Request $request, User $ob)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $detail = $this->bonusReportService->getOBBonusDetail($ob, $month);

        return view('bonus_reports.show', compact('detail', 'ob', 'month'));
    }

    /**
     * Export bonus report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $report = $this->bonusReportService->exportBonusReport($month);

        // You can implement Excel export here using Maatwebsite\Excel
        // For now, return JSON as example
        return response()->json($report);
    }

    /**
     * Export bonus report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $report = $this->bonusReportService->exportBonusReport($month);

        // You can implement PDF export here using Barryvdh\DomPDF
        // For now, return JSON as example
        return response()->json($report);
    }
}

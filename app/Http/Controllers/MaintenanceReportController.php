<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecord;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = MaintenanceRecord::where('hotel_id', $hotelId)
            ->with(['room', 'category', 'creator']);

        if ($request->filled('date_from')) {
            $query->whereDate('maintenance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('maintenance_date', '<=', $request->date_to);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $records = $query->orderBy('maintenance_date', 'desc')->get();

        return view('maintenance.report', compact('records'));
    }

    public function exportPdf(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = MaintenanceRecord::where('hotel_id', $hotelId)
            ->with(['room', 'category', 'creator']);

        if ($request->filled('date_from')) {
            $query->whereDate('maintenance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('maintenance_date', '<=', $request->date_to);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $records = $query->orderBy('maintenance_date', 'desc')->get();
        $hotelName = \App\Models\Hotel::find($hotelId)?->name ?? 'Simpang Homestay';
        $dateFrom = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from)->format('d M Y') : '-';
        $dateTo = $request->filled('date_to') ? \Carbon\Carbon::parse($request->date_to)->format('d M Y') : '-';

        $pdf = Pdf::loadView('maintenance.report_pdf', compact('records', 'hotelName', 'dateFrom', 'dateTo'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('laporan-maintenance-'.now()->format('Ymd').'.pdf');
    }
}

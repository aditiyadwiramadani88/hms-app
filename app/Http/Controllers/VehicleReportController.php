<?php

namespace App\Http\Controllers;

use App\Exports\ParkingReportExport;
use App\Models\VehicleLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class VehicleReportController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $query = VehicleLog::where('hotel_id', $hotelId)
            ->whereDate('time_in', $date)
            ->with(['guestVehicle.guest', 'booking.room', 'securityIn', 'securityOut'])
            ->latest('time_in');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('link_status')) {
            if ($request->link_status === 'linked') {
                $query->whereNotNull('booking_id');
            } elseif ($request->link_status === 'unlinked') {
                $query->whereNull('booking_id');
            }
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => VehicleLog::where('hotel_id', $hotelId)->whereDate('time_in', $date)->count(),
            'inside' => VehicleLog::where('hotel_id', $hotelId)->whereDate('time_in', $date)->where('status', 'in')->count(),
            'linked' => VehicleLog::where('hotel_id', $hotelId)->whereDate('time_in', $date)->whereNotNull('booking_id')->count(),
            'unlinked' => VehicleLog::where('hotel_id', $hotelId)->whereDate('time_in', $date)->whereNull('booking_id')->count(),
        ];

        return view('security-gate.report', compact('logs', 'stats', 'date'));
    }

    public function parkingReport(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $logs = VehicleLog::where('hotel_id', $hotelId)
            ->whereDate('time_in', $date)
            ->with(['booking.room', 'booking.guest', 'guestVehicle'])
            ->get();

        $grouped = [];
        foreach ($logs as $log) {
            $roomNumber = $log->booking?->room?->room_number ?? $log->booking?->custom_room_name ?? $log->destination_room ?? 'TANPA KAMAR';
            $stayType = $log->booking?->stay_type ?? 'daily';
            $guestName = $log->booking?->guest?->name ?? ($log->guestVehicle?->guest?->name ?? $log->driver_name);

            if (!isset($grouped[$roomNumber])) {
                $grouped[$roomNumber] = [
                    'room' => $roomNumber,
                    'guest_name' => $guestName,
                    'mobil_harian' => [],
                    'mobil_kost' => [],
                    'motor_harian' => [],
                    'motor_kost' => [],
                    'has_photo' => false,
                ];
            }

            $category = ($stayType === 'monthly') ? 'kost' : 'harian';
            $type = $log->vehicle_type;

            $key = "{$type}_{$category}";
            $grouped[$roomNumber][$key][] = $log;

            if ($log->photo_in) {
                $grouped[$roomNumber]['has_photo'] = true;
            }
        }

        // Sort: rooms with numbers first, then "TANPA KAMAR"
        $sorted = [];
        foreach ($grouped as $room => $data) {
            if ($room === 'TANPA KAMAR') {
                $sorted['TANPA KAMAR'] = $data;
            } else {
                $sorted[$room] = $data;
            }
        }

        $totals = [
            'mobil_harian' => 0,
            'mobil_kost' => 0,
            'motor_harian' => 0,
            'motor_kost' => 0,
        ];
        foreach ($grouped as $data) {
            $totals['mobil_harian'] += count($data['mobil_harian']);
            $totals['mobil_kost'] += count($data['mobil_kost']);
            $totals['motor_harian'] += count($data['motor_harian']);
            $totals['motor_kost'] += count($data['motor_kost']);
        }

        return view('security-gate.parking-report', compact('sorted', 'totals', 'date'));
    }

    public function detail(VehicleLog $vehicleLog)
    {
        $vehicleLog->load([
            'guestVehicle.guest',
            'booking.room',
            'booking.guest',
            'securityIn',
            'securityOut',
        ]);

        $photoInUrl = $vehicleLog->photo_in ? asset('storage/' . $vehicleLog->photo_in) : null;
        $photoOutUrl = $vehicleLog->photo_out ? asset('storage/' . $vehicleLog->photo_out) : null;

        return response()->json([
            'plate_number' => $vehicleLog->plate_number,
            'driver_name' => $vehicleLog->driver_name,
            'vehicle_type' => $vehicleLog->vehicle_type,
            'vehicle_color' => $vehicleLog->vehicle_color,
            'purpose' => $vehicleLog->purpose,
            'destination_room' => $vehicleLog->destination_room,
            'time_in' => $vehicleLog->time_in?->format('d/m/Y H:i'),
            'time_out' => $vehicleLog->time_out?->format('d/m/Y H:i'),
            'status' => $vehicleLog->status,
            'notes' => $vehicleLog->notes,
            'photo_in' => $photoInUrl,
            'photo_out' => $photoOutUrl,
            'security_in' => $vehicleLog->securityIn?->name,
            'security_out' => $vehicleLog->securityOut?->name,
            'guest' => $vehicleLog->booking?->guest?->name ?? $vehicleLog->guestVehicle?->guest?->name ?? null,
            'room' => $vehicleLog->booking?->room?->room_number ?? $vehicleLog->booking?->custom_room_name ?? $vehicleLog->destination_room ?? null,
            'booking_id' => $vehicleLog->booking_id,
            'is_linked' => $vehicleLog->booking_id !== null,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $logs = VehicleLog::where('hotel_id', $hotelId)
            ->whereDate('time_in', $date)
            ->with(['booking.room', 'booking.guest', 'guestVehicle'])
            ->get();

        $grouped = [];
        foreach ($logs as $log) {
            $roomNumber = $log->booking?->room?->room_number ?? $log->booking?->custom_room_name ?? $log->destination_room ?? 'TANPA KAMAR';
            $stayType = $log->booking?->stay_type ?? 'daily';
            $guestName = $log->booking?->guest?->name ?? ($log->guestVehicle?->guest?->name ?? $log->driver_name);

            if (!isset($grouped[$roomNumber])) {
                $grouped[$roomNumber] = [
                    'room' => $roomNumber,
                    'guest_name' => $guestName,
                    'mobil_harian' => [],
                    'mobil_kost' => [],
                    'motor_harian' => [],
                    'motor_kost' => [],
                ];
            }

            $category = ($stayType === 'monthly') ? 'kost' : 'harian';
            $type = $log->vehicle_type;
            $key = "{$type}_{$category}";
            $grouped[$roomNumber][$key][] = $log;
        }

        $totals = [
            'mobil_harian' => 0,
            'mobil_kost' => 0,
            'motor_harian' => 0,
            'motor_kost' => 0,
        ];
        foreach ($grouped as $data) {
            $totals['mobil_harian'] += count($data['mobil_harian']);
            $totals['mobil_kost'] += count($data['mobil_kost']);
            $totals['motor_harian'] += count($data['motor_harian']);
            $totals['motor_kost'] += count($data['motor_kost']);
        }

        $hotel = current_hotel();
        $pdf = Pdf::loadView('security-gate.parking_report_pdf', compact('grouped', 'totals', 'date', 'hotel'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('laporan_parkir_' . $date->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $filename = 'laporan_parkir_' . $date->format('Y_m_d') . '.xlsx';

        return Excel::download(new ParkingReportExport($date), $filename);
    }
}

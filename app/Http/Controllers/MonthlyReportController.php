<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyReportController extends Controller
{
    use AjaxResponse;

    public function index(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->filled('month') ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();

            $reportData = $this->buildMonthlyReport($hotelId, $month);
            $hotel = current_hotel();

            return view('reports.monthly', compact('reportData', 'month', 'hotel'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function buildMonthlyReport(int $hotelId, Carbon $month): array
    {
        $bookings = Booking::with([
            'guest',
            'room',
            'bookingSource',
            'transactions' => function ($q) {
                $q->where('type', 'payment')->where('status', 'success');
            },
            'user',
        ])
            ->where('hotel_id', $hotelId)
            ->where('status', 'checked_out')
            ->whereYear('check_out', $month->year)
            ->whereMonth('check_out', $month->month)
            ->orderBy('check_out', 'asc')
            ->get();

        $grouped = $bookings->groupBy(function ($booking) {
            return $booking->check_out->format('Y-m-d');
        });

        $dateGroups = [];
        $grandTotals = ['agoda' => 0, 'redd' => 0, 'travel' => 0, 'online_tf' => 0];

        foreach ($grouped as $dateStr => $group) {
            $date = Carbon::parse($dateStr);
            $rows = [];
            $subtotal = ['agoda' => 0, 'redd' => 0, 'travel' => 0, 'online_tf' => 0];
            $no = 1;

            foreach ($group as $booking) {
                $sourceName = $booking->bookingSource?->name ?? $booking->source;
                $column = $this->mapSourceToColumn($sourceName);

                $amount = (float) $booking->transactions->sum('amount');
                $paymentDate = $booking->transactions->first()?->created_at;

                $row = [
                    'no' => $no++,
                    'tgl_laporan' => $date->day,
                    'nama' => $booking->guest?->name ?? '-',
                    'room' => $booking->room?->room_number ?? ($booking->custom_room_name ?? '-'),
                    'booking_id' => $booking->id,
                    'check_in' => $booking->check_in ? $booking->check_in->format('d-M') : '-',
                    'check_out' => $booking->check_out ? $booking->check_out->format('d-M') : '-',
                    'agoda' => $column === 'agoda' ? $amount : 0,
                    'redd' => $column === 'redd' ? $amount : 0,
                    'travel' => $column === 'travel' ? $amount : 0,
                    'online_tf' => $column === 'online_tf' ? $amount : 0,
                    'tgl_tf' => $paymentDate ? $paymentDate->format('d M') : '-',
                    'jenis_anggota' => $booking->guest_type ?? '-',
                    'sales' => $booking->user?->name ?? '-',
                ];

                $subtotal[$column] += $amount;
                $grandTotals[$column] += $amount;

                $rows[] = $row;
            }

            $dateGroups[] = [
                'date' => $date,
                'day' => $date->day,
                'formatted_date' => $date->translatedFormat('l, d F Y'),
                'rows' => $rows,
                'subtotal' => $subtotal,
            ];
        }

        return [
            'dateGroups' => $dateGroups,
            'grandTotals' => $grandTotals,
            'grandTotal' => array_sum($grandTotals),
        ];
    }

    private function mapSourceToColumn(?string $sourceName): string
    {
        if (!$sourceName) return 'online_tf';

        $sourceName = strtolower(trim($sourceName));

        $mapping = [
            'agoda' => 'agoda',
            'reddoorz' => 'redd',
            'redd' => 'redd',
            'traveloka' => 'travel',
            'travel' => 'travel',
        ];

        return $mapping[$sourceName] ?? 'online_tf';
    }

    public function exportPdf(Request $request)
    {
        try {
            $hotelId = active_hotel_id();
            $month = $request->filled('month') ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();

            $reportData = $this->buildMonthlyReport($hotelId, $month);
            $hotel = current_hotel();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.monthly_pdf', [
                'reportData' => $reportData,
                'month' => $month,
                'hotel' => $hotel,
            ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('laporan_bulanan_' . $month->format('Y-m') . '.pdf');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

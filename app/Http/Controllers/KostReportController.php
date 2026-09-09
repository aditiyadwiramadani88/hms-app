<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Room;
use App\Models\GuestEmergencyContact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KostReportController extends Controller
{
    /**
     * Display the monthly kost report.
     */
    public function index(Request $request)
    {
        $hotelId = active_hotel_id();

        // Default to current month
        $month = $request->filled('month')
            ? Carbon::parse($request->month . '-01')
            : now()->startOfMonth();

        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        // Get all kost bookings that overlap with the selected month
        $bookings = Booking::with(['guest', 'room.roomType', 'transactions.bankAccount'])
            ->where('hotel_id', $hotelId)
            ->whereIn('stay_type', ['monthly', 'yearly'])
            ->where('check_in', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->where('check_out', '>=', $startDate)
                    ->orWhereNull('actual_check_out');
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('check_in', 'asc')
            ->get();

        // Get bank accounts for this hotel (for payment columns)
        $bankAccounts = BankAccount::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Build report data
        $reportData = [];
        $totalHargaKost = 0;
        $totalJaminan = 0;
        $totalPayments = [];

        foreach ($bankAccounts as $account) {
            $totalPayments[$account->id] = 0;
        }

        foreach ($bookings as $index => $booking) {
            // Get payments for this booking within the selected month
            $payments = Transaction::where('booking_id', $booking->id)
                ->where('type', 'payment')
                ->where('status', 'success')
                ->where(function ($q) {
                    $q->where('is_markup', false)->orWhereNull('is_markup');
                })
                ->whereBetween('created_at', [$startDate, $endDate->copy()->endOfDay()])
                ->get();

            $paymentsByAccount = [];
            $paymentDates = [];

            foreach ($bankAccounts as $account) {
                $accountPayments = $payments->where('bank_account_id', $account->id);
                $amount = $accountPayments->sum('amount');
                $paymentsByAccount[$account->id] = $amount;
                $totalPayments[$account->id] += $amount;

                // Collect payment dates
                foreach ($accountPayments as $payment) {
                    $paymentDates[] = $payment->created_at->format('d/m');
                }
            }

            // Also include payments without bank account (legacy)
            $noBankPayments = $payments->whereNull('bank_account_id');
            $paymentsByAccount['no_account'] = $noBankPayments->sum('amount');
            foreach ($noBankPayments as $payment) {
                $paymentDates[] = $payment->created_at->format('d/m');
            }

            // deposit_amount only reflects the deposit set at booking time
            // (createBooking/editDeposit). A deposit added mid-stay via
            // addItemCharge (refundable inventory item) only creates an
            // is_deposit charge Transaction and never touches deposit_amount,
            // so it was invisible here even though it was really collected.
            $extraDeposit = $booking->transactions
                ->filter(fn($t) => $t->type === 'charge' && $t->is_deposit && $t->reference_id !== 'DEPOSIT-' . $booking->id)
                ->sum('amount');
            $jaminan = (float) $booking->deposit_amount + (float) $extraDeposit;

            $totalHargaKost += (float) $booking->base_price;
            $totalJaminan += $jaminan;

            $reportData[] = [
                'no' => $index + 1,
                'nama' => $booking->guest->name ?? '-',
                'type' => $booking->room?->roomType?->name ?? '-',
                'room' => $booking->room?->room_number ?? ($booking->custom_room_name ?? '-'),
                'qty' => $booking->adults + $booking->children,
                'check_in' => $booking->check_in?->format('d/m/Y'),
                'check_out' => $booking->check_out?->format('d/m/Y'),
                'harga_kost' => (float) $booking->base_price,
                'jaminan' => $jaminan,
                'tgl_bayar' => implode(', ', array_unique($paymentDates)),
                'payments_by_account' => $paymentsByAccount,
                'payment_status' => $booking->payment_status,
                'booking_id' => $booking->id,
            ];
        }

        $totals = [
            'harga_kost' => $totalHargaKost,
            'jaminan' => $totalJaminan,
            'payments' => $totalPayments,
        ];

        if ($request->ajax()) {
            return view('reports.partials.kost_report_table', compact(
                'reportData', 'bankAccounts', 'totals', 'month'
            ));
        }

        return view('reports.kost', compact(
            'reportData', 'bankAccounts', 'totals', 'month'
        ));
    }

    /**
     * Export kost report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->month . '-01')
            : now()->startOfMonth();

        $filename = 'laporan_kost_' . $month->format('Y_m') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KostReportExport($month),
            $filename
        );
    }

    /**
     * Export kost report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $hotelId = active_hotel_id();
        $month = $request->filled('month')
            ? Carbon::parse($request->month . '-01')
            : now()->startOfMonth();

        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $bookings = Booking::with(['guest', 'room.roomType', 'transactions.bankAccount'])
            ->where('hotel_id', $hotelId)
            ->whereIn('stay_type', ['monthly', 'yearly'])
            ->where('check_in', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->where('check_out', '>=', $startDate)
                    ->orWhereNull('actual_check_out');
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('check_in', 'asc')
            ->get();

        $bankAccounts = BankAccount::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $reportData = [];
        $totalHargaKost = 0;
        $totalJaminan = 0;
        $totalPayments = [];

        foreach ($bankAccounts as $account) {
            $totalPayments[$account->id] = 0;
        }

        foreach ($bookings as $index => $booking) {
            $payments = Transaction::where('booking_id', $booking->id)
                ->where('type', 'payment')
                ->where('status', 'success')
                ->where(function ($q) {
                    $q->where('is_markup', false)->orWhereNull('is_markup');
                })
                ->whereBetween('created_at', [$startDate, $endDate->copy()->endOfDay()])
                ->get();

            $paymentsByAccount = [];
            $paymentDates = [];

            foreach ($bankAccounts as $account) {
                $accountPayments = $payments->where('bank_account_id', $account->id);
                $amount = $accountPayments->sum('amount');
                $paymentsByAccount[$account->id] = $amount;
                $totalPayments[$account->id] += $amount;

                foreach ($accountPayments as $payment) {
                    $paymentDates[] = $payment->created_at->format('d/m');
                }
            }

            // deposit_amount only reflects the deposit set at booking time
            // (createBooking/editDeposit). A deposit added mid-stay via
            // addItemCharge (refundable inventory item) only creates an
            // is_deposit charge Transaction and never touches deposit_amount,
            // so it was invisible here even though it was really collected.
            $extraDeposit = $booking->transactions
                ->filter(fn($t) => $t->type === 'charge' && $t->is_deposit && $t->reference_id !== 'DEPOSIT-' . $booking->id)
                ->sum('amount');
            $jaminan = (float) $booking->deposit_amount + (float) $extraDeposit;

            $totalHargaKost += (float) $booking->base_price;
            $totalJaminan += $jaminan;

            $reportData[] = [
                'no' => $index + 1,
                'nama' => $booking->guest->name ?? '-',
                'type' => $booking->room?->roomType?->name ?? '-',
                'room' => $booking->room?->room_number ?? ($booking->custom_room_name ?? '-'),
                'qty' => $booking->adults + $booking->children,
                'check_in' => $booking->check_in?->format('d/m/Y'),
                'check_out' => $booking->check_out?->format('d/m/Y'),
                'harga_kost' => (float) $booking->base_price,
                'jaminan' => $jaminan,
                'tgl_bayar' => implode(', ', array_unique($paymentDates)),
                'payments_by_account' => $paymentsByAccount,
            ];
        }

        $totals = [
            'harga_kost' => $totalHargaKost,
            'jaminan' => $totalJaminan,
            'payments' => $totalPayments,
        ];

        $hotel = current_hotel();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.kost_pdf', [
            'reportData' => $reportData,
            'bankAccounts' => $bankAccounts,
            'totals' => $totals,
            'month' => $month,
            'hotel' => $hotel,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('laporan_kost_' . $month->format('Y_m') . '.pdf');
    }

    /**
     * Display the kost tenant list (Data Kost Putra/Putri).
     */
    public function tenantList(Request $request)
    {
        $hotelId = active_hotel_id();

        $rooms = Room::with([
                'bookings' => function ($q) {
                    $q->whereIn('status', ['checked_in', 'confirmed'])
                        ->whereIn('stay_type', ['monthly', 'yearly']);
                },
                'bookings.guest.emergencyContacts',
            ])
            ->where('is_kos', true)
            ->where('hotel_id', $hotelId)
            ->orderBy('room_number')
            ->get();

        $tenantData = [];
        $no = 1;

        foreach ($rooms as $room) {
            $activeBookings = $room->bookings->filter(function ($b) {
                return in_array($b->status, ['checked_in', 'confirmed']);
            });

            $isi = $activeBookings->count();

            if ($activeBookings->isNotEmpty()) {
                foreach ($activeBookings as $index => $booking) {
                    $guest = $booking->guest;
                    $emergency = $guest?->emergencyContacts?->first();

                    $tenantData[] = [
                        'no' => $no++,
                        'kamar' => $room->room_number,
                        'isi' => $isi,
                        'nama_penghuni' => $guest?->name ?? '-',
                        'no_handphone' => $guest?->phone ?? '-',
                        'nama_ortu' => $emergency?->contact_name ?? '-',
                        'handphone_ortu' => $emergency?->phone_number ?? '-',
                        'harga' => (float) $booking->base_price,
                    ];
                }
            }
        }

        if ($request->ajax()) {
            return view('reports.partials.kost_tenant_list_table', compact('tenantData'));
        }

        return view('reports.kost_tenant_list', compact('tenantData'));
    }

    /**
     * Export kost tenant list to PDF.
     */
    public function exportTenantPdf()
    {
        $tenantData = $this->getTenantData();
        $hotel = current_hotel();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.kost_tenant_list_pdf', [
            'tenantData' => $tenantData,
            'hotel' => $hotel,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('data_kost_' . now()->format('Y_m_d') . '.pdf');
    }

    /**
     * Export kost tenant list to Excel.
     */
    public function exportTenantExcel()
    {
        $tenantData = $this->getTenantData();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KostTenantListExport($tenantData),
            'data_kost_' . now()->format('Y_m_d') . '.xlsx'
        );
    }

    private function getTenantData(): array
    {
        $hotelId = active_hotel_id();

        $rooms = Room::with([
                'bookings' => function ($q) {
                    $q->whereIn('status', ['checked_in', 'confirmed'])
                        ->whereIn('stay_type', ['monthly', 'yearly']);
                },
                'bookings.guest.emergencyContacts',
            ])
            ->where('is_kos', true)
            ->where('hotel_id', $hotelId)
            ->orderBy('room_number')
            ->get();

        $tenantData = [];
        $no = 1;

        foreach ($rooms as $room) {
            $activeBookings = $room->bookings->filter(function ($b) {
                return in_array($b->status, ['checked_in', 'confirmed']);
            });

            $isi = $activeBookings->count();

            if ($activeBookings->isNotEmpty()) {
                foreach ($activeBookings as $index => $booking) {
                    $guest = $booking->guest;
                    $emergency = $guest?->emergencyContacts?->first();

                    $tenantData[] = [
                        'no' => $no++,
                        'kamar' => $room->room_number,
                        'isi' => $isi,
                        'nama_penghuni' => $guest?->name ?? '-',
                        'no_handphone' => $guest?->phone ?? '-',
                        'nama_ortu' => $emergency?->contact_name ?? '-',
                        'handphone_ortu' => $emergency?->phone_number ?? '-',
                        'harga' => (float) $booking->base_price,
                    ];
                }
            }
        }

        return $tenantData;
    }
}

<?php

namespace App\Exports;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KostReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Carbon $month;
    protected $bankAccounts;
    protected $reportData;
    protected $totals;

    public function __construct(Carbon $month)
    {
        $this->month = $month;
        $this->buildData();
    }

    protected function buildData(): void
    {
        $hotelId = active_hotel_id();
        $startDate = $this->month->copy()->startOfMonth();
        $endDate = $this->month->copy()->endOfMonth();

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

        $this->bankAccounts = BankAccount::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $this->reportData = [];
        $totalHargaKost = 0;
        $totalJaminan = 0;
        $totalPayments = [];

        foreach ($this->bankAccounts as $account) {
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

            foreach ($this->bankAccounts as $account) {
                $accountPayments = $payments->where('bank_account_id', $account->id);
                $amount = $accountPayments->sum('amount');
                $paymentsByAccount[$account->id] = $amount;
                $totalPayments[$account->id] += $amount;

                foreach ($accountPayments as $payment) {
                    $paymentDates[] = $payment->created_at->format('d/m');
                }
            }

            // deposit_amount only reflects the deposit set at booking time; a
            // deposit added mid-stay via addItemCharge (refundable inventory
            // item) only creates an is_deposit charge Transaction (see
            // KostReportController for the same fix).
            $extraDeposit = $booking->transactions
                ->filter(fn($t) => $t->type === 'charge' && $t->is_deposit && $t->reference_id !== 'DEPOSIT-' . $booking->id)
                ->sum('amount');
            $jaminan = (float) $booking->deposit_amount + (float) $extraDeposit;

            $totalHargaKost += (float) $booking->base_price;
            $totalJaminan += $jaminan;

            $row = [
                $index + 1,
                $booking->guest->name ?? '-',
                $booking->room?->roomType?->name ?? '-',
                $booking->room?->room_number ?? ($booking->custom_room_name ?? '-'),
                $booking->adults + $booking->children,
                $booking->check_in?->format('d/m/Y'),
                $booking->check_out?->format('d/m/Y'),
                (float) $booking->base_price,
                $jaminan,
                implode(', ', array_unique($paymentDates)),
            ];

            foreach ($this->bankAccounts as $account) {
                $row[] = $paymentsByAccount[$account->id] > 0 ? $paymentsByAccount[$account->id] : '';
            }

            $this->reportData[] = $row;
        }

        // Total row
        $totalRow = ['', '', '', '', '', '', 'TOTAL:', $totalHargaKost, $totalJaminan, ''];
        foreach ($this->bankAccounts as $account) {
            $totalRow[] = $totalPayments[$account->id] > 0 ? $totalPayments[$account->id] : '';
        }
        $this->reportData[] = $totalRow;

        $this->totals = [
            'harga_kost' => $totalHargaKost,
            'jaminan' => $totalJaminan,
            'payments' => $totalPayments,
        ];
    }

    public function array(): array
    {
        // Header rows
        $hotel = current_hotel();
        $headerRows = [
            [($hotel->name ?? 'SIMPANG HOMESTAY & KOZZ') . ' - LAPORAN KOST BULANAN'],
            ['Periode: ' . $this->month->translatedFormat('F Y')],
            [], // empty row
        ];

        // Column headers
        $columns = ['NO', 'NAMA', 'TYPE', 'ROOM', 'QTY', 'IN', 'OUT', 'HARGA KOST', 'JAMINAN', 'TGL BAYAR'];
        foreach ($this->bankAccounts as $account) {
            $columns[] = strtoupper($account->name);
        }
        $headerRows[] = $columns;

        return array_merge($headerRows, $this->reportData);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->reportData) + 4; // 3 header rows + 1 column header row
        $lastCol = chr(ord('A') + 9 + count($this->bankAccounts)); // Calculate last column letter

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Kost ' . $this->month->format('M Y');
    }
}

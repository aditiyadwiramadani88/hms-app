<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsReportExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    protected $dateFrom;
    protected $dateTo;
    protected $type;

    public function __construct($dateFrom = null, $dateTo = null, $type = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->type = $type;
    }

    public function collection()
    {
        $startDate = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->startOfDay() : null;
        $endDate = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->endOfDay() : null;

        return Transaction::with(['guest', 'booking.room', 'user', 'bankAccount'])
            ->where('hotel_id', active_hotel_id())
            ->where(function($q) {
                $q->where('is_markup', false)->orWhereNull('is_markup');
            })
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Transaction ID',
            'Type',
            'Guest Name',
            'Room',
            'Description',
            'Payment Method',
            'Bank/Account',
            'Amount (Rp)',
            'Tax (Rp)',
            'Total (Rp)',
            'Processed By',
            'Status'
        ];
    }

    public function map($transaction): array
    {
        $typeLabel = ucfirst($transaction->type);
        if ($transaction->type === 'charge') $typeLabel = 'Tagihan (Charge)';
        if ($transaction->type === 'payment') $typeLabel = 'Pembayaran (Income)';
        if ($transaction->type === 'refund') $typeLabel = 'Pengembalian (Refund)';

        return [
            $transaction->created_at->format('d/m/Y H:i'),
            '#TRX-' . $transaction->id,
            $typeLabel,
            $transaction->guest->name ?? 'System',
            $transaction->booking->room->room_number ?? '-',
            $transaction->description,
            ucfirst($transaction->payment_method ?? 'N/A'),
            $transaction->bankAccount->name ?? '-',
            $transaction->amount - ($transaction->tax_amount ?? 0),
            $transaction->tax_amount ?? 0,
            $transaction->amount,
            $transaction->user->name ?? 'System',
            ucfirst($transaction->status)
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '405189']]],
        ];
    }
}

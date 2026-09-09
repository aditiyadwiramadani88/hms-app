<?php

namespace App\Exports;

use App\Models\BankAccount;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BankAccountMutationExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $bankAccount;
    protected $filters;

    public function __construct(BankAccount $bankAccount, array $filters)
    {
        $this->bankAccount = $bankAccount;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Transaction::where('bank_account_id', $this->bankAccount->id)
            ->with(['booking.guest', 'guest', 'user', 'category'])
            ->where('status', 'success');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.room', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            ['Mutasi Akun: ' . $this->bankAccount->name],
            ['Periode: ' . ($this->filters['date_from'] ?? 'Awal') . ' s/d ' . ($this->filters['date_to'] ?? 'Sekarang')],
            [], // Empty row for spacing
            [
                'Tanggal Waktu',
                'Referensi / Booking',
                'Kategori',
                'Deskripsi',
                'Guest',
                'Kamar',
                'Petugas',
                'Pemasukan (Rp)',
                'Pengeluaran (Rp)'
            ]
        ];
    }

    public function map($transaction): array
    {
        $isPayment = $transaction->type === 'payment';
        
        return [
            $transaction->created_at->format('Y-m-d H:i:s'),
            $transaction->booking_id ? '#BOOK-'.$transaction->booking_id : ($transaction->reference_id ?? '-'),
            $transaction->category->name ?? 'Uncategorized',
            $transaction->description,
            $transaction->booking->guest->name ?? ($transaction->guest->name ?? '-'),
            $transaction->booking->room->room_number ?? '-',
            $transaction->user->name ?? 'System',
            $isPayment ? $transaction->amount : 0,
            !$isPayment ? $transaction->amount : 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'f3f6f9']]],
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\BankAccount;
use App\Models\FinalCashMutation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinalCashMutationExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = FinalCashMutation::where('bank_account_id', $this->bankAccount->id)
            ->with(['user', 'booking.guest', 'booking.room']);

        if (!empty($this->filters['fc_search'])) {
            $search = $this->filters['fc_search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('booking.guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.room', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($this->filters['fc_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['fc_from']);
        }
        if (!empty($this->filters['fc_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['fc_to']);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            ['Laporan Final Cash: ' . $this->bankAccount->name],
            ['Periode: ' . ($this->filters['fc_from'] ?? 'Awal') . ' s/d ' . ($this->filters['fc_to'] ?? 'Sekarang')],
            [],
            [
                'Tanggal Waktu',
                'Tipe',
                'Referensi',
                'Deskripsi',
                'Petugas',
                'Masuk (Rp)',
                'Keluar (Rp)',
                'Saldo Akhir (Rp)',
            ],
        ];
    }

    public function map($fcm): array
    {
        if ($fcm->reference_type === 'booking_checkout') {
            $ref = '#BOOK-' . $fcm->reference_id;
        } elseif ($fcm->reference_type === 'owner_withdrawal') {
            $ref = 'Withdrawal';
        } else {
            $ref = 'Pindah Saldo';
        }

        return [
            $fcm->created_at->format('Y-m-d H:i:s'),
            $fcm->type === 'in' ? 'Masuk' : 'Keluar',
            $ref,
            $fcm->description,
            $fcm->user->name ?? 'System',
            $fcm->type === 'in'  ? $fcm->amount : 0,
            $fcm->type === 'out' ? $fcm->amount : 0,
            $fcm->balance_after,
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

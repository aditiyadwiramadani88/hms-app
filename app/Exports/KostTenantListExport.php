<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KostTenantListExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $tenantData;

    public function __construct(array $tenantData)
    {
        $this->tenantData = $tenantData;
    }

    public function array(): array
    {
        $hotel = current_hotel();
        $headerRows = [
            [($hotel->name ?? 'Data Kost') . ' - DATA KOCHUNI'],
            [now()->translatedFormat('d M Y')],
            [],
        ];

        $columns = ['NO', 'KAMAR', 'ISI', 'NAMA PENGHUNI', 'NO HANDPHONE', 'NAMA ORTU', 'HANDPHONE ORTU', 'HARGA'];
        $headerRows[] = $columns;

        $data = [];
        foreach ($this->tenantData as $row) {
            $data[] = [
                $row['no'],
                $row['kamar'],
                $row['isi'],
                $row['nama_penghuni'],
                $row['no_handphone'],
                $row['nama_ortu'],
                $row['handphone_ortu'],
                $row['harga'] > 0 ? $row['harga'] : '',
            ];
        }

        // Total row
        $data[] = [
            '',
            'TOTAL',
            collect($this->tenantData)->sum('isi'),
            collect($this->tenantData)->filter(fn($r) => $r['nama_penghuni'] !== '-')->count() . ' penghuni',
            '',
            '',
            '',
            collect($this->tenantData)->sum('harga'),
        ];

        return array_merge($headerRows, $data);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->tenantData) + 4;

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
        return 'Data Kost';
    }
}

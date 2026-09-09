<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $rows = [];
        
        // Summary Header
        $rows[] = ['FINANCIAL SUMMARY REPORT'];
        $rows[] = ['Period:', $this->startDate->format('d M Y') . ' - ' . $this->endDate->format('d M Y')];
        $rows[] = [''];

        $rows[] = ['METRIC', 'AMOUNT'];
        $rows[] = ['Total Room Revenue', $this->data['room_revenue']['total']];
        $rows[] = ['Total POS Revenue', $this->data['pos_revenue']['total']];
        $rows[] = ['Total Custom Mark Up Revenue', $this->data['markup_revenue']];
        $rows[] = ['Total Expenses (Purchases)', $this->data['expenses']];
        $rows[] = ['Total Refunds', $this->data['refunds']];
        $rows[] = ['GROSS REVENUE', $this->data['gross_revenue']];
        $rows[] = ['NET PROFIT', $this->data['net_profit']];
        
        $rows[] = [''];
        $rows[] = ['REVENUE BREAKDOWN BY SOURCE'];
        $rows[] = ['Source', 'Amount'];
        
        foreach ($this->data['room_revenue']['by_source'] as $source => $val) {
            $rows[] = ['Room: ' . ucfirst($source), $val['total_revenue']];
        }
        
        foreach ($this->data['pos_revenue']['by_method'] as $method => $val) {
            $rows[] = ['POS: ' . ucfirst($method), $val['total_revenue']];
        }

        $rows[] = [''];
        $rows[] = ['UANG YANG DISETOR (BY ACCOUNT)'];
        $rows[] = ['Account Name', 'Total Amount'];
        
        foreach ($this->data['payments']['by_account'] ?? [] as $acc) {
            $rows[] = [$acc->account_name, $acc->total_amount];
        }
        
        $rows[] = ['TOTAL UANG MASUK RIIL', $this->data['payments']['total'] ?? 0];

        return collect($rows);
    }

    public function headings(): array
    {
        return []; // Headings are part of the collection for custom layout
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            4    => ['font' => ['bold' => true]],
            9    => ['font' => ['bold' => true]],
            10   => ['font' => ['bold' => true, 'color' => ['rgb' => '00B050']]], // Net Profit Green
            12   => ['font' => ['bold' => true]],
            13   => ['font' => ['bold' => true]],
        ];
    }
}

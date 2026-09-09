<?php

namespace Tests\Unit;

use App\Models\Purchase;
use App\Models\Transaction;
use App\Services\ReportService;
use Tests\TestCase;

class RevenueReportExpensesTest extends TestCase
{
    public function test_expenses_include_operational_expense_transactions_not_just_purchases(): void
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $hotelId = session('active_hotel_id') ?? active_hotel_id();

        $report = app(ReportService::class)->getRevenueReport($start, $end);

        $purchases = Purchase::whereBetween('purchase_date', [$start, $end])
            ->where('status', 'received')
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->sum('total_amount');

        $expenseTx = Transaction::whereBetween('created_at', [$start, $end])
            ->where('type', 'expense')
            ->where('status', 'success')
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->sum('amount');

        // Financial report expenses must equal purchases + operational expense
        // transactions (the daily-report kas keluar), not purchases alone.
        $this->assertEqualsWithDelta((float) $purchases + (float) $expenseTx, $report['expenses'], 0.01);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Services\ReportService;
use Tests\TestCase;

class RevenueReportCashReceivedTest extends TestCase
{
    public function test_gross_revenue_stays_invoice_based_for_revenue_report_page(): void
    {
        $report = app(ReportService::class)->getRevenueReport(now()->startOfMonth(), now()->endOfMonth());

        // resources/views/reports/revenue.blade.php sums room_revenue.total + pos_revenue.total
        // as its footer "Total Gross Revenue" -- this must keep holding, or that page's totals
        // stop matching the line items it displays above them.
        $this->assertEquals(
            round($report['room_revenue']['total'] + $report['pos_revenue']['total'], 2),
            $report['gross_revenue']
        );
    }

    public function test_cash_received_excludes_manual_income_deposits(): void
    {
        $report = app(ReportService::class)->getRevenueReport(now()->startOfMonth(), now()->endOfMonth());

        $manualIncomeThisMonth = Transaction::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('type', 'payment')
            ->where('status', 'success')
            ->where('reference_id', 'MANUAL_INCOME')
            ->sum('amount');

        if ($manualIncomeThisMonth <= 0) {
            $this->markTestSkipped('No MANUAL_INCOME transaction this month to verify exclusion against.');
        }

        $allPayments = Transaction::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('type', 'payment')
            ->where('status', 'success')
            ->sum('amount');

        $this->assertEquals(round($allPayments - $manualIncomeThisMonth, 2), $report['cash_received']);
    }

    public function test_cash_received_is_not_greater_than_gross_revenue(): void
    {
        $report = app(ReportService::class)->getRevenueReport(now()->startOfMonth(), now()->endOfMonth());

        // cash_received only counts settled payments; gross_revenue counts full invoice value
        // (including unpaid balances), so cash_received should never exceed it.
        $this->assertLessThanOrEqual($report['gross_revenue'], $report['cash_received']);
    }
}

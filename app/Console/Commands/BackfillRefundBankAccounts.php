<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\BookingService;
use Illuminate\Console\Command;

class BackfillRefundBankAccounts extends Command
{
    protected $signature = 'transactions:backfill-refund-accounts
        {--dry-run : Only show what would be updated, without saving}
        {--with-balance : Also decrement the resolved account\'s balance (use only if the balance was never reduced for these refunds)}';

    protected $description = 'One-off backfill: refund Transactions created without bank_account_id (deposit refunds & cancellation refunds from before the fix) do not appear in any wallet column of the daily report. This assigns each one the account of its booking\'s latest payment (fallback: Tunai, then first account of the hotel).';

    public function handle(BookingService $bookingService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $withBalance = (bool) $this->option('with-balance');

        $refunds = Transaction::withoutGlobalScopes()
            ->where('type', 'refund')
            ->where('status', 'success')
            ->whereNull('bank_account_id')
            ->whereNotNull('booking_id')
            ->with('booking')
            ->get();

        $updated = 0;
        foreach ($refunds as $refund) {
            if (!$refund->booking) {
                $this->warn("Skip Transaction #{$refund->id}: booking #{$refund->booking_id} no longer exists.");
                continue;
            }

            $account = $bookingService->resolveRefundAccount($refund->booking);
            if (!$account) {
                $this->warn("Skip Transaction #{$refund->id}: no bank account found for hotel #{$refund->booking->hotel_id}.");
                continue;
            }

            $paymentMethod = $refund->payment_method
                ?? (str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer');

            $this->line("Transaction #{$refund->id} (Rp " . number_format((float) $refund->amount, 0, ',', '.')
                . ", \"{$refund->description}\") -> {$account->name}"
                . ($withBalance ? ' [balance -' . number_format((float) $refund->amount, 0, ',', '.') . ']' : ''));

            if (!$dryRun) {
                $refund->update([
                    'bank_account_id' => $account->id,
                    'payment_method' => $paymentMethod,
                ]);
                if ($withBalance) {
                    $account->decrement('balance', $refund->amount);
                    $account->decrement('available_balance', $refund->amount);
                }
            }
            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Done. {$updated} refund transaction(s) updated.");

        return Command::SUCCESS;
    }
}

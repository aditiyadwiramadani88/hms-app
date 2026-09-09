<?php

namespace App\Console\Commands;

use App\Models\IncomeTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;

class TagManualIncomeDeposits extends Command
{
    protected $signature = 'income:tag-manual-deposits {--dry-run : Only show what would be tagged, without saving}';
    protected $description = 'One-off backfill: tag pre-existing Transaction rows created by BankAccountController::deposit() with reference_id=MANUAL_INCOME so the daily report stops double-counting them alongside their matching income_transactions row';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $incomes = IncomeTransaction::whereNotNull('bank_account_id')->get();

        $tagged = 0;
        foreach ($incomes as $income) {
            $match = Transaction::where('hotel_id', $income->hotel_id)
                ->where('bank_account_id', $income->bank_account_id)
                ->where('type', 'payment')
                ->where('amount', $income->amount)
                ->whereNull('booking_id')
                ->whereNull('reference_id')
                ->whereDate('created_at', $income->transaction_date)
                ->where(function ($q) use ($income) {
                    $q->where('description', $income->notes)
                        ->orWhere('description', $income->category);
                })
                ->first();

            if (!$match) {
                continue;
            }

            $this->line("Tagging Transaction #{$match->id} (Rp " . number_format((float) $match->amount, 0, ',', '.') . ", \"{$match->description}\") <-> IncomeTransaction #{$income->id}");

            if (!$dryRun) {
                $match->update(['reference_id' => 'MANUAL_INCOME']);
            }
            $tagged++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Done. {$tagged} transaction(s) tagged.");

        return Command::SUCCESS;
    }
}

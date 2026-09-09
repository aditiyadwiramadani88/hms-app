<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\FinalCashMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinalCashService
{
    public function recordIn(BankAccount $account, float $amount, string $referenceType, ?int $referenceId, string $description): FinalCashMutation
    {
        if (!$account->isCashAccount()) {
            throw new \Exception('Final Cash mutations only apply to cash accounts.');
        }

        $lastBalance = $this->getLastBalance($account);
        $balanceAfter = $lastBalance + $amount;

        $mutation = FinalCashMutation::create([
            'hotel_id' => $account->hotel_id,
            'bank_account_id' => $account->id,
            'type' => 'in',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'user_id' => Auth::id(),
        ]);

        $this->verifyConsistency($account, $balanceAfter);

        return $mutation;
    }

    public function recordOut(BankAccount $account, float $amount, string $referenceType, ?int $referenceId, string $description): FinalCashMutation
    {
        if (!$account->isCashAccount()) {
            throw new \Exception('Final Cash mutations only apply to cash accounts.');
        }

        $lastBalance = $this->getLastBalance($account);
        $balanceAfter = $lastBalance - $amount;

        $mutation = FinalCashMutation::create([
            'hotel_id' => $account->hotel_id,
            'bank_account_id' => $account->id,
            'type' => 'out',
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'user_id' => Auth::id(),
        ]);

        $this->verifyConsistency($account, $balanceAfter);

        return $mutation;
    }

    public function getLastBalance(BankAccount $account): float
    {
        $last = FinalCashMutation::where('bank_account_id', $account->id)
            ->latest('id')
            ->first();

        // If no mutations exist yet, use available_balance as starting point
        if (!$last) {
            return (float) $account->available_balance;
        }

        return (float) $last->balance_after;
    }

    private function verifyConsistency(BankAccount $account, float $expectedBalance): void
    {
        $account->refresh();
        $actualAvailable = (float) $account->available_balance;

        if (abs($actualAvailable - $expectedBalance) > 0.01) {
            Log::warning('Final Cash balance mismatch', [
                'bank_account_id' => $account->id,
                'available_balance' => $actualAvailable,
                'final_cash_balance' => $expectedBalance,
            ]);
        }
    }
}

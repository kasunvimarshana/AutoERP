<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\CashRegister;
use Modules\POS\Models\CashRegisterTransaction;
use Modules\POS\Enums\CashRegisterStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashRegisterService
{
    public function openRegister(CashRegister $cashRegister, float $openingBalance, string $userId): CashRegister
    {
        return DB::transaction(function () use ($cashRegister, $openingBalance, $userId) {
            $cashRegister->update([
                'status' => CashRegisterStatus::OPEN,
                'user_id' => $userId,
                'opening_balance' => $openingBalance,
                'opened_at' => Carbon::now(),
                'closed_at' => null,
                'closing_balance' => null,
            ]);

            // Create opening transaction
            CashRegisterTransaction::create([
                'cash_register_id' => $cashRegister->id,
                'type' => 'cash_in',
                'amount' => $openingBalance,
                'payment_method' => 'cash',
                'notes' => 'Opening balance',
                'created_by' => $userId,
            ]);

            return $cashRegister;
        });
    }

    public function closeRegister(CashRegister $cashRegister, float $closingBalance): CashRegister
    {
        return DB::transaction(function () use ($cashRegister, $closingBalance) {
            $cashRegister->update([
                'status' => CashRegisterStatus::CLOSED,
                'closing_balance' => $closingBalance,
                'closed_at' => Carbon::now(),
            ]);

            // Create closing transaction
            CashRegisterTransaction::create([
                'cash_register_id' => $cashRegister->id,
                'type' => 'cash_out',
                'amount' => $closingBalance,
                'payment_method' => 'cash',
                'notes' => 'Closing balance',
                'created_by' => $cashRegister->user_id ?? auth()->id(),
            ]);

            return $cashRegister;
        });
    }

    public function addCashTransaction(
        CashRegister $cashRegister,
        string $type,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $notes = null
    ): CashRegisterTransaction {
        return CashRegisterTransaction::create([
            'cash_register_id' => $cashRegister->id,
            'type' => $type,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    public function getCurrentBalance(CashRegister $cashRegister): float
    {
        $cashIn = $cashRegister->cashTransactions()
            ->where('type', 'cash_in')
            ->sum('amount');

        $cashOut = $cashRegister->cashTransactions()
            ->where('type', 'cash_out')
            ->sum('amount');

        return $cashIn - $cashOut;
    }

    public function getExpectedBalance(CashRegister $cashRegister): float
    {
        // Opening balance + transactions
        $transactionTotal = $cashRegister->transactions()
            ->where('payment_status', 'paid')
            ->sum('paid_amount');

        return $cashRegister->opening_balance + $transactionTotal;
    }
}

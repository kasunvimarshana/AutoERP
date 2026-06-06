<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Invoice\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentCalculationResult;
use Modules\Payment\Models\Payment;

final class PaymentCalculationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function calculateForCreation(CreatePaymentData $data): PaymentCalculationResult
    {
        $totalAmount = '0.000000';
        $lineAmounts = [];

        foreach ($data->lines as $line) {
            $amount = $this->math->normalize($line->amount);
            $totalAmount = $this->math->add($totalAmount, $amount);
            $lineAmounts[] = $amount;
        }

        return new PaymentCalculationResult(
            totalAmount: $totalAmount,
            allocatedAmount: '0.000000',
            unappliedAmount: $totalAmount,
            refundedAmount: '0.000000',
            lineAmounts: $lineAmounts,
        );
    }

    public function recalculate(Payment $payment): PaymentCalculationResult
    {
        $totalAmount = $this->math->normalize((string) $payment->lines()->sum('amount'));
        $allocatedAmount = $this->math->normalize((string) $payment->allocations()
            ->where('status', 'active')
            ->sum('allocated_amount'));
        $refundedAmount = $this->math->normalize((string) $payment->refunds()->sum('amount'));
        $unappliedAmount = $this->math->sub($this->math->sub($totalAmount, $allocatedAmount), $refundedAmount);

        return new PaymentCalculationResult(
            totalAmount: $totalAmount,
            allocatedAmount: $allocatedAmount,
            unappliedAmount: $unappliedAmount,
            refundedAmount: $refundedAmount,
        );
    }
}

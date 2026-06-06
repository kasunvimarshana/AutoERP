<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Services\DecimalMath;
use Modules\Payment\DTOs\PaymentRefundData;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentRefund;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentRefundService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentValidationService $validator,
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentStatusService $statuses,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
    ) {}

    public function refund(PaymentRefundData $data): PaymentRefund
    {
        return DB::transaction(function () use ($data): PaymentRefund {
            $payment = Payment::query()->lockForUpdate()->findOrFail($data->paymentId);
            $this->statuses->assertAllocatable($payment);
            $this->validator->assertPositive($data->amount, 'Payment refund amount');

            if ($this->math->compare($data->amount, (string) $payment->unapplied_amount) > 0) {
                throw new InvalidArgumentException('Payment refund cannot exceed unapplied payment balance.');
            }

            $refund = PaymentRefund::query()->create([
                'tenant_id' => $payment->tenant_id,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'refund_number' => $data->refundNumber,
                'refund_date' => $data->refundDate,
                'party_type' => $data->partyType ?? $payment->party_type,
                'party_id' => $data->partyId ?? $payment->party_id,
                'payment_method_id' => $data->paymentMethodId,
                'amount' => $this->math->normalize($data->amount),
                'reason' => $data->reason,
                'status' => $data->status,
            ]);

            $this->syncPayment($payment->refresh());

            return $refund->refresh();
        });
    }

    private function syncPayment(Payment $payment): Payment
    {
        $calculation = $this->calculations->recalculate($payment);

        $payment->forceFill([
            'total_amount' => $calculation->totalAmount,
            'allocated_amount' => $calculation->allocatedAmount,
            'unapplied_amount' => $calculation->unappliedAmount,
            'refunded_amount' => $calculation->refundedAmount,
        ])->save();

        $payment = $this->statuses->applyCalculatedStatus($payment->refresh(), $calculation->totalAmount, $calculation->allocatedAmount);
        $this->unappliedBalances->sync($payment);

        return $payment->refresh();
    }
}

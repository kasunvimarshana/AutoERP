<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\UnappliedBalanceStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentReversal;

final class PaymentReversalService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceSettlementServiceInterface $invoiceSettlements,
        private readonly PaymentStatusService $statuses,
    ) {}

    public function reverse(PaymentReversalData $data): PaymentReversal
    {
        return DB::transaction(function () use ($data): PaymentReversal {
            $payment = Payment::query()
                ->with(['allocations', 'unappliedBalance'])
                ->lockForUpdate()
                ->findOrFail($data->paymentId);

            $status = $payment->status instanceof PaymentStatus
                ? $payment->status
                : PaymentStatus::from((string) $payment->status);

            if ($payment->reversals()->exists()) {
                throw new InvalidArgumentException('Payment reversal already exists for this payment.');
            }

            if (in_array($status, [PaymentStatus::Cancelled, PaymentStatus::Void, PaymentStatus::Reversed], true)) {
                throw new InvalidArgumentException('Cancelled, void, or reversed payments cannot be reversed.');
            }

            foreach ($payment->allocations()->where('status', AllocationStatus::Active->value)->get() as $allocation) {
                $this->invoiceSettlements->reversePaymentAllocation(
                    (int) $allocation->invoice_id,
                    (string) $allocation->allocated_amount,
                );

                $allocation->forceFill([
                    'status' => AllocationStatus::Reversed->value,
                ])->save();
            }

            $reversal = PaymentReversal::query()->create([
                'tenant_id' => $payment->tenant_id,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'reversal_number' => $data->reversalNumber,
                'reversal_date' => $data->reversalDate,
                'reason' => $data->reason,
                'reversed_by' => $data->reversedBy,
                'original_amount' => $payment->total_amount,
                'reversed_amount' => $this->math->normalize((string) $payment->total_amount),
                'status' => $data->status,
                'metadata' => $data->metadata,
            ]);

            $payment->forceFill([
                'status' => PaymentStatus::Reversed->value,
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '0.000000',
                'voided_by' => $data->reversedBy,
                'voided_at' => now(),
                'void_reason' => $data->reason,
            ])->save();
            $this->statuses->record($payment->refresh(), $status, PaymentStatus::Reversed, $data->reversedBy, $data->reason);

            if ($payment->unappliedBalance !== null) {
                $payment->unappliedBalance->forceFill([
                    'allocated_amount' => '0.000000',
                    'remaining_amount' => '0.000000',
                    'status' => UnappliedBalanceStatus::Cancelled->value,
                ])->save();
            }

            return $reversal->refresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;

final class PaymentAllocationFinanceService
{
    private const SOURCE_MODULE = 'payment';

    public function __construct(
        private readonly FinancePostingInterface $postings,
        private readonly FinanceSourceReversalInterface $reversals,
        private readonly PaymentPostingPolicyService $policies,
    ) {}

    public function post(Payment $payment, PaymentAllocation $allocation, ?int $actorId = null): void
    {
        $postingStatus = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
        if ($postingStatus !== PaymentPostingStatus::Posted) {
            return;
        }
        $allocationStatus = $allocation->status instanceof AllocationStatus
            ? $allocation->status
            : AllocationStatus::from((string) $allocation->status);
        if ($allocationStatus !== AllocationStatus::Active) {
            throw new InvalidArgumentException('Only active payment allocations can be posted to Finance.');
        }

        $policy = $this->policies->resolve($payment);
        if ($policy->allocatedRole === $policy->unappliedRole) {
            throw new InvalidArgumentException('Payment allocation requires distinct allocated and unapplied Finance roles.');
        }

        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
        $amount = (string) $allocation->allocated_amount;
        $lines = $direction === PaymentDirection::Inbound
            ? [
                new PostingLine(
                    debit: $amount,
                    credit: '0.000000',
                    description: 'Apply unapplied payment '.$payment->payment_number,
                    profileKey: $policy->unappliedRole->value,
                    sourceLineType: PaymentSourceType::PaymentAllocation->value,
                    sourceLineId: (int) $allocation->getKey(),
                ),
                new PostingLine(
                    debit: '0.000000',
                    credit: $amount,
                    description: 'Settle invoice '.$allocation->invoice_number_snapshot,
                    profileKey: $policy->allocatedRole->value,
                    sourceLineType: PaymentSourceType::PaymentAllocation->value,
                    sourceLineId: (int) $allocation->getKey(),
                ),
            ]
            : [
                new PostingLine(
                    debit: $amount,
                    credit: '0.000000',
                    description: 'Settle supplier invoice '.$allocation->invoice_number_snapshot,
                    profileKey: $policy->allocatedRole->value,
                    sourceLineType: PaymentSourceType::PaymentAllocation->value,
                    sourceLineId: (int) $allocation->getKey(),
                ),
                new PostingLine(
                    debit: '0.000000',
                    credit: $amount,
                    description: 'Apply supplier advance '.$payment->payment_number,
                    profileKey: $policy->unappliedRole->value,
                    sourceLineType: PaymentSourceType::PaymentAllocation->value,
                    sourceLineId: (int) $allocation->getKey(),
                ),
            ];

        $this->postings->post(new PostingContext(
            source: new PostingSourceData(
                sourceType: PaymentSourceType::PaymentAllocation->value,
                sourceId: (int) $allocation->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: self::SOURCE_MODULE,
                sourceNumber: (string) $payment->payment_number.'-A'.(int) $allocation->getKey(),
                sourceDate: $allocation->allocation_date?->toDateString(),
            ),
            postingDate: $allocation->allocation_date?->toDateString()
                ?? $payment->payment_date?->toDateString()
                ?? now()->toDateString(),
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            description: 'Payment allocation '.$payment->payment_number.' to '.$allocation->invoice_number_snapshot,
            postingProfileCode: $policy->postingProfileCode,
        ), $actorId);
    }

    public function reverse(
        Payment $payment,
        PaymentAllocation $allocation,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
    ): void {
        $this->reversals->reverseSource(
            (int) $payment->tenant_id,
            $payment->organization_unit_id,
            self::SOURCE_MODULE,
            PaymentSourceType::PaymentAllocation->value,
            (int) $allocation->getKey(),
            $reversalDate,
            $actorId,
            $reason,
        );
    }
}

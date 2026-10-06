<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Payment\Constants\PaymentAllocationFinanceMetadata;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;

final class PaymentAllocationFinanceService
{
    private const SOURCE_MODULE = 'payment';

    public function __construct(
        private readonly DecimalMath $math,
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
        if ($policy->allocationTargetRole === $policy->unappliedRole) {
            throw new InvalidArgumentException('Payment allocation requires distinct target and unapplied Finance roles.');
        }

        $direction = $this->direction($payment);
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
                    profileKey: $policy->allocationTargetRole->value,
                    sourceLineType: PaymentSourceType::PaymentAllocation->value,
                    sourceLineId: (int) $allocation->getKey(),
                ),
            ]
            : [
                new PostingLine(
                    debit: $amount,
                    credit: '0.000000',
                    description: 'Settle supplier invoice '.$allocation->invoice_number_snapshot,
                    profileKey: $policy->allocationTargetRole->value,
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

        $result = $this->postings->post(new PostingContext(
            source: $this->source($payment, $allocation, PaymentSourceType::PaymentAllocation),
            postingDate: $this->postingDate($payment, $allocation),
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            description: 'Payment allocation '.$payment->payment_number.' to '.$allocation->invoice_number_snapshot,
            postingProfileCode: $policy->postingProfileCode,
        ), $actorId);

        $metadata = array_merge(is_array($allocation->metadata) ? $allocation->metadata : [], [
            PaymentAllocationFinanceMetadata::POSTING_REFERENCE => $result->journalNumber,
        ]);
        $fxReference = $this->postRealizedFx($payment, $allocation, $policy->postingProfileCode, $policy->allocationTargetRole, $actorId);
        if ($fxReference !== null) {
            $metadata[PaymentAllocationFinanceMetadata::FX_POSTING_REFERENCE] = $fxReference;
        }

        $allocation->forceFill([
            'metadata' => $metadata,
            'row_version' => (int) $allocation->row_version + 1,
        ])->save();
    }

    public function reverse(
        Payment $payment,
        PaymentAllocation $allocation,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
    ): void {
        $metadata = is_array($allocation->metadata) ? $allocation->metadata : [];
        if (trim((string) ($metadata[PaymentAllocationFinanceMetadata::POSTING_REFERENCE] ?? '')) === '') {
            throw new InvalidArgumentException(
                'Payment allocation has no independent Finance posting. Reverse the owning payment instead.',
            );
        }
        if (trim((string) ($metadata[PaymentAllocationFinanceMetadata::REVERSAL_REFERENCE] ?? '')) !== '') {
            throw new InvalidArgumentException('Payment allocation Finance reclassification is already reversed.');
        }

        $result = $this->reversals->reverseSource(
            (int) $payment->tenant_id,
            $payment->organization_unit_id,
            self::SOURCE_MODULE,
            PaymentSourceType::PaymentAllocation->value,
            (int) $allocation->getKey(),
            $reversalDate,
            $actorId,
            $reason,
        );
        $metadata[PaymentAllocationFinanceMetadata::REVERSAL_REFERENCE] = $result->journalNumber;

        if (trim((string) ($metadata[PaymentAllocationFinanceMetadata::FX_POSTING_REFERENCE] ?? '')) !== '') {
            if (trim((string) ($metadata[PaymentAllocationFinanceMetadata::FX_REVERSAL_REFERENCE] ?? '')) !== '') {
                throw new InvalidArgumentException('Payment allocation realized FX posting is already reversed.');
            }

            $fxResult = $this->reversals->reverseSource(
                (int) $payment->tenant_id,
                $payment->organization_unit_id,
                self::SOURCE_MODULE,
                PaymentSourceType::PaymentAllocationFx->value,
                (int) $allocation->getKey(),
                $reversalDate,
                $actorId,
                $reason,
            );
            $metadata[PaymentAllocationFinanceMetadata::FX_REVERSAL_REFERENCE] = $fxResult->journalNumber;
        }

        $allocation->forceFill([
            'metadata' => $metadata,
            'row_version' => (int) $allocation->row_version + 1,
        ])->save();
    }

    private function postRealizedFx(
        Payment $payment,
        PaymentAllocation $allocation,
        string $postingProfileCode,
        PaymentPostingRole $targetRole,
        ?int $actorId,
    ): ?string {
        $invoiceRate = trim((string) $allocation->invoice_exchange_rate_snapshot);
        if ($invoiceRate === '' || $this->math->compare($invoiceRate, '0') <= 0) {
            throw new InvalidArgumentException('Payment allocation requires a positive invoice exchange rate snapshot.');
        }

        $paymentRate = (string) $payment->exchange_rate;
        $difference = $this->math->mul(
            (string) $allocation->allocated_amount,
            $this->math->sub($paymentRate, $invoiceRate),
        );
        if ($this->math->isZero($difference)) {
            return null;
        }

        $absolute = $this->math->isNegative($difference) ? substr($difference, 1) : $difference;
        $direction = $this->direction($payment);
        if ($direction === PaymentDirection::Inbound) {
            $lines = $this->math->isNegative($difference)
                ? [
                    new PostingLine(
                        debit: '0.000000',
                        credit: $absolute,
                        description: 'Realized FX settlement adjustment '.$allocation->invoice_number_snapshot,
                        profileKey: $targetRole->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                    new PostingLine(
                        debit: $absolute,
                        credit: '0.000000',
                        description: 'Realized FX loss '.$allocation->invoice_number_snapshot,
                        profileKey: PaymentPostingRole::RealizedFxLoss->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                ]
                : [
                    new PostingLine(
                        debit: $absolute,
                        credit: '0.000000',
                        description: 'Realized FX settlement adjustment '.$allocation->invoice_number_snapshot,
                        profileKey: $targetRole->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                    new PostingLine(
                        debit: '0.000000',
                        credit: $absolute,
                        description: 'Realized FX gain '.$allocation->invoice_number_snapshot,
                        profileKey: PaymentPostingRole::RealizedFxGain->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                ];
        } else {
            $lines = $this->math->isNegative($difference)
                ? [
                    new PostingLine(
                        debit: $absolute,
                        credit: '0.000000',
                        description: 'Realized FX settlement adjustment '.$allocation->invoice_number_snapshot,
                        profileKey: $targetRole->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                    new PostingLine(
                        debit: '0.000000',
                        credit: $absolute,
                        description: 'Realized FX gain '.$allocation->invoice_number_snapshot,
                        profileKey: PaymentPostingRole::RealizedFxGain->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                ]
                : [
                    new PostingLine(
                        debit: '0.000000',
                        credit: $absolute,
                        description: 'Realized FX settlement adjustment '.$allocation->invoice_number_snapshot,
                        profileKey: $targetRole->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                    new PostingLine(
                        debit: $absolute,
                        credit: '0.000000',
                        description: 'Realized FX loss '.$allocation->invoice_number_snapshot,
                        profileKey: PaymentPostingRole::RealizedFxLoss->value,
                        sourceLineType: PaymentSourceType::PaymentAllocationFx->value,
                        sourceLineId: (int) $allocation->getKey(),
                    ),
                ];
        }

        $result = $this->postings->post(new PostingContext(
            source: $this->source($payment, $allocation, PaymentSourceType::PaymentAllocationFx),
            postingDate: $this->postingDate($payment, $allocation),
            currencyId: null,
            exchangeRate: '1.000000',
            lines: $lines,
            description: 'Realized FX on payment allocation '.$payment->payment_number.' to '.$allocation->invoice_number_snapshot,
            postingProfileCode: $postingProfileCode,
        ), $actorId);

        return $result->journalNumber;
    }

    private function source(
        Payment $payment,
        PaymentAllocation $allocation,
        PaymentSourceType $sourceType,
    ): PostingSourceData {
        $suffix = $sourceType === PaymentSourceType::PaymentAllocationFx ? '-FX' : '';

        return new PostingSourceData(
            sourceType: $sourceType->value,
            sourceId: (int) $allocation->getKey(),
            tenantId: (int) $payment->tenant_id,
            organizationUnitId: $payment->organization_unit_id,
            sourceModule: self::SOURCE_MODULE,
            sourceNumber: (string) $payment->payment_number.'-A'.(int) $allocation->getKey().$suffix,
            sourceDate: $allocation->allocation_date?->toDateString(),
        );
    }

    private function postingDate(Payment $payment, PaymentAllocation $allocation): string
    {
        return $allocation->allocation_date?->toDateString()
            ?? $payment->payment_date?->toDateString()
            ?? now()->toDateString();
    }

    private function direction(Payment $payment): PaymentDirection
    {
        return $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
    }
}

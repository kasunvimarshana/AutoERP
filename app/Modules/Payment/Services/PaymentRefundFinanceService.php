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
use Modules\Payment\Constants\PaymentRefundFinanceMetadata;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;

final class PaymentRefundFinanceService
{
    private const SOURCE_MODULE = 'payment';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
        private readonly FinanceSourceReversalInterface $reversals,
        private readonly PaymentPostingPolicyService $postingPolicy,
        private readonly PaymentRefundPolicyService $refundPolicy,
    ) {}

    public function post(Payment $refund, ?int $actorId = null): ?string
    {
        if ($this->paymentType($refund) !== PaymentType::Refund) {
            return null;
        }

        $original = $this->refundPolicy->originalForPosting($refund);
        $originalPolicy = $this->postingPolicy->resolve($original);
        $difference = $this->math->mul(
            (string) $refund->total_amount,
            $this->math->sub((string) $refund->exchange_rate, (string) $original->exchange_rate),
        );
        if ($this->math->isZero($difference)) {
            return null;
        }

        $absolute = $this->absolute($difference);
        $lines = $this->fxLines(
            $this->direction($refund),
            $originalPolicy->unappliedRole,
            $absolute,
            $this->math->isNegative($difference),
            (string) $refund->payment_number,
            (int) $refund->getKey(),
        );

        $result = $this->postings->post(new PostingContext(
            source: new PostingSourceData(
                sourceType: PaymentSourceType::PaymentRefundFx->value,
                sourceId: (int) $refund->getKey(),
                tenantId: (int) $refund->tenant_id,
                organizationUnitId: $refund->organization_unit_id,
                sourceModule: self::SOURCE_MODULE,
                sourceNumber: (string) $refund->payment_number.'-FX',
                sourceDate: $refund->payment_date?->toDateString(),
            ),
            postingDate: $refund->payment_date?->toDateString() ?? now()->toDateString(),
            currencyId: null,
            exchangeRate: '1.000000',
            lines: $lines,
            description: 'Realized FX on refund '.$refund->payment_number,
            postingProfileCode: $originalPolicy->postingProfileCode,
        ), $actorId);

        return $result->journalNumber;
    }

    public function reverse(
        Payment $refund,
        string $reversalDate,
        ?int $actorId = null,
        ?string $reason = null,
    ): ?string {
        if ($this->paymentType($refund) !== PaymentType::Refund) {
            return null;
        }

        $metadata = is_array($refund->metadata) ? $refund->metadata : [];
        if (trim((string) ($metadata[PaymentRefundFinanceMetadata::FX_POSTING_REFERENCE] ?? '')) === '') {
            return null;
        }
        if (trim((string) ($metadata[PaymentRefundFinanceMetadata::FX_REVERSAL_REFERENCE] ?? '')) !== '') {
            throw new InvalidArgumentException('Refund realized FX posting is already reversed.');
        }

        $result = $this->reversals->reverseSource(
            (int) $refund->tenant_id,
            $refund->organization_unit_id,
            self::SOURCE_MODULE,
            PaymentSourceType::PaymentRefundFx->value,
            (int) $refund->getKey(),
            $reversalDate,
            $actorId,
            $reason,
        );

        return $result->journalNumber;
    }

    /**
     * @return list<PostingLine>
     */
    private function fxLines(
        PaymentDirection $refundDirection,
        PaymentPostingRole $unappliedRole,
        string $amount,
        bool $negativeDifference,
        string $paymentNumber,
        int $paymentId,
    ): array {
        if ($refundDirection === PaymentDirection::Outbound) {
            return $negativeDifference
                ? [
                    $this->line($amount, '0.000000', $unappliedRole, 'Refund FX carrying-value adjustment '.$paymentNumber, $paymentId),
                    $this->line('0.000000', $amount, PaymentPostingRole::RealizedFxGain, 'Realized FX gain '.$paymentNumber, $paymentId),
                ]
                : [
                    $this->line('0.000000', $amount, $unappliedRole, 'Refund FX carrying-value adjustment '.$paymentNumber, $paymentId),
                    $this->line($amount, '0.000000', PaymentPostingRole::RealizedFxLoss, 'Realized FX loss '.$paymentNumber, $paymentId),
                ];
        }

        return $negativeDifference
            ? [
                $this->line('0.000000', $amount, $unappliedRole, 'Refund FX carrying-value adjustment '.$paymentNumber, $paymentId),
                $this->line($amount, '0.000000', PaymentPostingRole::RealizedFxLoss, 'Realized FX loss '.$paymentNumber, $paymentId),
            ]
            : [
                $this->line($amount, '0.000000', $unappliedRole, 'Refund FX carrying-value adjustment '.$paymentNumber, $paymentId),
                $this->line('0.000000', $amount, PaymentPostingRole::RealizedFxGain, 'Realized FX gain '.$paymentNumber, $paymentId),
            ];
    }

    private function line(
        string $debit,
        string $credit,
        PaymentPostingRole $role,
        string $description,
        int $paymentId,
    ): PostingLine {
        return new PostingLine(
            debit: $debit,
            credit: $credit,
            description: $description,
            profileKey: $role->value,
            sourceLineType: PaymentSourceType::PaymentRefundFx->value,
            sourceLineId: $paymentId,
        );
    }

    private function absolute(string $amount): string
    {
        return $this->math->isNegative($amount) ? substr($amount, 1) : $amount;
    }

    private function paymentType(Payment $payment): PaymentType
    {
        return $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::from((string) $payment->payment_type);
    }

    private function direction(Payment $payment): PaymentDirection
    {
        return $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Payment\Application\Contracts\Services\RefundServiceInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class RefundService implements RefundServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function refundPayment(int|string $sourcePaymentId, array $payload): Result
    {
        try {
            $source = $this->paymentRepository->findById($sourcePaymentId);
            if (! $source instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Source payment not found.'));
            }

            $sourceStatus = strtolower((string) $source->get('status', PaymentStatus::DRAFT));
            if (! in_array($sourceStatus, [PaymentStatus::POSTED, PaymentStatus::RECONCILED], true)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Only posted or reconciled payments can be refunded.',
                ));
            }

            $refundNumber = trim((string) ($payload['payment_number'] ?? ''));
            if ($refundNumber === '') {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'payment_number is required for refund.',
                ));
            }

            $refundAmount = isset($payload['amount'])
                ? round((float) $payload['amount'], 4)
                : round((float) $source->get('amount', 0), 4);
            if ($refundAmount <= 0 || $refundAmount > (float) $source->get('amount', 0)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Refund amount must be greater than zero and not exceed source amount.',
                ));
            }

            $refund = $this->paymentRepository->create([
                'row_version' => 1,
                'tenant_id' => (int) $source->get('tenant_id'),
                'organization_unit_id' => $source->get('organization_unit_id'),
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                'party_type' => $source->get('party_type'),
                'party_id' => $source->get('party_id'),
                'reference' => $payload['reference'] ?? ('Refund for ' . (string) $source->get('payment_number')),
                'payment_number' => $refundNumber,
                'payment_date' => $payload['payment_date'] ?? $this->clock->now()->format('Y-m-d'),
                'amount' => $refundAmount,
                'allocated_amount' => 0,
                'direction' => $this->reverseDirection((string) $source->get('direction', 'inbound')),
                'payment_group_id' => $source->get('payment_group_id'),
                'payment_method_id' => (int) ($payload['payment_method_id'] ?? $source->get('payment_method_id')),
                'account_id' => (int) ($payload['account_id'] ?? $source->get('account_id')),
                'currency_id' => $source->get('currency_id'),
                'exchange_rate' => (float) $source->get('exchange_rate', 1),
                'base_amount' => round($refundAmount * (float) $source->get('exchange_rate', 1), 4),
                'status' => PaymentStatus::POSTED,
                'notes' => $payload['notes'] ?? 'Auto-generated refund payment.',
                'idempotency_key' => $payload['idempotency_key'] ?? null,
                'journal_entry_id' => null,
                'reversal_of_payment_id' => (int) $source->id(),
                'created_by' => $payload['created_by'] ?? null,
                'posted_by' => $payload['posted_by'] ?? null,
                'posted_at' => $payload['posted_at'] ?? $this->clock->now()->format('Y-m-d H:i:s'),
            ]);

            return Result::success($refund);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function reverseDirection(string $direction): string
    {
        return strtolower(trim($direction)) === 'inbound' ? 'outbound' : 'inbound';
    }
}

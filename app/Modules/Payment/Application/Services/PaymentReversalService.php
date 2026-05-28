<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentReversalServiceInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class PaymentReversalService implements PaymentReversalServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
    }

    public function reversePayment(int|string $paymentId, array $payload): Result
    {
        try {
            $payment = $this->paymentRepository->findById($paymentId);
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            $status = strtolower((string) $payment->get('status', PaymentStatus::DRAFT));
            if (! in_array($status, [PaymentStatus::POSTED, PaymentStatus::RECONCILED], true)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Only posted or reconciled payments can be reversed.',
                ));
            }

            $existingReversal = $this->findExistingReversal((int) $payment->id());
            if ($existingReversal instanceof DataRecord) {
                return Result::success([
                    'source_payment' => $payment->toArray(),
                    'reversal_payment' => $existingReversal->toArray(),
                ]);
            }

            $reverseNumber = trim((string) ($payload['payment_number'] ?? ''));
            if ($reverseNumber === '') {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'payment_number is required for reversal.',
                ));
            }

            return $this->paymentRepository->transaction(function () use ($payment, $payload, $reverseNumber): Result {
                $reversal = $this->paymentRepository->create([
                    'row_version' => 1,
                    'tenant_id' => (int) $payment->get('tenant_id'),
                    'organization_unit_id' => $payment->get('organization_unit_id'),
                    'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                    'party_type' => $payment->get('party_type'),
                    'party_id' => $payment->get('party_id'),
                    'reference' => $payload['reference'] ?? ('Reversal of ' . (string) $payment->get('payment_number')),
                    'payment_number' => $reverseNumber,
                    'payment_date' => $payload['payment_date'] ?? now()->toDateString(),
                    'amount' => (float) $payment->get('amount', 0),
                    'allocated_amount' => 0,
                    'direction' => $this->reverseDirection((string) $payment->get('direction', 'inbound')),
                    'payment_group_id' => $payment->get('payment_group_id'),
                    'payment_method_id' => (int) $payment->get('payment_method_id'),
                    'account_id' => (int) $payment->get('account_id'),
                    'currency_id' => $payment->get('currency_id'),
                    'exchange_rate' => (float) $payment->get('exchange_rate', 1),
                    'base_amount' => (float) $payment->get('base_amount', 0),
                    'status' => PaymentStatus::POSTED,
                    'notes' => $payload['notes'] ?? 'Auto-generated reversal payment.',
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                    'journal_entry_id' => null,
                    'reversal_of_payment_id' => (int) $payment->id(),
                    'created_by' => $payload['created_by'] ?? null,
                    'posted_by' => $payload['posted_by'] ?? null,
                    'posted_at' => $payload['posted_at'] ?? now(),
                ]);

                $source = $this->paymentRepository->update((int) $payment->id(), [
                    'status' => PaymentStatus::REVERSED,
                    'reversed_by' => $payload['reversed_by'] ?? null,
                    'reversed_at' => $payload['reversed_at'] ?? now(),
                    'row_version' => ((int) $payment->get('row_version', 1)) + 1,
                ]);

                return Result::success([
                    'source_payment' => $source->toArray(),
                    'reversal_payment' => $reversal->toArray(),
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function findExistingReversal(int $sourcePaymentId): ?DataRecord
    {
        $matches = $this->paymentRepository->list(['reversal_of_payment_id' => $sourcePaymentId]);
        foreach ($matches as $match) {
            if ($match instanceof DataRecord) {
                return $match;
            }
        }

        return null;
    }

    private function reverseDirection(string $direction): string
    {
        return strtolower(trim($direction)) === 'inbound' ? 'outbound' : 'inbound';
    }
}

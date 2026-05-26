<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\UnallocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class UnallocatePaymentDocumentService implements UnallocatePaymentDocumentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentAllocationRepositoryInterface $allocations,
    ) {
    }

    public function execute(int|string $paymentId, array $payload): Result
    {
        try {
            $payment = $this->payments->findById($paymentId);
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            $paymentStatus = strtolower(trim((string) $payment->get('status', PaymentStatus::DRAFT)));
            if ($paymentStatus === PaymentStatus::VOIDED) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Voided payments cannot be unallocated.',
                ));
            }

            $allocation = $this->resolveAllocation((int) $payment->id(), $payload);
            if (! $allocation instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment allocation not found.'));
            }

            $result = $this->payments->transaction(function () use ($payment, $allocation): array {
                $this->allocations->delete((int) $allocation->id());

                $remainingAllocations = $this->allocations->list(['payment_id' => (int) $payment->id()]);
                $allocatedTotal = $this->sumAllocated($remainingAllocations);
                $paymentAmount = (float) $payment->get('amount', 0);

                $nextStatus = $allocatedTotal <= 0
                    ? (strtolower(trim((string) $payment->get('status', PaymentStatus::DRAFT))) === PaymentStatus::DRAFT
                        ? PaymentStatus::DRAFT
                        : PaymentStatus::POSTED)
                    : ($allocatedTotal >= round($paymentAmount, 4)
                        ? PaymentStatus::RECONCILED
                        : PaymentStatus::POSTED);

                $updatedPayment = $this->payments->update((int) $payment->id(), [
                    'status' => $nextStatus,
                    'row_version' => ((int) $payment->get('row_version', 1)) + 1,
                ]);

                return [
                    'payment' => $updatedPayment->toArray(),
                    'removed_allocation_id' => (int) $allocation->id(),
                    'allocated_total' => $allocatedTotal,
                    'unallocated_amount' => round($paymentAmount - $allocatedTotal, 4),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveAllocation(int $paymentId, array $payload): ?DataRecord
    {
        if (isset($payload['allocation_id'])) {
            $allocation = $this->allocations->findById((int) $payload['allocation_id']);
            if (
                $allocation instanceof DataRecord
                && (int) $allocation->get('payment_id', 0) === $paymentId
            ) {
                return $allocation;
            }
        }

        $documentType = trim((string) ($payload['document_type'] ?? ''));
        $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;

        if ($documentType === '' || $documentId < 1) {
            return null;
        }

        $matches = $this->allocations->list([
            'payment_id' => $paymentId,
            'document_type' => $documentType,
            'document_id' => $documentId,
        ]);

        foreach ($matches as $match) {
            if ($match instanceof DataRecord) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @param list<DataRecord> $allocations
     */
    private function sumAllocated(array $allocations): float
    {
        $sum = 0.0;
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof DataRecord) {
                continue;
            }

            $sum += (float) $allocation->get('allocated_amount', 0);
        }

        return round($sum, 4);
    }
}

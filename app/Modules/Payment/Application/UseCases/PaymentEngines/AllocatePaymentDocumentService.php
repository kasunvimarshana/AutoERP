<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\AllocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class AllocatePaymentDocumentService implements AllocatePaymentDocumentServiceInterface
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
                    'Voided payments cannot be allocated.',
                ));
            }

            $documentType = trim((string) ($payload['document_type'] ?? ''));
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;
            $allocatedAmount = (float) ($payload['allocated_amount'] ?? 0);

            if ($documentType === '' || $documentId < 1 || $allocatedAmount <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'document_type, document_id and allocated_amount are required.',
                ));
            }

            $existing = $this->allocations->list([
                'payment_id' => (int) $payment->id(),
                'document_type' => $documentType,
                'document_id' => $documentId,
            ]);
            if ($existing !== []) {
                return Result::failure(new Error(
                    PaymentErrorCode::CONFLICT,
                    'Allocation already exists for this payment and document.',
                ));
            }

            $result = $this->payments->transaction(function () use (
                $payment,
                $payload,
                $documentType,
                $documentId,
                $allocatedAmount,
            ): array {
                $allAllocations = $this->allocations->list(['payment_id' => (int) $payment->id()]);
                $currentlyAllocated = $this->sumAllocated($allAllocations);

                $paymentAmount = (float) $payment->get('amount', 0);
                $remaining = round($paymentAmount - $currentlyAllocated, 4);
                if ($allocatedAmount > $remaining) {
                    throw new \RuntimeException(PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT);
                }

                $allocation = $this->allocations->create([
                    'tenant_id' => (int) $payment->get('tenant_id'),
                    'organization_unit_id' => $payment->get('organization_unit_id'),
                    'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                    'payment_id' => (int) $payment->id(),
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'reference' => isset($payload['reference']) ? trim((string) $payload['reference']) : null,
                    'allocated_amount' => round($allocatedAmount, 4),
                    'row_version' => 1,
                ]);

                $newAllocated = round($currentlyAllocated + $allocatedAmount, 4);
                $nextStatus = $newAllocated >= round($paymentAmount, 4)
                    ? PaymentStatus::RECONCILED
                    : PaymentStatus::POSTED;

                $updatedPayment = $this->payments->update((int) $payment->id(), [
                    'status' => $nextStatus,
                    'row_version' => ((int) $payment->get('row_version', 1)) + 1,
                ]);

                return [
                    'payment' => $updatedPayment->toArray(),
                    'allocation' => $allocation->toArray(),
                    'allocated_total' => $newAllocated,
                    'unallocated_amount' => round($paymentAmount - $newAllocated, 4),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            $code = $exception->getMessage() === PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT
                ? PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT
                : PaymentErrorCode::INVALID_VALUE;

            return Result::failure(new Error($code, $exception->getMessage()));
        }
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
